<?php

namespace App\Jobs;

use App\Models\PerformancePeriod;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Otomatis menutup periode penilaian yang sudah melewati end_date
 * dan memiliki auto_close = true.
 */
class AutoClosePeriod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $periodId) {}

    public function handle(AuditLogger $audit): void
    {
        $period = PerformancePeriod::find($this->periodId);
        if (! $period || ! in_array($period->status, ['ACTIVE', 'REVIEW'], true)) {
            return;
        }
        if (! $period->end_date->isPast()) {
            return;
        }

        $before = $period->toArray();

        // Transisi ke REVIEW lalu FINALIZED jika masih ACTIVE
        if ($period->status === 'ACTIVE') {
            $period->update(['status' => 'REVIEW', 'is_active' => true]);
            $audit->log('evaluation.period.auto_review', null, $period, $before, $period->fresh()->toArray());
            $before = $period->fresh()->toArray();
        }

        // Tutup periode
        $period->update(['status' => 'FINALIZED', 'is_active' => false]);
        $audit->log('evaluation.period.auto_close', null, $period->fresh(), $before, $period->fresh()->toArray());

        // Notifikasi ke HR Admin dan Super Admin
        $admins = \App\Models\User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'HR Admin', 'HR Manager']))
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new SystemNotification(
                "Periode \"{$period->name}\" otomatis ditutup",
                "Periode penilaian \"{$period->name}\" telah melewati tanggal {$period->end_date->format('d M Y')} dan otomatis diubah ke status Final.",
                route('evaluations.configuration'),
                'warning',
            ));
        }
    }
}
