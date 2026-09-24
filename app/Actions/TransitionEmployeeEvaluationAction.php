<?php

namespace App\Actions;

use App\Models\EmployeeEvaluation;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionEmployeeEvaluationAction
{
    private const LABELS = [
        'SUBMITTED' => 'Diajukan',
        'APPROVED' => 'Disetujui',
        'FINALIZED' => 'Final',
        'CLOSED' => 'Ditutup',
    ];

    private const FLOW = [
        'SUBMITTED' => ['from' => 'DRAFT', 'ability' => 'submit', 'time' => 'submitted_at'],
        'APPROVED' => ['from' => 'SUBMITTED', 'ability' => 'approve', 'time' => 'approved_at'],
        'FINALIZED' => ['from' => 'APPROVED', 'ability' => 'finalize', 'time' => 'finalized_at'],
        'CLOSED' => ['from' => 'FINALIZED', 'ability' => 'close', 'time' => 'closed_at'],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $actor, EmployeeEvaluation $evaluation, string $status): EmployeeEvaluation
    {
        $transition = self::FLOW[$status] ?? null;
        if (! $transition) {
            throw ValidationException::withMessages(['status' => 'Status penilaian tidak valid.']);
        }
        if (! $actor->can($transition['ability'], $evaluation)) {
            throw new AuthorizationException;
        }
        if ($evaluation->status !== $transition['from']) {
            throw ValidationException::withMessages(['status' => 'Perubahan status tidak sesuai urutan workflow penilaian.']);
        }

        return DB::transaction(function () use ($actor, $evaluation, $status, $transition): EmployeeEvaluation {
            $before = $evaluation->toArray();
            $evaluation->update(['status' => $status, $transition['time'] => now()]);
            $this->audit->log('evaluation.'.strtolower($status), $actor, $evaluation, $before, $evaluation->fresh()->toArray());

            if ($evaluation->evaluator_id !== $actor->id) {
                $evaluation->evaluator->notify(new SystemNotification(
                    'Status penilaian diperbarui',
                    "Penilaian {$evaluation->employee->full_name} kini berstatus ".self::LABELS[$status].'.',
                    route('evaluations.show', $evaluation),
                ));
            }

            return $evaluation->fresh();
        });
    }
}
