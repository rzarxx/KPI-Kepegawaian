<?php

use App\Jobs\AutoClosePeriod;
use App\Jobs\SendPeriodEvaluationReminder;
use App\Models\PerformancePeriod;
use App\Models\ReportExport;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

// ─── Bersihkan laporan yang sudah kedaluwarsa ───────────────────────────────
Schedule::call(function (): void {
    ReportExport::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->whereNotIn('status', ['QUEUED', 'PROCESSING'])
        ->chunkById(100, function ($exports): void {
            foreach ($exports as $export) {
                if ($export->file_path) {
                    Storage::disk('local')->delete($export->file_path);
                }

                $export->update(['status' => 'EXPIRED', 'file_path' => null]);
            }
        });
})->dailyAt('02:30')->name('reports:cleanup-expired')->withoutOverlapping();

// ─── Cek periode penilaian: pengingat & auto-close ─────────────────────────
Schedule::call(function (): void {
    $periods = PerformancePeriod::query()
        ->whereIn('status', ['ACTIVE', 'REVIEW'])
        ->where('is_active', true)
        ->get();

    foreach ($periods as $period) {
        // Auto-close: periode sudah lewat end_date dan diaktifkan
        if ($period->auto_close && $period->isOverdue()) {
            AutoClosePeriod::dispatch($period->id);
            continue;
        }

        // Pengingat: dalam window notify_before_days dan belum dikirim hari ini
        if ($period->isInReminderWindow()) {
            $alreadySentToday = $period->reminder_sent_at
                && $period->reminder_sent_at->isToday();

            if (! $alreadySentToday) {
                $reason = $period->isOverdue() ? 'overdue' : 'reminder';
                SendPeriodEvaluationReminder::dispatch($period->id, $reason);
            }
        }
    }
})->dailyAt('08:00')->name('evaluations:check-periods')->withoutOverlapping();

