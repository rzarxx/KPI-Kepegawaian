<?php

namespace App\Http\Middleware;

use App\Services\OrganizationalScopeResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $scopeResolver = app(OrganizationalScopeResolver::class);
        $activeScopes = $user ? $scopeResolver->effectiveScopes($user)->loadMissing([
            'branch:id,name',
            'division:id,name',
            'subDivision:id,name',
        ]) : null;
        $scopeLabels = $activeScopes?->map(fn ($scope): string => collect([
            $scope->branch?->name,
            $scope->division?->name,
            $scope->subDivision?->name,
        ])->filter()->join(' / ') ?: 'Seluruh organisasi')->values()->all() ?? [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'access' => $user ? [
                    'role' => $user->getRoleNames()->first() ?? 'Tanpa role',
                    'scopeLabels' => $user->hasRole('Super Admin') || $scopeLabels === []
                        ? ['Seluruh organisasi']
                        : $scopeLabels,
                ] : null,
                'abilities' => $user ? [
                    'dashboardView' => $user->can('employee.view'),
                    'employeeView' => $user->can('employee.view'),
                    'incidentView' => $user->can('employee_incident.view'),
                    'evaluationView' => $user->can('evaluation.view'),
                    'reportView' => $user->can('report.view'),
                    'auditView' => $user->can('audit.view') && $scopeResolver->allowedBranchIds($user) === null,
                    'organizationView' => $user->can('organization.view'),
                    'userView' => $user->can('user.view'),
                ] : [],
                'unreadNotifications' => $user?->unreadNotifications()->count() ?? 0,
            ],
            'impersonation' => $request->session()->has('impersonation.original_user_id') ? [
                'active' => true,
                'target_name' => $request->user()?->name,
            ] : null,
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
            ],
        ];
    }
}
