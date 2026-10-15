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
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $actor, EmployeeEvaluation $evaluation, string $status, string $reason): EmployeeEvaluation
    {
        if ($status !== 'DRAFT') {
            throw ValidationException::withMessages(['status' => 'Status penilaian tidak valid.']);
        }
        if (! $actor->can('reopen', $evaluation)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $evaluation, $reason): EmployeeEvaluation {
            $before = $evaluation->toArray();
            $evaluation->update(['status' => 'DRAFT', 'finalized_at' => null, 'closed_at' => null]);
            $this->audit->log('evaluation.reopened', $actor, $evaluation, $before, $evaluation->fresh()->toArray(), ['reason' => $reason]);
            $evaluation->evaluator?->notify(new SystemNotification(
                'Penilaian dibuka kembali',
                "Penilaian {$evaluation->employee->full_name} perlu diperbaiki. Alasan: {$reason}",
                route('evaluations.show', $evaluation),
            ));

            return $evaluation->fresh();
        });
    }
}
