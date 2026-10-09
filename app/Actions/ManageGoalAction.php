<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageGoalAction
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrganizationalScopeResolver $scopeResolver,
    ) {}

    /** Buat goal baru */
    public function create(User $actor, array $data): Goal
    {
        if (! $actor->can('goal.create')) {
            throw new AuthorizationException;
        }

        $this->assertTargetIsAllowed($actor, $data);

        // Validasi parent jika ada
        if (! empty($data['parent_id'])) {
            $parent = Goal::findOrFail($data['parent_id']);
            if (! $actor->can('view', $parent)) {
                throw new AuthorizationException;
            }
            $levelHierarchy = ['COMPANY' => 0, 'BRANCH' => 1, 'DIVISION' => 2, 'INDIVIDUAL' => 3];
            $parentLevel = $levelHierarchy[$parent->level] ?? 0;
            $childLevel = $levelHierarchy[$data['level']] ?? 0;

            if ($childLevel <= $parentLevel) {
                throw ValidationException::withMessages([
                    'level' => 'Level target harus lebih rendah dari target induk.',
                ]);
            }

            // Pastikan sama periode
            if ((int) $parent->period_id !== (int) $data['period_id']) {
                throw ValidationException::withMessages([
                    'period_id' => 'Target harus dalam periode yang sama dengan target induk.',
                ]);
            }
        }

        $goal = Goal::create([
            'parent_id' => $data['parent_id'] ?? null,
            'period_id' => $data['period_id'],
            'level' => $data['level'],
            'goalable_type' => $data['goalable_type'] ?? null,
            'goalable_id' => $data['goalable_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_value' => $data['target_value'] ?? null,
            'target_unit' => $data['target_unit'] ?? null,
            'status' => 'DRAFT',
            'created_by' => $actor->id,
        ]);

        $this->audit->log('goal.created', $actor, $goal, null, $goal->toArray());

        return $goal;
    }

    private function assertTargetIsAllowed(User $actor, array $data): void
    {
        $levelTargets = [
            'COMPANY' => null,
            'BRANCH' => Branch::class,
            'DIVISION' => Division::class,
            'INDIVIDUAL' => Employee::class,
        ];
        $expectedType = $levelTargets[$data['level']];
        $actualType = $data['goalable_type'] ?? null;

        if ($actualType !== $expectedType || ($expectedType === null && ! empty($data['goalable_id']))) {
            throw ValidationException::withMessages(['goalable_type' => 'Target harus sesuai dengan level target.']);
        }

        if ($expectedType === null) {
            if ($this->scopeResolver->allowedBranchIds($actor) !== null) {
                throw new AuthorizationException;
            }

            return;
        }

        $target = $expectedType::query()->findOrFail($data['goalable_id']);
        $isAllowed = match ($expectedType) {
            Branch::class => $this->scopeResolver->allows($actor, $target->id),
            Division::class => $this->scopeResolver->allows($actor, $target->branch_id, $target->id),
            Employee::class => $this->scopeResolver->allowsEmployee($actor, $target),
        };

        if (! $isAllowed) {
            throw new AuthorizationException;
        }
    }

    /** Update goal */
    public function update(User $actor, Goal $goal, array $data): Goal
    {
        if (! $actor->can('update', $goal)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $goal, $data): Goal {
            $before = $goal->toArray();

            $goal->update([
                'title' => $data['title'] ?? $goal->title,
                'description' => $data['description'] ?? $goal->description,
                'target_value' => $data['target_value'] ?? $goal->target_value,
                'target_unit' => $data['target_unit'] ?? $goal->target_unit,
                'actual_value' => $data['actual_value'] ?? $goal->actual_value,
            ]);

            // Hitung ulang pencapaian
            $achievement = $goal->calculateAchievement();
            if ($achievement !== null) {
                $goal->update(['achievement_percentage' => $achievement]);
            }

            // Sync parent jika ada
            if ($goal->parent_id) {
                $goal->parent->syncFromChildren();
            }

            $this->audit->log('goal.updated', $actor, $goal, $before, $goal->fresh()->toArray());

            return $goal->fresh();
        });
    }

    /** Transisi status goal */
    public function transition(User $actor, Goal $goal, string $newStatus): Goal
    {
        if (! $actor->can('update', $goal)) {
            throw new AuthorizationException;
        }

        $allowed = [
            'DRAFT' => ['ACTIVE'],
            'ACTIVE' => ['COMPLETED', 'CANCELLED'],
        ];

        if (! in_array($newStatus, $allowed[$goal->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status tidak valid.']);
        }

        return DB::transaction(function () use ($actor, $goal, $newStatus): Goal {
            $before = $goal->toArray();
            $goal->update(['status' => $newStatus]);
            $this->audit->log('goal.status_changed', $actor, $goal, $before, $goal->fresh()->toArray());

            return $goal->fresh();
        });
    }
}
