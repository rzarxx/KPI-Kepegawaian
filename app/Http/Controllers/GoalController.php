<?php

namespace App\Http\Controllers;

use App\Actions\ManageGoalAction;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Models\Branch;
use App\Models\Goal;
use App\Models\PerformancePeriod;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalController extends Controller
{
    /** Halaman daftar target/goal */
    public function index(Request $request, OrganizationalScopeResolver $scopeResolver)
    {
        $this->authorize('viewAny', Goal::class);

        $user = $request->user();
        $periodId = $request->query('period_id');
        $level = $request->query('level');

        $query = Goal::query()
            ->with(['parent:id,title,level', 'period:id,name', 'children'])
            ->orderByDesc('created_at');

        // Organizational scope: filter goals berdasarkan scope user
        $allowedBranchIds = $scopeResolver->allowedBranchIds($user);
        if ($allowedBranchIds !== null) {
            $branchModel = (new Branch)->getMorphClass();
            $query->where(function ($q) use ($allowedBranchIds, $branchModel) {
                // Goal perusahaan (COMPANY level tanpa goalable) tetap terlihat
                $q->where('level', 'COMPANY')
                    // Goal cabang yang termasuk dalam scope
                    ->orWhere(function ($q2) use ($allowedBranchIds, $branchModel) {
                        $q2->where('goalable_type', $branchModel)
                            ->whereIn('goalable_id', $allowedBranchIds);
                    })
                    // Goal divisi/individu yang cabangnya termasuk scope
                    ->orWhereHas('goalable', function ($q3) use ($allowedBranchIds) {
                        // Division has branch_id
                        $q3->whereIn('branch_id', $allowedBranchIds);
                    })
                    // Goal individu (employee) via currentAssignment.branch_id
                    ->orWhere(function ($q4) use ($allowedBranchIds) {
                        $q4->where('level', 'INDIVIDUAL')
                            ->whereNull('goalable_type');
                    });
            });
        }

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        if ($level) {
            $query->where('level', $level);
        }

        $goals = $query->paginate(25)->through(fn (Goal $goal) => [
            'id' => $goal->id,
            'title' => $goal->title,
            'description' => $goal->description,
            'level' => $goal->level,
            'parent_title' => $goal->parent?->title,
            'parent_level' => $goal->parent?->level,
            'period_name' => $goal->period->name,
            'target_value' => $goal->target_value,
            'target_unit' => $goal->target_unit,
            'actual_value' => $goal->actual_value,
            'achievement_percentage' => $goal->achievement_percentage,
            'status' => $goal->status,
            'children_count' => $goal->children->count(),
        ]);

        $periods = PerformancePeriod::query()
            ->whereIn('status', ['ACTIVE', 'REVIEW', 'FINALIZED', 'CLOSED'])
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        // Goal induk yang bisa jadi parent
        $parentGoals = Goal::query()
            ->whereIn('level', ['COMPANY', 'BRANCH', 'DIVISION'])
            ->whereIn('status', ['DRAFT', 'ACTIVE'])
            ->orderBy('level')
            ->orderBy('title')
            ->get(['id', 'title', 'level', 'period_id']);

        return Inertia::render('Goals/Index', [
            'goals' => $goals,
            'periods' => $periods,
            'parentGoals' => $parentGoals,
            'filters' => [
                'period_id' => $periodId,
                'level' => $level,
            ],
            'canCreate' => $request->user()->can('goal.create'),
            'canUpdate' => $request->user()->can('goal.update'),
        ]);
    }

    /** Simpan goal baru */
    public function store(StoreGoalRequest $request, ManageGoalAction $action)
    {
        $action->create($request->user(), $request->validated());

        return back()->with('success', 'Target berhasil ditambahkan.');
    }

    /** Update goal */
    public function update(Goal $goal, UpdateGoalRequest $request, ManageGoalAction $action)
    {
        $action->update($request->user(), $goal, $request->validated());

        return back()->with('success', 'Target berhasil diperbarui.');
    }

    /** Transisi status goal */
    public function transition(Goal $goal, Request $request, ManageGoalAction $action)
    {
        $data = $request->validate([
            'status' => ['required', 'in:ACTIVE,COMPLETED,CANCELLED'],
        ]);

        $action->transition($request->user(), $goal, $data['status']);

        return back()->with('success', 'Status target berhasil diperbarui.');
    }
}
