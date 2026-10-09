<?php

namespace App\Actions;

use App\Models\CalibrationAdjustment;
use App\Models\CalibrationSession;
use App\Models\Division;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationCriterion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageCalibrationAction
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrganizationalScopeResolver $scopeResolver,
    ) {}

    /** Buat sesi kalibrasi baru */
    public function createSession(User $actor, array $data): CalibrationSession
    {
        if (! $actor->can('calibration.manage')) {
            throw new AuthorizationException;
        }

        $this->assertScopeIsAllowed($actor, $data['scope_type'] ?? 'ALL', $data['scope_id'] ?? null);

        $session = CalibrationSession::create([
            'period_id' => $data['period_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scope_type' => $data['scope_type'] ?? 'ALL',
            'scope_id' => $data['scope_id'] ?? null,
            'created_by' => $actor->id,
            'status' => 'DRAFT',
        ]);

        $this->audit->log('calibration.created', $actor, $session, null, $session->toArray());

        return $session;
    }

    /** Tambah/update penyesuaian nilai */
    public function saveAdjustment(User $actor, CalibrationSession $session, array $data): CalibrationAdjustment
    {
        if (! $actor->can('update', $session)) {
            throw new AuthorizationException;
        }

        if (! $session->isEditable()) {
            throw ValidationException::withMessages(['session' => 'Sesi kalibrasi sudah tidak dapat diubah.']);
        }

        $evaluation = EmployeeEvaluation::findOrFail($data['evaluation_id']);

        if (! $evaluation->assignment
            || ! $this->scopeResolver->allowsAssignment($actor, $evaluation->assignment)
            || ! $this->scopeResolver->calibrationIncludesAssignment($session, $evaluation->assignment)) {
            throw new AuthorizationException;
        }

        // Pastikan evaluasi sudah FINALIZED atau APPROVED
        if (! in_array($evaluation->status, ['APPROVED', 'FINALIZED'], true)) {
            throw ValidationException::withMessages(['evaluation_id' => 'Hanya penilaian yang sudah disetujui/final yang dapat dikalibrasi.']);
        }

        // Tentukan kriteria baru berdasarkan adjusted score
        $newCriteria = EvaluationCriterion::query()
            ->where('min_score', '<=', $data['adjusted_score'])
            ->where('max_score', '>=', $data['adjusted_score'])
            ->first();

        return DB::transaction(function () use ($actor, $session, $evaluation, $data, $newCriteria): CalibrationAdjustment {
            $adjustment = CalibrationAdjustment::updateOrCreate(
                ['session_id' => $session->id, 'evaluation_id' => $evaluation->id],
                [
                    'original_score' => $evaluation->total_score,
                    'adjusted_score' => $data['adjusted_score'],
                    'adjusted_criteria_id' => $newCriteria?->id,
                    'reason' => $data['reason'] ?? null,
                    'adjusted_by' => $actor->id,
                ]
            );

            $this->audit->log('calibration.adjustment_saved', $actor, $adjustment, null, [
                'employee_name' => $evaluation->employee->full_name,
                'original_score' => $evaluation->total_score,
                'adjusted_score' => $data['adjusted_score'],
            ]);

            return $adjustment;
        });
    }

    /** Transisi status sesi */
    public function transitionSession(User $actor, CalibrationSession $session, string $newStatus): CalibrationSession
    {
        $allowedTransitions = [
            'DRAFT' => 'IN_REVIEW',
            'IN_REVIEW' => 'FINALIZED',
        ];

        $expectedStatus = $allowedTransitions[$session->status] ?? null;
        if ($expectedStatus !== $newStatus) {
            throw ValidationException::withMessages(['status' => 'Perubahan status tidak valid.']);
        }

        if ($newStatus === 'IN_REVIEW' && ! $actor->can('update', $session)) {
            throw new AuthorizationException;
        }
        if ($newStatus === 'FINALIZED' && ! $actor->can('finalize', $session)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $session, $newStatus): CalibrationSession {
            $before = $session->toArray();
            $session->update([
                'status' => $newStatus,
                'calibrated_at' => $newStatus === 'FINALIZED' ? now() : $session->calibrated_at,
            ]);

            $this->audit->log('calibration.status_changed', $actor, $session, $before, $session->fresh()->toArray());

            return $session->fresh();
        });
    }

    /** Terapkan hasil kalibrasi ke evaluasi asli */
    public function applyCalibration(User $actor, CalibrationSession $session): CalibrationSession
    {
        if (! $actor->can('apply', $session)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $session): CalibrationSession {
            $adjustments = $session->adjustments()->with('evaluation')->get();

            foreach ($adjustments as $adjustment) {
                $evaluation = $adjustment->evaluation;
                if (! $evaluation->assignment
                    || ! $this->scopeResolver->allowsAssignment($actor, $evaluation->assignment)
                    || ! $this->scopeResolver->calibrationIncludesAssignment($session, $evaluation->assignment)) {
                    throw new AuthorizationException;
                }
                $beforeEval = $evaluation->toArray();

                $evaluation->update([
                    'total_score' => $adjustment->adjusted_score,
                    'criteria_id' => $adjustment->adjusted_criteria_id,
                ]);

                $this->audit->log('calibration.applied_to_evaluation', $actor, $evaluation, $beforeEval, [
                    'calibration_session' => $session->name,
                    'original_score' => $adjustment->original_score,
                    'adjusted_score' => $adjustment->adjusted_score,
                ]);
            }

            $before = $session->toArray();
            $session->update(['status' => 'APPLIED']);

            $this->audit->log('calibration.applied', $actor, $session, $before, $session->fresh()->toArray());

            return $session->fresh();
        });
    }

    private function assertScopeIsAllowed(User $actor, string $scopeType, ?int $scopeId): void
    {
        if ($scopeType === 'ALL') {
            if ($this->scopeResolver->allowedBranchIds($actor) !== null) {
                throw new AuthorizationException;
            }

            return;
        }

        if ($scopeType === 'BRANCH') {
            if (! $this->scopeResolver->allows($actor, $scopeId)) {
                throw new AuthorizationException;
            }

            return;
        }

        $division = Division::query()->findOrFail($scopeId);
        if (! $this->scopeResolver->allows($actor, $division->branch_id, $division->id)) {
            throw new AuthorizationException;
        }
    }
}
