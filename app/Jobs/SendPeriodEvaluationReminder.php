<?php

namespace App\Jobs;

use App\Models\PerformancePeriod;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\EvaluationTaskService;
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

    public function handle(EvaluationTaskService $tasks): void
    {
        $period = PerformancePeriod::find($this->periodId);
        if (! $period || $period->status !== 'ACTIVE' || ! $period->is_active) {
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

        // Hanya Kasubdiv yang menjadi operator penilaian pada alur sederhana.
        $evaluators = User::query()
            ->where('is_active', true)
            ->role('Sub Division Head')
            ->get();

        foreach ($evaluators as $evaluator) {
            // Cek apakah evaluator ini punya pejuang yang belum dinilai
            $pendingCount = collect($tasks->gather($evaluator, $period->id)['tasks'])
                ->whereIn('status', ['NOT_STARTED', 'DRAFT'])
                ->where('can_evaluate', true)
                ->count();
            if ($pendingCount === 0) {
                continue;
            }

            $evaluator->notify(new SystemNotification(
                $title,
                "{$message} ({$pendingCount} pejuang belum dinilai).",
                route('evaluations.index', ['period_id' => $period->id]),
                $isOverdue ? 'warning' : 'info',
            ));
        }

        // Update reminder_sent_at agar tidak dikirim ulang
        $period->update(['reminder_sent_at' => now()]);
    }
}
