<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\Branch;
use App\Models\Division;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request, OrganizationalScopeResolver $scope, UserAccessService $access)
    {
        $this->authorize('viewAny', User::class);

        $branchIds = $scope->allowedBranchIds($request->user());
        $users = User::query()->with(['roles', 'organizationalScopes.branch', 'organizationalScopes.division', 'organizationalScopes.subDivision'])
            ->when($branchIds !== null, fn ($query) => $query->whereHas('organizationalScopes', fn ($scopeQuery) => $scopeQuery->where('is_active', true)->whereIn('branch_id', $branchIds)))
            ->orderBy('name')->get();

        return Inertia::render('Users/Index', ['users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'is_active' => $u->is_active, 'roles' => $u->getRoleNames(), 'scopes' => $u->organizationalScopes->map(fn ($s) => ['id' => $s->id, 'branch_id' => $s->branch_id, 'division_id' => $s->division_id, 'sub_division_id' => $s->sub_division_id, 'label' => collect([$s->branch?->name, $s->division?->name, $s->subDivision?->name])->filter()->join(' / ') ?: 'Seluruh organisasi'])]), 'roles' => $access->assignableRoleNames($request->user()), 'canManageRoles' => $request->user()->can('user.update'), 'canImpersonate' => $request->user()->hasRole('Super Admin') && $request->user()->can('impersonation.start'), 'branches' => $scope->scopeBranches($request->user(), Branch::query()->where('is_active', true))->get(['id', 'name']), 'divisions' => Division::query()->where('is_active', true)->when($branchIds !== null, fn ($query) => $query->whereIn('branch_id', $branchIds))->get(['id', 'branch_id', 'name']), 'subDivisions' => SubDivision::query()->where('is_active', true)->when($branchIds !== null, fn ($query) => $query->whereHas('division', fn ($divisionQuery) => $divisionQuery->whereIn('branch_id', $branchIds)))->get(['id', 'division_id', 'name'])]);
    }

    public function store(StoreManagedUserRequest $request, UserAccessService $access, AuditLogger $audit)
    {
        $data = $request->validated();
        $user = $access->provision($request->user(), $data);
        $audit->log('user.create', $request->user(), $user, null, ['role' => $data['role']]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(User $user, UpdateManagedUserRequest $request, UserAccessService $access, AuditLogger $audit)
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        $access->update($request->user(), $user, $data);
        $audit->log('user.access.update', $request->user(), $user, null, ['role' => $data['role'], 'scope_count' => count($data['scopes'] ?? [])]);

        return back()->with('success', 'Akses pengguna diperbarui.');
    }

    public function toggle(User $user, Request $request, AuditLogger $audit)
    {
        $this->authorize('disable', $user);
        $user->update(['is_active' => ! $user->is_active]);
        $audit->log('user.'.($user->is_active ? 'enable' : 'disable'), $request->user(), $user);

        return back()->with('success', 'Status pengguna diperbarui.');
    }
}
