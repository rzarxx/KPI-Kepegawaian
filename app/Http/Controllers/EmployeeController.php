<?php

namespace App\Http\Controllers;

use App\Actions\ChangeEmployeeStatusAction;
use App\Actions\CreateEmployeeAction;
use App\Actions\RehireEmployeeAction;
use App\Actions\TransferEmployeeAction;
use App\Actions\UpdateEmployeeAction;
use App\Http\Requests\ChangeEmployeeStatusRequest;
use App\Http\Requests\EmployeeIndexRequest;
use App\Http\Requests\RehireEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\TransferEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\PerformancePeriod;
use App\Models\Position;
use App\Models\SubDivision;
use App\Services\EmployeeQueryService;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(EmployeeIndexRequest $request, EmployeeQueryService $employees): Response
    {
        $this->authorize('viewAny', Employee::class);
        $filters = $request->validated();
        $query = $employees->visibleTo($request->user())->with(['currentAssignment.branch', 'currentAssignment.division', 'currentAssignment.position'])->orderBy('full_name');
        $query->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_number', 'like', "%{$search}%")));
        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('current_status', $status));
        foreach (['branch_id', 'division_id', 'sub_division_id'] as $column) {
            $query->when($filters[$column] ?? null, fn (Builder $employeeQuery, $id) => $employeeQuery->where(function (Builder $nested) use ($column, $id): void {
                $nested->whereHas('currentAssignment', fn (Builder $assignment) => $assignment->where($column, $id))
                    ->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', fn (Builder $assignment) => $assignment->where($column, $id)));
            }));
        }

        return Inertia::render('Employees/Index', [
            'employees' => $query->paginate(15)->withQueryString()->through(fn (Employee $employee) => $this->employeeData($employee)),
            'filters' => $filters,
            'filterOptions' => $this->organizationOptions($request),
            'canCreate' => $request->user()->can('create', Employee::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('Employees/Create', $this->organizationOptions($request));
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): RedirectResponse
    {
        $employee = $action->execute($request->user(), $request->validated());

        return redirect()->route('employees.show', $employee)->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);
        $user = request()->user();
        $relations = [
            'currentAssignment.branch', 'currentAssignment.division', 'currentAssignment.subDivision', 'currentAssignment.position',
            'latestAssignment.branch', 'latestAssignment.division', 'latestAssignment.subDivision', 'latestAssignment.position',
            'assignments.branch', 'assignments.division', 'assignments.subDivision', 'assignments.position',
            'statusHistories.actor',
        ];
        if ($user->can('employee_document.view')) {
            $relations[] = 'documents.uploader';
        }
        if ($user->can('employee_incident.view')) {
            $relations[] = 'incidents';
        }
        if ($user->can('evaluation.view')) {
            $relations[] = 'evaluations.period';
        }
        $employee->load($relations);

        $activities = $employee->assignments->map(fn ($assignment) => [
            'id' => 'assignment-'.$assignment->id,
            'date' => $assignment->start_date->toDateString(),
            'type' => 'ASSIGNMENT',
            'title' => $employee->assignments->min('start_date')->equalTo($assignment->start_date) ? 'Mulai bekerja' : 'Perubahan penempatan',
            'description' => collect([$assignment->branch->name, $assignment->division->name, $assignment->subDivision?->name, $assignment->position?->name])->filter()->join(' - '),
        ])->concat($employee->statusHistories->map(fn ($history) => [
            'id' => 'status-'.$history->id,
            'date' => $history->effective_date->toDateString(),
            'type' => 'STATUS',
            'title' => 'Status: '.$history->new_status->label(),
            'description' => $history->reason,
        ]));
        if ($user->can('employee_incident.view')) {
            $activities = $activities->concat($employee->incidents->map(fn ($incident) => [
                'id' => 'incident-'.$incident->id,
                'date' => $incident->occurred_at->toDateString(),
                'type' => 'INCIDENT',
                'title' => $incident->title ?: 'Catatan masalah',
                'description' => 'Tingkat '.$this->severityLabel($incident->severity).' - '.$this->incidentStatusLabel($incident->status),
            ]));
        }
        if ($user->can('evaluation.view')) {
            $activities = $activities->concat($employee->evaluations->map(fn ($evaluation) => [
                'id' => 'evaluation-'.$evaluation->id,
                'date' => $evaluation->updated_at->toDateString(),
                'type' => 'EVALUATION',
                'title' => 'Penilaian '.$evaluation->period->name,
                'description' => 'Nilai '.number_format((float) $evaluation->total_score, 2, ',', '.').' - '.$this->evaluationStatusLabel($evaluation->status),
            ]));
        }

        $canEvaluate = $employee->currentAssignment !== null
            && $user->can('evaluate', $employee)
            && $user->can('viewAny', PerformancePeriod::class);
        $evaluationPeriods = collect();
        if ($canEvaluate) {
            $evaluationPeriods = PerformancePeriod::query()
                ->where('is_active', true)
                ->whereIn('status', ['DRAFT', 'ACTIVE'])
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'start_date', 'end_date']);
            $evaluationsByPeriod = EmployeeEvaluation::query()
                ->where('employee_id', $employee->id)
                ->where('evaluator_id', $user->id)
                ->whereIn('period_id', $evaluationPeriods->pluck('id'))
                ->pluck('id', 'period_id');
            $evaluationPeriods = $evaluationPeriods->map(fn (PerformancePeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
                'evaluation_id' => $evaluationsByPeriod->get($period->id),
            ]);
        }

        return Inertia::render('Employees/Show', [
            ...$this->organizationOptions(request()),
            'employee' => $this->employeeData($employee),
            'assignments' => $employee->assignments->sortByDesc('start_date')->values()->map(fn ($assignment) => [
                'id' => $assignment->id,
                'start_date' => $assignment->start_date->toDateString(),
                'end_date' => $assignment->end_date?->toDateString(),
                'branch' => $assignment->branch->name,
                'division' => $assignment->division->name,
                'sub_division' => $assignment->subDivision?->name,
                'position' => $assignment->position?->name,
                'reason' => $assignment->reason,
            ]),
            'statusHistories' => $employee->statusHistories->sortByDesc('effective_date')->values()->map(fn ($history) => [
                'id' => $history->id,
                'status' => $history->new_status->value,
                'label' => $history->new_status->label(),
                'effective_date' => $history->effective_date->toDateString(),
                'reason' => $history->reason,
            ]),
            'activities' => $activities->sortByDesc('date')->values(),
            'documents' => $user->can('employee_document.view') ? $employee->documents->sortByDesc('created_at')->values()->map(fn ($document) => [
                'id' => $document->id,
                'category' => $document->category,
                'name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'uploaded_by' => $document->uploader?->name,
                'created_at' => $document->created_at->toIso8601String(),
                'can_delete' => $user->can('delete', $document),
            ]) : [],
            'canViewIncidents' => $user->can('employee_incident.view'),
            'canViewDocuments' => $user->can('employee_document.view'),
            'canUploadDocument' => $user->can('employee_document.create'),
            'canEvaluate' => $canEvaluate,
            'evaluationPeriods' => $evaluationPeriods,
            'canRehire' => $user->can('rehire', $employee),
            'canTransfer' => $user->can('transfer', $employee) && $employee->currentAssignment !== null,
            'canChangeStatus' => $user->can('changeStatus', $employee) && $employee->currentAssignment !== null,
            'canUpdate' => $user->can('update', $employee),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('Employees/Edit', [
            'employee' => $employee->only('id', 'employee_number', 'national_id', 'full_name', 'email', 'phone', 'birth_date', 'gender', 'join_date', 'current_status'),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $action->execute($request->user(), $employee, $request->validated());

        return redirect()->route('employees.show', $employee)->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function transfer(Employee $employee, TransferEmployeeRequest $request, TransferEmployeeAction $action): RedirectResponse
    {
        $this->authorize('transfer', $employee);
        $action->execute($request->user(), $employee, $request->validated());

        return back()->with('success', 'Mutasi karyawan berhasil disimpan.');
    }

    public function changeStatus(Employee $employee, ChangeEmployeeStatusRequest $request, ChangeEmployeeStatusAction $action): RedirectResponse
    {
        $this->authorize('changeStatus', $employee);
        $data = $request->validated();
        $action->execute($request->user(), $employee, $data['status'], $data['effective_date'], $data['reason']);

        return back()->with('success', 'Status karyawan berhasil diperbarui.');
    }

    public function rehire(Employee $employee, RehireEmployeeRequest $request, RehireEmployeeAction $action): RedirectResponse
    {
        $action->execute($request->user(), $employee, $request->validated());

        return back()->with('success', 'Karyawan berhasil diaktifkan kembali dengan penempatan baru.');
    }

    private function employeeData(Employee $employee): array
    {
        $assignment = $employee->currentAssignment ?? $employee->latestAssignment;

        return ['id' => $employee->id, 'employee_number' => $employee->employee_number, 'full_name' => $employee->full_name, 'email' => $employee->email, 'phone' => $employee->phone, 'join_date' => $employee->join_date->toDateString(), 'status' => $employee->current_status->value, 'status_label' => $employee->current_status->label(), 'assignment' => $assignment ? ['branch' => $assignment->branch?->name, 'division' => $assignment->division?->name, 'sub_division' => $assignment->subDivision?->name, 'position' => $assignment->position?->name] : null];
    }

    private function organizationOptions(Request $request): array
    {
        $branchQuery = app(OrganizationalScopeResolver::class)->scopeBranches($request->user(), Branch::query()->where('is_active', true));
        $branches = $branchQuery->orderBy('name')->get(['id', 'name']);
        $branchIds = $branches->pluck('id');

        return ['branches' => $branches, 'divisions' => Division::query()->where('is_active', true)->where(fn ($query) => $query->whereNull('branch_id')->orWhereIn('branch_id', $branchIds))->orderBy('name')->get(['id', 'branch_id', 'name']), 'subDivisions' => SubDivision::query()->where('is_active', true)->whereHas('division', fn ($query) => $query->whereNull('branch_id')->orWhereIn('branch_id', $branchIds))->orderBy('name')->get(['id', 'division_id', 'name']), 'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])];
    }

    private function severityLabel(string $severity): string
    {
        return ['LOW' => 'Rendah', 'MEDIUM' => 'Sedang', 'HIGH' => 'Tinggi', 'CRITICAL' => 'Kritis'][$severity] ?? $severity;
    }

    private function incidentStatusLabel(string $status): string
    {
        return ['OPEN' => 'Terbuka', 'UNDER_REVIEW' => 'Ditinjau', 'RESOLVED' => 'Selesai', 'CLOSED' => 'Ditutup'][$status] ?? $status;
    }

    private function evaluationStatusLabel(string $status): string
    {
        return ['DRAFT' => 'Draf', 'SUBMITTED' => 'Diajukan', 'APPROVED' => 'Disetujui', 'FINALIZED' => 'Final', 'CLOSED' => 'Ditutup'][$status] ?? $status;
    }
}
