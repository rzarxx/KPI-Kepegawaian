<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationComponentRequest;
use App\Http\Requests\StoreEvaluationCriterionRequest;
use App\Http\Requests\StorePerformancePeriodRequest;
use App\Http\Requests\TransitionPerformancePeriodRequest;
use App\Models\EmployeeAttentionRule;
use App\Models\EvaluationComponent;
use App\Models\EvaluationCriterion;
use App\Models\PerformancePeriod;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationConfigurationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PerformancePeriod::class);
        $isImpersonating = $request->session()->has('impersonation.original_user_id');

        return Inertia::render('Evaluations/Configuration', [
            'periods' => PerformancePeriod::query()->latest('end_date')->get(),
            'components' => EvaluationComponent::query()->with('rules')->orderBy('sort_order')->get(),
            'criteria' => EvaluationCriterion::query()->orderBy('sort_order')->get(),
            'attentionRules' => EmployeeAttentionRule::query()->orderBy('id')->get(),
            'canManage' => ! $isImpersonating && $request->user()->can('organization.manage'),
            'canManageSettings' => ! $isImpersonating && $request->user()->can('settings.manage'),
        ]);
    }

    public function storePeriod(StorePerformancePeriodRequest $request, AuditLogger $audit)
    {
        $period = PerformancePeriod::query()->create($request->validated());
        $audit->log('evaluation.period.create', $request->user(), $period, null, $period->toArray());

        return back()->with('success', 'Periode penilaian berhasil ditambahkan.');
    }

    public function updatePeriod(StorePerformancePeriodRequest $request, PerformancePeriod $period, AuditLogger $audit)
    {
        $this->authorize('update', $period);
        $before = $period->toArray();
        $period->update($request->validated());
        $audit->log('evaluation.period.update', $request->user(), $period, $before, $period->fresh()->toArray());

        return back()->with('success', 'Periode penilaian berhasil diperbarui.');
    }

    public function transitionPeriod(TransitionPerformancePeriodRequest $request, PerformancePeriod $period, AuditLogger $audit)
    {
        $this->authorize('transition', $period);
        $next = $request->validated('status');
        $allowed = ['DRAFT' => 'ACTIVE', 'ACTIVE' => 'REVIEW', 'REVIEW' => 'FINALIZED', 'FINALIZED' => 'CLOSED'];
        if (($allowed[$period->status] ?? null) !== $next) {
            throw ValidationException::withMessages(['status' => 'Perubahan status periode tidak sesuai urutan.']);
        }
        if ($next === 'ACTIVE' && round((float) EvaluationComponent::query()->where('is_active', true)->sum('default_weight'), 2) !== 100.0) {
            throw ValidationException::withMessages(['status' => 'Total bobot komponen aktif harus tepat 100% sebelum periode diaktifkan.']);
        }
        $before = $period->toArray();
        $period->update(['status' => $next, 'is_active' => in_array($next, ['ACTIVE', 'REVIEW'], true)]);
        $audit->log('evaluation.period.transition', $request->user(), $period, $before, $period->fresh()->toArray());

        return back()->with('success', 'Status periode berhasil diperbarui.');
    }

    public function storeComponent(StoreEvaluationComponentRequest $request, AuditLogger $audit)
    {
        $data = $request->safe()->except('rules');
        $this->ensureActiveWeight($data);
        $component = DB::transaction(function () use ($data, $request) {
            $component = EvaluationComponent::query()->create($data);
            $component->rules()->createMany($request->validated('rules', []));

            return $component;
        });
        $audit->log('evaluation.component.create', $request->user(), $component, null, $component->load('rules')->toArray());

        return back()->with('success', 'Komponen penilaian berhasil ditambahkan.');
    }

    public function updateComponent(StoreEvaluationComponentRequest $request, EvaluationComponent $component, AuditLogger $audit)
    {
        $this->authorize('update', $component);
        $before = $component->load('rules')->toArray();
        $data = $request->safe()->except('rules');
        $this->ensureActiveWeight($data, $component);
        DB::transaction(function () use ($component, $data, $request) {
            $component->update($data);
            $component->rules()->delete();
            $component->rules()->createMany($request->validated('rules', []));
        });
        $audit->log('evaluation.component.update', $request->user(), $component, $before, $component->fresh()->load('rules')->toArray());

        return back()->with('success', 'Komponen penilaian berhasil diperbarui.');
    }

    public function storeCriterion(StoreEvaluationCriterionRequest $request, AuditLogger $audit)
    {
        $criterion = EvaluationCriterion::query()->create($request->validated());
        $audit->log('evaluation.criterion.create', $request->user(), $criterion, null, $criterion->toArray());

        return back()->with('success', 'Kriteria hasil berhasil ditambahkan.');
    }

    public function updateCriterion(StoreEvaluationCriterionRequest $request, EvaluationCriterion $criterion, AuditLogger $audit)
    {
        $this->authorize('update', $criterion);
        $before = $criterion->toArray();
        $criterion->update($request->validated());
        $audit->log('evaluation.criterion.update', $request->user(), $criterion, $before, $criterion->fresh()->toArray());

        return back()->with('success', 'Kriteria hasil berhasil diperbarui.');
    }

    private function ensureActiveWeight(array $data, ?EvaluationComponent $except = null): void
    {
        if (! ($data['is_active'] ?? false)) {
            return;
        }
        $total = (float) EvaluationComponent::query()->where('is_active', true)->when($except, fn ($query) => $query->whereKeyNot($except->id))->sum('default_weight') + (float) $data['default_weight'];
        if ($total > 100.0001) {
            throw ValidationException::withMessages(['default_weight' => 'Total bobot komponen aktif tidak boleh melebihi 100%.']);
        }
    }
}
