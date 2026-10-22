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
    /** @var array<string, class-string<Branch|Division|Employee>|null> */
    private const LEVEL_TARGETS = [
        'COMPANY' => null,
        'BRANCH' => Branch::class,
        'DIVISION' => Division::class,
        'INDIVIDUAL' => Employee::class,
    ];

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

        $target = $this->resolveAllowedTarget($actor, $data);

        if (! empty($data['parent_id'])) {
            $parent = Goal::findOrFail($data['parent_id']);
            $this->assertParentIsAllowed($actor, $parent, $target, $data);
        }

        return DB::transaction(function () use ($actor, $data, $target): Goal {
            $goal = Goal::create([
                'parent_id' => $data['parent_id'] ?? null,
                'period_id' => $data['period_id'],
                'level' => $data['level'],
                'goalable_type' => $target?->getMorphClass(),
                'goalable_id' => $target?->getKey(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'target_value' => $data['target_value'] ?? null,
                'target_unit' => $data['target_unit'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $actor->id,
            ]);

            $this->audit->log('goal.created', $actor, $goal, null, $goal->toArray());

            return $goal;
        });
    }

    private function resolveAllowedTarget(User $actor, array $data): Branch|Division|Employee|null
    {
        $expectedType = self::LEVEL_TARGETS[$data['level']];

        if ($expectedType === null) {
            if ($this->scopeResolver->allowedBranchIds($actor) !== null) {
                throw new AuthorizationException;
            }

            return null;
        }

        $target = $expectedType::query()->find($data['goalable_id'] ?? null);
        if ($target === null) {
            throw ValidationException::withMessages([
                'goalable_id' => 'Sasaran target tidak ditemukan.',
            ]);
        }

        if (($target instanceof Branch || $target instanceof Division) && ! $target->is_active) {
            throw ValidationException::withMessages([
                'goalable_id' => 'Sasaran target sudah tidak aktif.',
            ]);
        }

        if ($target instanceof Employee) {
            $target->loadMissing('currentAssignment');
            $status = $target->current_status->value;

            if ($target->currentAssignment === null || ! in_array($status, ['ACTIVE', 'PROBATION'], true)) {
                throw ValidationException::withMessages([
                    'goalable_id' => 'Pejuang yang dipilih tidak memiliki penempatan aktif.',
                ]);
            }
        }

        $isAllowed = match ($expectedType) {
            Branch::class => $this->scopeResolver->allows($actor, $target->id),
            Division::class => $this->scopeResolver->allows($actor, $target->branch_id, $target->id),
            Employee::class => $this->scopeResolver->allowsEmployee($actor, $target),
        };

        if (! $isAllowed) {
            throw new AuthorizationException;
        }

        return $target;
    }

    private function assertParentIsAllowed(
        User $actor,
        Goal $parent,
        Branch|Division|Employee|null $target,
        array $data,
    ): void {
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

        if ((int) $parent->period_id !== (int) $data['period_id']) {
            throw ValidationException::withMessages([
                'period_id' => 'Target harus dalam periode yang sama dengan target induk.',
            ]);
        }

        $matchesTarget = match ($parent->level) {
            'COMPANY' => $parent->goalable_id === null,
            'BRANCH' => match (true) {
                $target instanceof Division => (int) $target->branch_id === (int) $parent->goalable_id,
                $target instanceof Employee => (int) $target->currentAssignment?->branch_id === (int) $parent->goalable_id,
                default => false,
            },
            'DIVISION' => $target instanceof Employee
                && (int) $target->currentAssignment?->division_id === (int) $parent->goalable_id,
            default => false,
        };

        if (! $matchesTarget) {
            throw ValidationException::withMessages([
                'parent_id' => 'Target induk tidak sesuai dengan sasaran organisasi yang dipilih.',
            ]);
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
