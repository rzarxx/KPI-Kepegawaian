<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManageOrganizationUnitRequest;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index(Request $request, OrganizationalScopeResolver $scope)
    {
        $this->authorize('viewAny', Branch::class);
        $branches = $scope->scopeBranches($request->user(), Branch::query())->orderBy('name')->get();
        $branchIds = $branches->pluck('id');

        return Inertia::render('Organization/Index', [
            'branches' => $branches, 'divisions' => Division::query()->where(fn ($q) => $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds))->orderBy('name')->get(),
            'subDivisions' => SubDivision::query()->whereHas('division', fn ($q) => $q->where(fn ($d) => $d->whereNull('branch_id')->orWhereIn('branch_id', $branchIds)))->orderBy('name')->get(),
            'positions' => Position::query()->orderBy('name')->get(), 'canManage' => $request->user()->can('organization.manage'),
        ]);
    }

    public function store(ManageOrganizationUnitRequest $request, string $type, AuditLogger $audit)
    {
        $this->ensureTargetScope($request->user(), $type, $request->validated());
        $unit = $this->model($type)->newQuery()->create($this->payload($request->validated(), $type));
        $audit->log("organization.{$type}.create", $request->user(), $unit, null, $unit->toArray());

        return back()->with('success', 'Data organisasi berhasil ditambahkan.');
    }

    public function update(ManageOrganizationUnitRequest $request, string $type, int $unit, AuditLogger $audit)
    {
        $model = $this->model($type)->newQuery()->findOrFail($unit);
        $this->authorize('update', $model);
        $this->ensureTargetScope($request->user(), $type, $request->validated());
        $before = $model->toArray();
        $model->update($this->payload($request->validated(), $type));
        $audit->log("organization.{$type}.update", $request->user(), $model, $before, $model->fresh()->toArray());

        return back()->with('success', 'Data organisasi berhasil diperbarui.');
    }

    private function model(string $type): Model
    {
        return match ($type) {
            'cabang' => new Branch, 'divisi' => new Division, 'sub-divisi' => new SubDivision, 'jabatan' => new Position, default => abort(404)
        };
    }

    private function payload(array $data, string $type): array
    {
        return match ($type) {
            'cabang' => collect($data)->only(['code', 'name', 'is_active'])->all(), 'divisi' => collect($data)->only(['branch_id', 'code', 'name', 'is_active'])->all(), 'sub-divisi' => collect($data)->only(['division_id', 'code', 'name', 'is_active'])->all(), 'jabatan' => collect($data)->only(['code', 'name', 'level', 'is_active'])->all()
        };
    }

    private function ensureTargetScope(User $user, string $type, array $data): void
    {
        $resolver = app(OrganizationalScopeResolver::class);
        if ($type === 'cabang' && $resolver->allowedBranchIds($user) !== null) {
            abort(403);
        }
        if ($type === 'divisi' && ! $resolver->allows($user, (int) $data['branch_id'])) {
            abort(403);
        }
        if ($type === 'sub-divisi') {
            $division = Division::query()->findOrFail($data['division_id']);
            if (! $resolver->allows($user, $division->branch_id, $division->id)) {
                abort(403);
            }
        }
    }
}
