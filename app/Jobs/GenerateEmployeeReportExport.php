<?php

namespace App\Jobs;

use App\Exports\EmployeeReportExport;
use App\Models\ReportExport;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use App\Services\ReportQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateEmployeeReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $exportId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(AuditLogger $audit, ReportQueryService $reports): void
    {
        $export = ReportExport::query()->with('requestedBy')->findOrFail($this->exportId);
        $export->update(['status' => 'PROCESSING', 'error_message' => null]);

        try {
            $path = 'reports/karyawan-'.$export->id.'.xlsx';
            $rowCount = $reports->query($export->requestedBy, $export->filters, $export->scope_snapshot)->count();
            Excel::store(
                new EmployeeReportExport($export->requestedBy, $export->filters, $export->scope_snapshot ?? []),
                $path,
                'local',
            );
            $export->update([
                'status' => 'COMPLETED',
                'file_path' => $path,
                'row_count' => $rowCount,
                'completed_at' => now(),
                'expires_at' => now()->addDay(),
            ]);
            $audit->log('report.export.complete', $export->requestedBy, $export, null, [
                'row_count' => $rowCount,
                'expires_at' => $export->expires_at?->toIso8601String(),
            ], [
                'filters' => $export->filters,
                'scope_snapshot' => $export->scope_snapshot,
            ]);
            $export->requestedBy->notify(new SystemNotification(
                'Laporan siap diunduh',
                "Ekspor laporan berisi {$rowCount} baris dan tersedia selama 24 jam.",
                route('reports.download', $export),
                'success',
            ));
        } catch (Throwable $exception) {
            $export->update(['status' => 'FAILED', 'error_message' => 'Ekspor gagal diproses.']);
            $export->requestedBy->notify(new SystemNotification(
                'Ekspor laporan gagal',
                'Laporan belum dapat dibuat. Silakan coba kembali atau hubungi administrator.',
                route('reports.index'),
                'error',
            ));

            throw $exception;
        }
    }
}
