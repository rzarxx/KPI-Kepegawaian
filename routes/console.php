<?php

use App\Models\ReportExport;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

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
