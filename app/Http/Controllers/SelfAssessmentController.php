<?php

namespace App\Http\Controllers;

use App\Actions\SaveSelfAssessmentAction;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelfAssessmentController extends Controller
{
    /** Tampilkan daftar periode untuk self-assessment */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->can('evaluation.self_assess')) {
            abort(403, 'Anda tidak memiliki izin untuk penilaian diri.');
        }

        $employee = $user->employee()->first();

        if (! $employee) {
            return Inertia::render('SelfAssessment/Index', [
                'periods' => [],
                'employee' => null,
                'error' => 'Akun Anda belum terhubung dengan data karyawan.',
            ]);
        }

        $periods = PerformancePeriod::query()
            ->where('is_active', true)
            ->whereIn('status', ['ACTIVE'])
            ->orderByDesc('start_date')
            ->get();

        // Ambil semua self-assessment sekaligus (hindari N+1)
        $existingEvaluations = EmployeeEvaluation::query()
            ->where('employee_id', $employee->id)
            ->where('evaluator_id', $user->id)
            ->where('evaluation_type', EmployeeEvaluation::TYPE_SELF)
            ->whereIn('period_id', $periods->pluck('id'))
            ->get(['id', 'period_id', 'status'])
            ->keyBy('period_id');

        $periodsData = $periods->map(function (PerformancePeriod $period) use ($existingEvaluations) {
            $existing = $existingEvaluations->get($period->id);

            return [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'frequency' => $period->frequency,
                'self_assessment_id' => $existing?->id,
                'self_assessment_status' => $existing?->status,
            ];
        });

        return Inertia::render('SelfAssessment/Index', [
            'periods' => $periodsData,
            'employee' => $employee->only('id', 'full_name', 'employee_number'),
        ]);
    }

    /** Form penilaian diri */
    public function create(Request $request, PerformancePeriod $period)
    {
        $user = $request->user();

        if (! $user->can('evaluation.self_assess')) {
            abort(403, 'Anda tidak memiliki izin untuk penilaian diri.');
        }

        $employee = $user->employee()->first();
        abort_unless($employee !== null, 403, 'Akun Anda belum terhubung dengan data pejuang.');

        $existing = EmployeeEvaluation::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->where('evaluator_id', $user->id)
            ->where('evaluation_type', EmployeeEvaluation::TYPE_SELF)
            ->with('scores')
            ->first();

        $components = EvaluationComponent::query()
            ->where('is_active', true)
            ->where('is_auto_calculated', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'default_weight', 'is_auto_calculated']);

        return Inertia::render('SelfAssessment/Form', [
            'employee' => $employee->only('id', 'full_name', 'employee_number'),
            'period' => $period->only('id', 'name', 'start_date', 'end_date'),
            'components' => $components,
            'evaluation' => $existing ? [
                'id' => $existing->id,
                'status' => $existing->status,
                'notes' => $existing->notes,
                'total_score' => $existing->total_score,
                'scores' => $existing->scores->map(fn ($s) => [
                    'component_id' => $s->component_id,
                    'raw_score' => (string) $s->raw_score,
                    'note' => $s->note,
                ])->values(),
            ] : null,
            'abilities' => [
                'edit' => ! $existing || $existing->status === 'DRAFT',
                'submit' => $existing && $existing->status === 'DRAFT',
            ],
        ]);
    }

    /** Simpan penilaian diri */
    public function store(Request $request, PerformancePeriod $period, SaveSelfAssessmentAction $action)
    {
        $user = $request->user();

        if (! $user->can('evaluation.self_assess')) {
            abort(403, 'Anda tidak memiliki izin untuk penilaian diri.');
        }

        $employee = $user->employee()->first();
        abort_unless($employee !== null, 403, 'Akun Anda belum terhubung dengan data pejuang.');

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'scores' => ['required', 'array'],
            'scores.*.component_id' => ['required', 'integer', 'exists:evaluation_components,id'],
            'scores.*.raw_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scores.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $evaluation = $action->execute($user, $employee, $period, $data);

        return redirect()
            ->route('self-assessment.create', $period)
            ->with('success', 'Penilaian diri berhasil disimpan sebagai draf.');
    }

    /** Submit penilaian diri */
    public function submit(Request $request, EmployeeEvaluation $evaluation)
    {
        $user = $request->user();

        if (! $user->can('evaluation.self_assess')) {
            abort(403, 'Anda tidak memiliki izin untuk penilaian diri.');
        }

        if (! $evaluation->isSelfAssessment()
            || $evaluation->evaluator_id !== $user->id
            || $evaluation->employee?->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke penilaian ini.');
        }

        if ($evaluation->status !== 'DRAFT') {
            return back()->withErrors(['status' => 'Penilaian diri sudah diajukan sebelumnya.']);
        }

        $evaluation->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Penilaian diri berhasil diajukan.');
    }

    /** Lihat hasil self-assessment (untuk atasan) */
    public function show(EmployeeEvaluation $evaluation, Request $request)
    {
        $this->authorize('view', $evaluation);

        if (! $evaluation->isSelfAssessment()) {
            abort(404);
        }

        $evaluation->load(['scores.component', 'employee', 'period']);

        return Inertia::render('SelfAssessment/Show', [
            'evaluation' => [
                'id' => $evaluation->id,
                'status' => $evaluation->status,
                'total_score' => $evaluation->total_score,
                'notes' => $evaluation->notes,
                'submitted_at' => $evaluation->submitted_at?->format('Y-m-d H:i'),
                'employee' => $evaluation->employee->only('id', 'full_name', 'employee_number'),
                'period' => $evaluation->period->only('id', 'name', 'start_date', 'end_date'),
                'scores' => $evaluation->scores->map(fn ($s) => [
                    'component_name' => $s->component->name,
                    'raw_score' => (string) $s->raw_score,
                    'weight' => (string) $s->weight,
                    'weighted_score' => (string) $s->weighted_score,
                    'note' => $s->note,
                ])->values(),
            ],
        ]);
    }
}
