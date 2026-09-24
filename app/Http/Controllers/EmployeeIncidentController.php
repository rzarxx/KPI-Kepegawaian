<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveEmployeeIncidentRequest;
use App\Http\Requests\StoreEmployeeIncidentRequest;
use App\Http\Requests\TransitionEmployeeIncidentRequest;
use App\Http\Requests\UpdateEmployeeIncidentRequest;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\IncidentCategory;
use App\Models\User;
use App\Notifications\EmployeeIncidentResolvedNotification;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use App\Services\EmployeeQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmployeeIncidentController extends Controller
{
    public function index(Employee $employee)
    {
        $this->authorize('viewAny', EmployeeIncident::class);
        $this->authorize('view', $employee);
        $categories = IncidentCategory::query()->where('is_active', true)->orderBy('sort_order')->get(['code', 'name']);
        $categoryNames = $categories->pluck('name', 'code');

        return Inertia::render('Employees/Incidents', [
            'employee' => $employee->only('id', 'full_name', 'employee_number'),
            'incidents' => $employee->incidents()->with(['reporter:id,name', 'resolver:id,name'])->latest('occurred_at')->get()
                ->map(fn (EmployeeIncident $incident) => [
                    'id' => $incident->id,
                    'title' => $incident->title ?: 'Catatan '.$categoryNames->get($incident->category, $incident->category),
                    'category' => $incident->category,
                    'category_name' => $categoryNames->get($incident->category, $incident->category),
                    'severity' => $incident->severity,
                    'description' => $incident->description,
                    'occurred_at' => $incident->occurred_at->toDateString(),
                    'status' => $incident->status,
                    'resolution' => $incident->resolution,
                    'reporter' => $incident->reporter?->name,
                    'resolver' => $incident->resolver?->name,
                ]),
            'categories' => $categories,
            'canCreate' => request()->user()->can('employee_incident.create'),
            'canUpdate' => request()->user()->can('employee_incident.update'),
            'canResolve' => request()->user()->can('employee_incident.resolve'),
        ]);
    }

    public function overview(EmployeeQueryService $employees)
    {
        $this->authorize('viewAny', EmployeeIncident::class);
        $visible = $employees->visibleTo(request()->user())->select('employees.id');
        $incidents = EmployeeIncident::query()->whereIn('employee_id', $visible)
            ->with(['employee.currentAssignment.division', 'reporter:id,name'])
            ->latest('occurred_at')->paginate(20)->through(fn ($incident) => [
                'id' => $incident->id, 'employee_id' => $incident->employee_id,
                'employee' => $incident->employee->full_name, 'division' => $incident->employee->currentAssignment?->division?->name,
                'title' => $incident->title, 'category' => $incident->category, 'severity' => $incident->severity,
                'status' => $incident->status, 'occurred_at' => $incident->occurred_at->toDateString(),
                'reporter' => $incident->reporter?->name, 'resolution' => $incident->resolution,
            ]);

        return Inertia::render('Employees/ProblemIndex', ['incidents' => $incidents]);
    }

    public function store(Employee $employee, StoreEmployeeIncidentRequest $request, AuditLogger $audit)
    {
        $this->authorize('view', $employee);
        $this->authorize('create', EmployeeIncident::class);
        $incident = DB::transaction(function () use ($employee, $request, $audit) {
            $incident = $employee->incidents()->create([...$request->validated(), 'employee_assignment_id' => $employee->currentAssignment?->id, 'reported_by' => $request->user()->id]);
            $audit->log('employee.incident.create', $request->user(), $incident, null, $incident->toArray());

            return $incident;
        });
        if (in_array($incident->severity, ['HIGH', 'CRITICAL'], true)) {
            User::permission('employee_incident.resolve')->where('is_active', true)->get()
                ->filter(fn (User $user) => $user->id !== $request->user()->id && $user->can('view', $employee))
                ->each->notify(new SystemNotification('Karyawan memerlukan perhatian', 'Terdapat catatan masalah prioritas yang perlu ditinjau.', route('employees.incidents.index', $employee), 'warning'));
        }

        return back()->with('success', 'Catatan masalah berhasil ditambahkan.');
    }

    public function update(UpdateEmployeeIncidentRequest $request, EmployeeIncident $incident, AuditLogger $audit)
    {
        $before = $incident->toArray();
        $incident->update($request->validated());
        $audit->log('employee.incident.update', $request->user(), $incident, $before, $incident->fresh()->toArray());

        return back()->with('success', 'Catatan masalah berhasil diperbarui.');
    }

    public function resolve(EmployeeIncident $incident, ResolveEmployeeIncidentRequest $request, AuditLogger $audit)
    {
        $before = $incident->toArray();
        $incident->update(['status' => 'RESOLVED', 'resolution' => $request->validated('resolution'), 'resolved_at' => now(), 'resolved_by' => $request->user()->id]);
        $audit->log('employee.incident.resolve', $request->user(), $incident, $before, $incident->fresh()->toArray());
        if ($incident->reported_by !== $request->user()->id) {
            $incident->reporter?->notify(new EmployeeIncidentResolvedNotification($incident));
        }

        return back()->with('success', 'Catatan masalah berhasil diselesaikan.');
    }

    public function transition(TransitionEmployeeIncidentRequest $request, EmployeeIncident $incident, AuditLogger $audit)
    {
        $this->authorize('transition', $incident);
        $target = $request->validated('status');
        $allowed = ['OPEN' => ['UNDER_REVIEW', 'RESOLVED'], 'UNDER_REVIEW' => ['RESOLVED'], 'RESOLVED' => ['CLOSED']];
        if (! in_array($target, $allowed[$incident->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status catatan masalah tidak sesuai urutan.']);
        }
        $before = $incident->toArray();
        $data = ['status' => $target];
        if ($target === 'RESOLVED') {
            $data += ['resolution' => $request->validated('resolution'), 'resolved_at' => now(), 'resolved_by' => $request->user()->id];
        }
        $incident->update($data);
        $audit->log('employee.incident.'.strtolower($target), $request->user(), $incident, $before, $incident->fresh()->toArray());
        if ($target === 'RESOLVED' && $incident->reported_by !== $request->user()->id) {
            $incident->reporter?->notify(new EmployeeIncidentResolvedNotification($incident));
        }

        return back()->with('success', 'Status catatan masalah berhasil diperbarui.');
    }
}
