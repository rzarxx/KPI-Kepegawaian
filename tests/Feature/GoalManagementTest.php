<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\PerformancePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_page_only_exposes_target_options_inside_the_users_scope(): void
    {
        $actor = $this->goalManager();
        $allowedBranch = $this->branch('CBG-A', 'Cabang Diperbolehkan');
        $outsideBranch = $this->branch('CBG-B', 'Cabang Luar');
        $allowedDivision = $this->division($allowedBranch, 'DIV-A', 'Divisi Diperbolehkan');
        $outsideDivision = $this->division($outsideBranch, 'DIV-B', 'Divisi Luar');
        $allowedEmployee = $this->employee($allowedBranch, $allowedDivision, 'EMP-A', 'Pejuang Diperbolehkan');
        $this->employee($outsideBranch, $outsideDivision, 'EMP-B', 'Pejuang Luar');
        $this->period();

        $actor->organizationalScopes()->create([
            'branch_id' => $allowedBranch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->get(route('goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Index')
                ->where('targetOptions.allowed_levels', ['BRANCH', 'DIVISION', 'INDIVIDUAL'])
                ->has('targetOptions.branches', 1)
                ->where('targetOptions.branches.0.id', $allowedBranch->id)
                ->has('targetOptions.divisions', 1)
                ->where('targetOptions.divisions.0.id', $allowedDivision->id)
                ->has('targetOptions.employees', 1)
                ->where('targetOptions.employees.0.id', $allowedEmployee->id));
    }

    public function test_scoped_user_can_create_a_branch_target_without_sending_a_model_class(): void
    {
        $actor = $this->goalManager();
        $branch = $this->branch('CBG-A', 'Cabang Diperbolehkan');
        $period = $this->period();
        $actor->organizationalScopes()->create([
            'branch_id' => $branch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('goals.store'), [
                'period_id' => $period->id,
                'level' => 'BRANCH',
                'goalable_id' => $branch->id,
                'title' => 'Peningkatan layanan cabang',
                'target_value' => 100,
                'target_unit' => '%',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Target berhasil ditambahkan.');

        $this->assertDatabaseHas('goals', [
            'period_id' => $period->id,
            'level' => 'BRANCH',
            'goalable_type' => Branch::class,
            'goalable_id' => $branch->id,
            'title' => 'Peningkatan layanan cabang',
            'status' => 'DRAFT',
        ]);
        $this->assertTrue(AuditLog::query()->where('action', 'goal.created')->exists());
    }

    public function test_scoped_user_can_create_division_and_individual_targets(): void
    {
        $actor = $this->goalManager();
        $branch = $this->branch('CBG-A', 'Cabang Diperbolehkan');
        $division = $this->division($branch, 'DIV-A', 'Divisi Diperbolehkan');
        $employee = $this->employee($branch, $division, 'EMP-A', 'Pejuang Diperbolehkan');
        $period = $this->period();
        $actor->organizationalScopes()->create([
            'branch_id' => $branch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        foreach ([
            ['level' => 'DIVISION', 'target' => $division, 'title' => 'Target Divisi'],
            ['level' => 'INDIVIDUAL', 'target' => $employee, 'title' => 'Target Individu'],
        ] as $case) {
            $this->actingAs($actor)
                ->post(route('goals.store'), [
                    'period_id' => $period->id,
                    'level' => $case['level'],
                    'goalable_id' => $case['target']->id,
                    'title' => $case['title'],
                ])
                ->assertRedirect()
                ->assertSessionHas('success', 'Target berhasil ditambahkan.');

            $this->assertDatabaseHas('goals', [
                'level' => $case['level'],
                'goalable_type' => $case['target']->getMorphClass(),
                'goalable_id' => $case['target']->id,
                'title' => $case['title'],
            ]);
        }
    }

    public function test_non_company_target_requires_a_target_selection(): void
    {
        $actor = $this->goalManager();
        $branch = $this->branch('CBG-A', 'Cabang Diperbolehkan');
        $period = $this->period();
        $actor->organizationalScopes()->create([
            'branch_id' => $branch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->from(route('goals.index'))
            ->post(route('goals.store'), [
                'period_id' => $period->id,
                'level' => 'DIVISION',
                'title' => 'Target tanpa divisi',
            ])
            ->assertRedirect(route('goals.index'))
            ->assertSessionHasErrors('goalable_id');

        $this->assertDatabaseMissing('goals', ['title' => 'Target tanpa divisi']);
    }

    public function test_direct_request_cannot_create_a_target_outside_scope(): void
    {
        $actor = $this->goalManager();
        $allowedBranch = $this->branch('CBG-A', 'Cabang Diperbolehkan');
        $outsideBranch = $this->branch('CBG-B', 'Cabang Luar');
        $period = $this->period();
        $actor->organizationalScopes()->create([
            'branch_id' => $allowedBranch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('goals.store'), [
                'period_id' => $period->id,
                'level' => 'BRANCH',
                'goalable_id' => $outsideBranch->id,
                'title' => 'Target di luar cakupan',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('goals', ['title' => 'Target di luar cakupan']);
    }

    public function test_parent_target_must_match_the_selected_organizational_target(): void
    {
        $actor = $this->goalManager();
        $actor->organizationalScopes()->create([
            'scope_type' => 'global',
            'is_active' => true,
        ]);
        $branchA = $this->branch('CBG-A', 'Cabang A');
        $branchB = $this->branch('CBG-B', 'Cabang B');
        $divisionB = $this->division($branchB, 'DIV-B', 'Divisi B');
        $period = $this->period();
        $parent = Goal::query()->create([
            'period_id' => $period->id,
            'level' => 'BRANCH',
            'goalable_type' => Branch::class,
            'goalable_id' => $branchA->id,
            'title' => 'Target Cabang A',
            'status' => 'ACTIVE',
            'created_by' => $actor->id,
        ]);

        $this->actingAs($actor)
            ->from(route('goals.index'))
            ->post(route('goals.store'), [
                'parent_id' => $parent->id,
                'period_id' => $period->id,
                'level' => 'DIVISION',
                'goalable_id' => $divisionB->id,
                'title' => 'Target Divisi B',
            ])
            ->assertRedirect(route('goals.index'))
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('goals', ['title' => 'Target Divisi B']);
    }

    private function goalManager(): User
    {
        $user = User::factory()->create();
        foreach (['goal.view', 'goal.create', 'goal.update'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::query()->create(['code' => $code, 'name' => $name, 'is_active' => true]);
    }

    private function division(Branch $branch, string $code, string $name): Division
    {
        return Division::query()->create([
            'branch_id' => $branch->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function employee(Branch $branch, Division $division, string $number, string $name): Employee
    {
        $employee = Employee::query()->create([
            'employee_number' => $number,
            'full_name' => $name,
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);
        $employee->assignments()->create([
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'start_date' => now()->subYear(),
            'status' => 'ACTIVE',
        ]);

        return $employee;
    }

    private function period(): PerformancePeriod
    {
        return PerformancePeriod::query()->create([
            'name' => 'Periode Target',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
    }
}
