<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request, OrganizationalScopeResolver $scopes): Response
    {
        abort_unless($request->user()->can('audit.view'), 403);
        abort_unless($scopes->allowedBranchIds($request->user()) === null, 403);

        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', '%'.$action.'%'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'actor' => $log->actor?->name ?? 'Sistem',
                'action' => $log->action,
                'subject' => $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : null,
                'ipAddress' => $log->ip_address,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
