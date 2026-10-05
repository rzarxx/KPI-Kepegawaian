<?php

namespace App\Http\Controllers;

use App\Actions\ManageCalibrationAction;
use App\Http\Requests\StoreCalibrationAdjustmentRequest;
use App\Http\Requests\StoreCalibrationSessionRequest;
use App\Models\CalibrationSession;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationCriterion;
use App\Models\PerformancePeriod;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalibrationController extends Controller
{
    /** Halaman daftar sesi kalibrasi */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CalibrationSession::class);

        $sessions = CalibrationSession::query()
            ->with(['period:id,name', 'creator:id,name', 'adjustments'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (CalibrationSession $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'period_name' => $s->period->name,
                'status' => $s->status,
                'scope_type' => $s->scope_type,
                'adjustments_count' => $s->adjustments->count(),
                'creator_name' => $s->creator->name,
                'calibrated_at' => $s->calibrated_at?->format('Y-m-d H:i'),
                'created_at' => $s->created_at->format('Y-m-d H:i'),
            ]);

        $periods = PerformancePeriod::query()
            ->whereIn('status', ['ACTIVE', 'REVIEW', 'FINALIZED'])
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        return Inertia::render('Calibration/Index', [
            'sessions' => $sessions,
            'periods' => $periods,
            'canManage' => $request->user()->can('calibration.manage'),
        ]);
    }

    /** Simpan sesi baru */
    public function store(StoreCalibrationSessionRequest $request, ManageCalibrationAction $action)
    {
        $action->createSession($request->user(), $request->validated());

        return back()->with('success', 'Sesi kalibrasi berhasil dibuat.');
    }

    /** Detail sesi kalibrasi — daftar evaluasi yang bisa dikalibrasi */
    public function show(CalibrationSession $session, Request $request, OrganizationalScopeResolver $scopeResolver)
    {
        $this->authorize('view', $session);

        $session->load(['period', 'adjustments.evaluation.employee', 'adjustments.adjustedBy:id,name']);

        // Ambil evaluasi FINALIZED/APPROVED untuk periode ini, DENGAN SCOPE organisasi
        $user = $request->user();
        $evaluationQuery = EmployeeEvaluation::query()
            ->where('period_id', $session->period_id)
            ->where('evaluation_type', 'SUPERVISOR')
            ->whereIn('status', ['APPROVED', 'FINALIZED'])
            ->whereHas('assignment', function ($q) use ($user, $scopeResolver) {
                $scopeResolver->scopeBranches($user, $q, 'branch_id');
            })
            ->with(['employee:id,full_name,employee_number', 'criterion:id,name,color_semantic'])
            ->orderBy('total_score');
        $evaluations = $evaluationQuery->get()
            ->map(function (EmployeeEvaluation $eval) use ($session) {
                $adjustment = $session->adjustments->firstWhere('evaluation_id', $eval->id);

                return [
                    'id' => $eval->id,
                    'employee_name' => $eval->employee->full_name,
                    'employee_number' => $eval->employee->employee_number,
                    'original_score' => (string) $eval->total_score,
                    'criterion_name' => $eval->criterion?->name,
                    'criterion_color' => $eval->criterion?->color_semantic,
                    'status' => $eval->status,
                    'adjustment' => $adjustment ? [
                        'id' => $adjustment->id,
                        'adjusted_score' => (string) $adjustment->adjusted_score,
                        'reason' => $adjustment->reason,
                        'adjusted_by' => $adjustment->adjustedBy->name,
                        'difference' => $adjustment->scoreDifference(),
                    ] : null,
                ];
            });

        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get(['id', 'name', 'min_score', 'max_score', 'color_semantic']);

        return Inertia::render('Calibration/Show', [
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'description' => $session->description,
                'status' => $session->status,
                'period_name' => $session->period->name,
                'calibrated_at' => $session->calibrated_at?->format('Y-m-d H:i'),
            ],
            'evaluations' => $evaluations,
            'criteria' => $criteria,
            'canManage' => $request->user()->can('calibration.manage'),
            'isEditable' => $session->isEditable(),
        ]);
    }

    /** Simpan penyesuaian kalibrasi */
    public function adjust(CalibrationSession $session, StoreCalibrationAdjustmentRequest $request, ManageCalibrationAction $action)
    {
        $action->saveAdjustment($request->user(), $session, $request->validated());

        return back()->with('success', 'Penyesuaian nilai berhasil disimpan.');
    }

    /** Transisi status */
    public function transition(CalibrationSession $session, Request $request, ManageCalibrationAction $action)
    {
        $data = $request->validate([
            'status' => ['required', 'in:IN_REVIEW,FINALIZED'],
        ]);

        $action->transitionSession($request->user(), $session, $data['status']);

        return back()->with('success', 'Status sesi kalibrasi berhasil diperbarui.');
    }

    /** Terapkan hasil kalibrasi */
    public function apply(CalibrationSession $session, Request $request, ManageCalibrationAction $action)
    {
        $action->applyCalibration($request->user(), $session);

        return back()->with('success', 'Hasil kalibrasi berhasil diterapkan ke semua penilaian terkait.');
    }

    public function destroy(CalibrationSession $session, Request $request, AuditLogger $audit)
    {
        $this->authorize('delete', $session);

        $snapshot = $session->toArray();
        $session->delete();
        $audit->log('calibration.deleted', $request->user(), $session, $snapshot, null);

        return redirect()->route('calibration.index')->with('success', 'Sesi kalibrasi berhasil dihapus.');
    }
}
