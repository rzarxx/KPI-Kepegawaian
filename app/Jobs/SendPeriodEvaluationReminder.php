<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\PerformancePeriod;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Kirim notifikasi pengingat ke seluruh evaluator yang BELUM mengisi penilaian
 * untuk periode yang mendekati atau telah melewati batas waktu.
 */
class SendPeriodEvaluationReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $periodId, public readonly string $reason = 'reminder') {}

    public function handle(OrganizationalScopeResolver $scopeResolver): void
    {
        $period = PerformancePeriod::find($this->periodId);
        if (! $period || in_array($period->status, ['FINALIZED', 'CLOSED'], true)) {
            return;
        }

        $isOverdue = $this->reason === 'overdue';
        $title = $isOverdue
            ? "Periode penilaian \"{$period->name}\" telah berakhir"
            : "Pengingat: periode penilaian \"{$period->name}\" segera berakhir";
        $daysLeft = now()->diffInDays($period->end_date, false);
        $message = $isOverdue
            ? "Periode penilaian \"{$period->name}\" (s/d {$period->end_date->format('d M Y')}) sudah berakhir. Segera selesaikan penilaian yang belum diisi."
            : "Periode penilaian \"{$period->name}\" berakhir dalam {$daysLeft} hari ({$period->end_date->format('d M Y')}). Pastikan semua penilaian sudah diisi sebelum batas waktu.";

        // Cari semua user yang bisa membuat penilaian (evaluator)
        $evaluators = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Division Head', 'Sub Division Head', 'Branch Head', 'HR Admin', 'HR Manager']))
            ->get();

        foreach ($evaluators as $evaluator) {
            // Cek apakah evaluator ini punya pejuang yang belum dinilai
            $pendingCount = $this->countPendingEvaluations($evaluator, $period, $scopeResolver);
            if ($pendingCount === 0) {
                continue;
            }

            $evaluator->notify(new SystemNotification(
                $title,
                "{$message} ({$pendingCount} pejuang belum dinilai).",
                route('evaluations.configuration'),
                $isOverdue ? 'warning' : 'info',
            ));
        }

        // Update reminder_sent_at agar tidak dikirim ulang
        $period->update(['reminder_sent_at' => now()]);
    }

    private function countPendingEvaluations(User $evaluator, PerformancePeriod $period, OrganizationalScopeResolver $scopeResolver): int
    {
        // Pejuang yang sudah dinilai oleh evaluator ini di periode ini
        $alreadyEvaluatedIds = EmployeeEvaluation::query()
            ->where('evaluator_id', $evaluator->id)
            ->where('period_id', $period->id)
            ->pluck('employee_id');

        // Pejuang aktif dalam scope evaluator yang belum dinilai
        $query = Employee::query()
            ->where('current_status', 'ACTIVE')
            ->whereNotIn('id', $alreadyEvaluatedIds)
            ->whereHas('currentAssignment');

        $allowedBranchIds = $scopeResolver->allowedBranchIds($evaluator);
        if ($allowedBranchIds !== null) {
            $query->whereHas('currentAssignment', fn ($q) => $q->whereIn('branch_id', $allowedBranchIds));
        }

        return $query->count();
    }
}
