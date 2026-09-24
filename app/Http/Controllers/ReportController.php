<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Jobs\GenerateEmployeeReportExport;
use App\Models\Branch;
use App\Models\Division;
use App\Models\EvaluationCriterion;
use App\Models\ReportExport;
use App\Models\SubDivision;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use App\Services\OrganizationalScopeSnapshot;
use App\Services\ReportQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request, ReportQueryService $reports, OrganizationalScopeResolver $scopes): Response
    {
        $filters = $request->validated();
        $rows = $reports->query($request->user(), $filters)
            ->paginate(20)
            ->withQueryString()
            ->through(function ($employee): array {
                $assignment = $employee->currentAssignment ?? $employee->latestAssignment;
                $evaluation = $employee->evaluations->first();

                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'number' => $employee->employee_number,
                    'status' => $employee->current_status->label(),
                    'branch' => $assignment?->branch?->name,
                    'division' => $assignment?->division?->name,
                    'score' => $evaluation?->total_score,
                    'criteria' => $evaluation?->criterion?->name,
                ];
            });
        $branches = $scopes->scopeBranches($request->user(), Branch::query()->where('is_active', true))
            ->orderBy('name')->get(['id', 'name']);
        $divisions = Division::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhereIn('branch_id', $branches->pluck('id')))
            ->orderBy('name')->get(['id', 'branch_id', 'name']);

        return Inertia::render('Reports/Index', [
            'rows' => $rows,
            'filters' => $filters,
            'filterOptions' => [
                'branches' => $branches,
                'divisions' => $divisions,
                'subDivisions' => SubDivision::query()->where('is_active', true)->whereIn('division_id', $divisions->pluck('id'))->orderBy('name')->get(['id', 'division_id', 'name']),
                'criteria' => EvaluationCriterion::query()->orderBy('sort_order')->get(['id', 'name']),
            ],
            'exports' => ReportExport::query()->where('requested_by', $request->user()->id)->latest()->take(10)->get(),
            'canExport' => $request->user()->can('report.export'),
        ]);
    }

    public function export(
        ReportFilterRequest $request,
        OrganizationalScopeSnapshot $scopeSnapshot,
        AuditLogger $audit,
    ): RedirectResponse {
        $filters = $request->validated();
        $snapshot = $scopeSnapshot->capture($request->user());
        $export = ReportExport::query()->create([
            'requested_by' => $request->user()->id,
            'filters' => $filters,
            'scope_snapshot' => $snapshot,
            'status' => 'QUEUED',
        ]);
        $audit->log('report.export.requested', $request->user(), $export, null, null, [
            'filters' => $filters,
            'scope_snapshot' => $snapshot,
        ]);
        GenerateEmployeeReportExport::dispatch($export->id);

        return back()->with('success', 'Ekspor laporan sedang diproses. Anda akan menerima notifikasi saat file siap.');
    }

    public function download(ReportExport $export): StreamedResponse
    {
        $this->authorize('download', $export);
        abort_unless($export->file_path && Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download($export->file_path, 'laporan-karyawan-'.$export->id.'.xlsx');
    }
}
