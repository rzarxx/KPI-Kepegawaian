<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\SubDivision;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class LocalRoleAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder akun role hanya boleh dijalankan pada environment local atau testing.');
        }

        $this->call(AccessControlSeeder::class);

        $password = (string) (env('LOCAL_ROLE_SEED_PASSWORD') ?: 'KpiLokal2026!');

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('LOCAL_ROLE_SEED_PASSWORD minimal 12 karakter.');
        }

        $accounts = DB::transaction(function () use ($password): array {
            $jakarta = Branch::query()->updateOrCreate(
                ['code' => 'UAT-JKT'],
                ['name' => 'Cabang UAT Jakarta', 'is_active' => true],
            );
            $surabaya = Branch::query()->updateOrCreate(
                ['code' => 'UAT-SBY'],
                ['name' => 'Cabang UAT Surabaya', 'is_active' => true],
            );
            $jakartaHr = Division::query()->updateOrCreate(
                ['code' => 'UAT-JKT-HR'],
                ['branch_id' => $jakarta->id, 'name' => 'SDM Jakarta', 'is_active' => true],
            );
            $jakartaOperations = Division::query()->updateOrCreate(
                ['code' => 'UAT-JKT-OPS'],
                ['branch_id' => $jakarta->id, 'name' => 'Operasional Jakarta', 'is_active' => true],
            );
            $surabayaOperations = Division::query()->updateOrCreate(
                ['code' => 'UAT-SBY-OPS'],
                ['branch_id' => $surabaya->id, 'name' => 'Operasional Surabaya', 'is_active' => true],
            );
            $jakartaAdmin = SubDivision::query()->updateOrCreate(
                ['code' => 'UAT-JKT-HR-ADM'],
                ['division_id' => $jakartaHr->id, 'name' => 'Administrasi SDM', 'is_active' => true],
            );
            $jakartaRecruitment = SubDivision::query()->updateOrCreate(
                ['code' => 'UAT-JKT-HR-REC'],
                ['division_id' => $jakartaHr->id, 'name' => 'Rekrutmen', 'is_active' => true],
            );
            $jakartaField = SubDivision::query()->updateOrCreate(
                ['code' => 'UAT-JKT-OPS-FLD'],
                ['division_id' => $jakartaOperations->id, 'name' => 'Lapangan Jakarta', 'is_active' => true],
            );
            $surabayaField = SubDivision::query()->updateOrCreate(
                ['code' => 'UAT-SBY-OPS-FLD'],
                ['division_id' => $surabayaOperations->id, 'name' => 'Lapangan Surabaya', 'is_active' => true],
            );

            $this->seedEmployees([
                ['UAT-RBAC-001', 'Ayu UAT Jakarta', $jakarta->id, $jakartaHr->id, $jakartaAdmin->id],
                ['UAT-RBAC-002', 'Bagas UAT Rekrutmen', $jakarta->id, $jakartaHr->id, $jakartaRecruitment->id],
                ['UAT-RBAC-003', 'Citra UAT Operasional Jakarta', $jakarta->id, $jakartaOperations->id, $jakartaField->id],
                ['UAT-RBAC-004', 'Dimas UAT Surabaya', $surabaya->id, $surabayaOperations->id, $surabayaField->id],
            ]);

            User::query()->where('email', 'employee@kpi.local.test')->update(['is_active' => false]);

            return Role::query()
                ->where('guard_name', 'web')
                ->where('name', '!=', 'Employee')
                ->orderBy('name')
                ->get()
                ->map(function (Role $role) use ($jakarta, $jakartaHr, $jakartaAdmin, $password): array {
                    $email = Str::slug($role->name, '.').'@kpi.local.test';
                    $user = User::query()->updateOrCreate(
                        ['email' => $email],
                        [
                            'name' => 'Akun Lokal - '.$role->name,
                            'password' => $password,
                            'is_active' => true,
                        ],
                    );
                    $user->forceFill(['email_verified_at' => now()])->save();
                    $user->syncRoles([$role]);

                    $user->organizationalScopes()->delete();
                    $scope = $this->scopeFor($role->name, $jakarta->id, $jakartaHr->id, $jakartaAdmin->id);

                    if ($scope !== null) {
                        $user->organizationalScopes()->create($scope);
                    }

                    return [$role->name, $user->email, $scope['scope_type'] ?? 'global'];
                })
                ->all();
        });

        $this->command?->newLine();
        $this->command?->info('Akun lokal untuk role yang memiliki portal berhasil dibuat atau diperbarui.');
        $this->command?->table(['Role', 'Email', 'Scope'], $accounts);
        $this->command?->warn('Password seluruh akun: '.$password);
        $this->command?->warn('Akun ini hanya untuk pengembangan lokal dan tidak boleh digunakan di production.');
        $this->command?->warn('Role Employee tidak dibuat sebagai akun login karena belum ada relasi aman users ke employees.');
    }

    /**
     * @return array<string, int|string|bool|null>|null
     */
    private function scopeFor(string $role, int $branchId, int $divisionId, int $subDivisionId): ?array
    {
        return match ($role) {
            'Super Admin' => null,
            'Branch Head' => [
                'branch_id' => $branchId,
                'division_id' => null,
                'sub_division_id' => null,
                'scope_type' => 'branch',
                'is_active' => true,
            ],
            'Division Head' => [
                'branch_id' => $branchId,
                'division_id' => $divisionId,
                'sub_division_id' => null,
                'scope_type' => 'division',
                'is_active' => true,
            ],
            'Sub Division Head' => [
                'branch_id' => $branchId,
                'division_id' => $divisionId,
                'sub_division_id' => $subDivisionId,
                'scope_type' => 'sub_division',
                'is_active' => true,
            ],
            default => [
                'branch_id' => null,
                'division_id' => null,
                'sub_division_id' => null,
                'scope_type' => 'organization',
                'is_active' => true,
            ],
        };
    }

    /**
     * @param  array<int, array{string, string, int, int, int}>  $rows
     */
    private function seedEmployees(array $rows): void
    {
        foreach ($rows as [$number, $name, $branchId, $divisionId, $subDivisionId]) {
            $employee = Employee::query()->updateOrCreate(
                ['employee_number' => $number],
                [
                    'full_name' => $name,
                    'email' => strtolower($number).'@kpi.local.test',
                    'join_date' => '2026-01-01',
                    'current_status' => 'ACTIVE',
                ],
            );

            EmployeeAssignment::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'start_date' => '2026-01-01'],
                [
                    'branch_id' => $branchId,
                    'division_id' => $divisionId,
                    'sub_division_id' => $subDivisionId,
                    'position_id' => null,
                    'end_date' => null,
                    'status' => 'ACTIVE',
                    'reason' => 'Data UAT RBAC lokal',
                ],
            );
        }
    }
}
