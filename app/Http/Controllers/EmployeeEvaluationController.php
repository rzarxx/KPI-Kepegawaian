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
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeEvaluationController extends Controller
{
    public function create(Employee $employee, Request $request)
    {
        $this->authorize('evaluate', $employee);

        $periodId = $request->query('period_id');
        $period = $periodId ? PerformancePeriod::findOrFail($periodId) : null;

        if ($period) {
            $this->authorize('view', $period);
        }

        // Period is required — redirect to employee page if none provided
        if (! $period) {
            return redirect()->route('employees.show', $employee)
                ->with('error', 'Pilih periode penilaian terlebih dahulu.');
        }

        $lookupAttributes = [
            'employee_id' => $employee->id,
            'evaluator_id' => $request->user()->id,
            'period_id' => $period->id,
            'evaluation_type' => EmployeeEvaluation::TYPE_SUPERVISOR,
        ];
        $evaluation = EmployeeEvaluation::query()->where($lookupAttributes)->with('scores')->first();

        return Inertia::render('Evaluations/Form', $this->formData($request, $employee, $period, $evaluation));
    }

    public function store(Employee $employee, StoreEmployeeEvaluationRequest $request, SaveEmployeeEvaluationAction $action)
    {
        $this->authorize('evaluate', $employee);

        $periodId = $request->input('period_id');
        if (! $periodId) {
            return back()->withErrors(['period_id' => 'Periode penilaian wajib dipilih.']);
        }
        $period = PerformancePeriod::findOrFail($periodId);
        $this->authorize('view', $period);

        $data = $request->validated();
        $evaluation = $action->execute($request->user(), $employee, $period, $data);

        return redirect()->route('evaluations.show', $evaluation)->with('success', 'Draf penilaian berhasil disimpan.');
    }

    public function show(EmployeeEvaluation $evaluation, Request $request)
    {
        $this->authorize('view', $evaluation);

        return Inertia::render('Evaluations/Form', $this->formData($request, $evaluation->employee, $evaluation->period, $evaluation->load('scores')));
    }

    public function transition(TransitionEmployeeEvaluationRequest $request, EmployeeEvaluation $evaluation, TransitionEmployeeEvaluationAction $action)
    {
        $action->execute($request->user(), $evaluation, $request->validated('status'));

        return back()->with('success', 'Status penilaian berhasil diperbarui.');
    }

    private function formData(Request $request, Employee $employee, ?PerformancePeriod $period, ?EmployeeEvaluation $evaluation): array
    {
        $periods = PerformancePeriod::query()
            ->where('is_active', true)
            ->whereIn('status', ['DRAFT', 'ACTIVE'])
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'start_date', 'end_date']);

        // Ambil self-assessment karyawan untuk periode ini (jika sudah diajukan)
        $selfAssessment = null;
        if ($period) {
            $selfEval = EmployeeEvaluation::query()
                ->where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->where('evaluation_type', EmployeeEvaluation::TYPE_SELF)
                ->whereIn('status', ['SUBMITTED', 'DRAFT'])
                ->with('scores')
                ->first();
            if ($selfEval) {
                $selfAssessment = [
                    'id' => $selfEval->id,
                    'status' => $selfEval->status,
                    'total_score' => $selfEval->total_score,
                    'notes' => $selfEval->notes,
                    'scores' => $selfEval->scores->map(fn ($s) => [
                        'component_id' => $s->component_id,
                        'raw_score' => (string) $s->raw_score,
                        'note' => $s->note,
                    ])->values(),
                ];
            }
        }

        return [
            'employee' => $employee->only('id', 'full_name', 'employee_number'),
            'period' => $period ? $period->only('id', 'name', 'start_date', 'end_date') : null,
            'periods' => $periods->map(fn (PerformancePeriod $p) => $p->only('id', 'name', 'start_date', 'end_date')),
            'components' => EvaluationComponent::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'default_weight', 'is_auto_calculated']),
            'evaluation' => $evaluation ? [
                'id' => $evaluation->id,
                'status' => $evaluation->status,
                'notes' => $evaluation->notes,
                'total_score' => $evaluation->total_score,
                'scores' => $evaluation->scores->map(fn ($score) => ['component_id' => $score->component_id, 'raw_score' => (string) $score->raw_score, 'note' => $score->note])->values(),
            ] : null,
            'selfAssessment' => $selfAssessment,
            'abilities' => [
                'edit' => ! $evaluation || $request->user()->can('update', $evaluation),
                'submit' => $evaluation && $request->user()->can('submit', $evaluation),
                'approve' => $evaluation && $request->user()->can('approve', $evaluation),
                'finalize' => $evaluation && $request->user()->can('finalize', $evaluation),
                'close' => $evaluation && $request->user()->can('close', $evaluation),
            ],
        ];
    }
}
