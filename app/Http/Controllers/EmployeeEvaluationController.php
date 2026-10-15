<?php

namespace App\Http\Controllers;

use App\Actions\SaveEmployeeEvaluationAction;
use App\Actions\TransitionEmployeeEvaluationAction;
use App\Http\Requests\StoreEmployeeEvaluationRequest;
use App\Http\Requests\TransitionEmployeeEvaluationRequest;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Services\EvaluationTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeEvaluationController extends Controller
{
    public function index(Request $request, EvaluationTaskService $tasks): Response
    {
        abort_unless($request->user()->can('evaluation.view'), 403);
        $filters = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:performance_periods,id'],
        ]);

        return Inertia::render('Evaluations/Index', [
            ...$tasks->gather($request->user(), isset($filters['period_id']) ? (int) $filters['period_id'] : null),
            'filters' => $filters,
        ]);
    }

    public function create(Employee $employee, Request $request): Response
    {
        $this->authorize('evaluate', $employee);
        $periodId = $request->validate([
            'period_id' => ['required', 'integer', 'exists:performance_periods,id'],
        ])['period_id'];
        $period = PerformancePeriod::query()
            ->where('is_active', true)
            ->where('status', 'ACTIVE')
            ->findOrFail($periodId);
        $this->authorize('view', $period);

        $evaluation = EmployeeEvaluation::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->where('evaluation_type', EmployeeEvaluation::TYPE_SUPERVISOR)
            ->with('scores')
            ->first();

        if ($evaluation && $evaluation->evaluator_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Evaluations/Form', $this->formData($request, $employee, $period, $evaluation));
    }

    public function store(
        Employee $employee,
        StoreEmployeeEvaluationRequest $request,
        SaveEmployeeEvaluationAction $action,
    ): RedirectResponse {
        $this->authorize('evaluate', $employee);
        $period = PerformancePeriod::query()
            ->where('is_active', true)
            ->where('status', 'ACTIVE')
            ->findOrFail($request->integer('period_id'));
        $this->authorize('view', $period);

        $evaluation = $action->execute($request->user(), $employee, $period, $request->validated());

        return redirect()->route('evaluations.index', ['period_id' => $period->id])->with(
            'success',
            $evaluation->status === 'FINALIZED'
                ? 'Penilaian berhasil diselesaikan.'
                : 'Draf penilaian berhasil disimpan.',
        );
    }

    public function show(EmployeeEvaluation $evaluation, Request $request): Response
    {
        $this->authorize('view', $evaluation);

        return Inertia::render('Evaluations/Form', $this->formData(
            $request,
            $evaluation->employee,
            $evaluation->period,
            $evaluation->load('scores'),
        ));
    }

    public function transition(
        TransitionEmployeeEvaluationRequest $request,
        EmployeeEvaluation $evaluation,
        TransitionEmployeeEvaluationAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $evaluation,
            $request->validated('status'),
            $request->validated('reason'),
        );

        return back()->with('success', 'Penilaian berhasil dibuka kembali sebagai draf.');
    }

    /** @return array<string, mixed> */
    private function formData(
        Request $request,
        Employee $employee,
        ?PerformancePeriod $period,
        ?EmployeeEvaluation $evaluation,
    ): array {
        return [
            'employee' => $employee->only('id', 'full_name', 'employee_number'),
            'period' => $period ? [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
            ] : null,
            'components' => EvaluationComponent::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'default_weight', 'is_auto_calculated']),
            'evaluation' => $evaluation ? [
                'id' => $evaluation->id,
                'status' => $evaluation->status,
                'notes' => $evaluation->notes,
                'total_score' => $evaluation->total_score,
                'scores' => $evaluation->scores->map(fn ($score): array => [
                    'component_id' => $score->component_id,
                    'raw_score' => (string) $score->raw_score,
                    'note' => $score->note,
                ])->values(),
            ] : null,
            'abilities' => [
                'edit' => ! $evaluation || $request->user()->can('update', $evaluation),
                'complete' => ! $evaluation || $request->user()->can('complete', $evaluation),
                'reopen' => $evaluation && $request->user()->can('reopen', $evaluation),
            ],
        ];
    }
}
