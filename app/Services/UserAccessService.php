<?php

namespace App\Services;

use App\Models\Division;
use App\Models\SubDivision;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    /** @return list<string> */
    public function assignableRoleNames(User $actor): array
    {
        if ($actor->hasRole('Super Admin')) {
            return ['Super Admin', 'HR Admin', 'HR Manager', 'Branch Head', 'Division Head', 'Sub Division Head', 'Auditor'];
        }

        if ($actor->hasRole('HR Admin')) {
            return ['HR Manager', 'Branch Head', 'Division Head', 'Sub Division Head', 'Auditor'];
        }

        return [];
    }

    public function provision(User $actor, array $data): User
    {
        $this->assertRoleAssignable($actor, $data['role']);
        $this->validateRoleScopeContract($data['role'], $data['scopes'] ?? []);
        $this->validateScopes($actor, $data['scopes'] ?? []);

        return DB::transaction(function () use ($data): User {
            $user = User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'is_active' => true]);
            $user->assignRole($data['role']);
            $this->replaceScopes($user, $data['scopes'] ?? []);

            return $user;
        });
    }

    public function update(User $actor, User $user, array $data): void
    {
        $this->assertRoleAssignable($actor, $data['role']);
        $this->validateRoleScopeContract($data['role'], $data['scopes'] ?? []);
        $this->validateScopes($actor, $data['scopes'] ?? []);

        DB::transaction(function () use ($user, $data): void {
            $user->update(['name' => $data['name']]);
            $user->syncRoles([$data['role']]);
            $this->replaceScopes($user, $data['scopes'] ?? []);
        });
    }

    private function assertRoleAssignable(User $actor, string $role): void
    {
        if (! in_array($role, $this->assignableRoleNames($actor), true)) {
            throw new AuthorizationException;
        }
    }

    private function validateRoleScopeContract(string $role, array $scopes): void
    {
        if ($role === 'Super Admin') {
            if ($scopes !== []) {
                throw ValidationException::withMessages(['scopes' => 'Super Admin harus menggunakan cakupan seluruh organisasi tanpa scope tambahan.']);
            }

            return;
        }

        if ($scopes === []) {
            throw ValidationException::withMessages(['scopes' => 'Role ini wajib memiliki cakupan organisasi aktif.']);
        }

        if (! in_array($role, ['Branch Head', 'Division Head', 'Sub Division Head'], true)) {
            return;
        }

        if (count($scopes) !== 1) {
            throw ValidationException::withMessages(['scopes' => 'Role kepala organisasi harus memiliki tepat satu cakupan.']);
        }

        $scope = $scopes[0];
        $branchId = $scope['branch_id'] ?? null;
        $divisionId = $scope['division_id'] ?? null;
        $subDivisionId = $scope['sub_division_id'] ?? null;
        $valid = match ($role) {
            'Branch Head' => $branchId !== null && $divisionId === null && $subDivisionId === null,
            'Division Head' => $branchId !== null && $divisionId !== null && $subDivisionId === null,
            'Sub Division Head' => $branchId !== null && $divisionId !== null && $subDivisionId !== null,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'scopes' => [
                    'Branch Head wajib dikunci ke satu cabang; Division Head ke satu divisi; dan Sub Division Head ke satu sub divisi.',
                ],
            ]);
        }
    }

    private function validateScopes(User $actor, array $scopes): void
    {
        $resolver = app(OrganizationalScopeResolver::class);
        foreach ($scopes as $scope) {
            $branchId = $scope['branch_id'] ?? null;
            $divisionId = $scope['division_id'] ?? null;
            $subDivisionId = $scope['sub_division_id'] ?? null;
            if ($divisionId !== null) {
                $division = Division::query()->findOrFail($divisionId);
                if ($branchId !== null && (int) $branchId !== $division->branch_id) {
                    throw ValidationException::withMessages(['scopes' => 'Divisi tidak berada pada cabang yang dipilih.']);
                }
                $branchId = $division->branch_id;
            }
            if ($subDivisionId !== null) {
                $subDivision = SubDivision::query()->findOrFail($subDivisionId);
                if ($divisionId !== null && (int) $divisionId !== $subDivision->division_id) {
                    throw ValidationException::withMessages(['scopes' => 'Sub divisi tidak berada pada divisi yang dipilih.']);
                }
                $division = $subDivision->division;
                $divisionId = $division->id;
                $branchId = $division->branch_id;
            }
            if (! $resolver->allows($actor, $branchId, $divisionId, $subDivisionId)) {
                throw new AuthorizationException;
            }
        }
    }

    private function replaceScopes(User $user, array $scopes): void
    {
        $user->organizationalScopes()->delete();
        foreach ($scopes as $scope) {
            $user->organizationalScopes()->create([
                'branch_id' => $scope['branch_id'] ?? null,
                'division_id' => $scope['division_id'] ?? null,
                'sub_division_id' => $scope['sub_division_id'] ?? null,
                'scope_type' => ! empty($scope['sub_division_id']) ? 'sub_division' : (! empty($scope['division_id']) ? 'division' : (! empty($scope['branch_id']) ? 'branch' : 'organization')),
                'is_active' => true,
            ]);
        }
    }
}
