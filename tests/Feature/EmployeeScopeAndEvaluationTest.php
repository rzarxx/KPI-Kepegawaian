<?php

namespace Tests\Feature;

use App\Actions\SaveEmployeeEvaluationAction;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EvaluationComponent;
use App\Models\EvaluationCriterion;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeScopeAndEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_outside_organizational_scope_returns_forbidden(): void
    {
        $viewer = User::factory()->create();
        Permission::findOrCreate('employee.view', 'web');
        $viewer->givePermissionTo('employee.view');
        $allowedBranch = Branch::query()->create(['code' => 'CBG-A', 'name' => 'Cabang A']);
        $otherBranch = Branch::query()->create(['code' => 'CBG-B', 'name' => 'Cabang B']);
        $viewer->organizationalScopes()->create(['branch_id' => $allowedBranch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $allowed = $this->employeeAt($allowedBranch);
        $outside = $this->employeeAt($otherBranch);

        $this->actingAs($viewer)->get(route('employees.show', $allowed))->assertOk();
        $this->actingAs($viewer)->get(route('employees.show', $outside))->assertForbidden();
        $this->actingAs($viewer)->get(route('employees.index'))->assertSee($allowed->full_name)->assertDontSee($outside->full_name);
    }

    public function test_evaluation_total_and_criteria_are_calculated_on_server(): void
    {
        $this->seed(AccessControlSeeder::class);
        $actor = User::factory()->create();
        $branch = Branch::query()->create(['code' => 'CBG-A', 'name' => 'Cabang A']);
        $employee = $this->employeeAt($branch);
        $actor->assignRole('Sub Division Head');
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'division_id' => $employee->currentAssignment->division_id, 'sub_division_id' => $employee->currentAssignment->sub_division_id, 'scope_type' => 'sub_division', 'is_active' => true]);
        $period = PerformancePeriod::query()->create(['name' => 'Semester 1', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'ACTIVE', 'is_active' => true]);
        EvaluationCriterion::query()->create(['name' => 'Baik', 'min_score' => 80, 'max_score' => 100, 'color_semantic' => 'success', 'sort_order' => 1]);
        $component = EvaluationComponent::query()->create(['code' => 'KOM-1', 'name' => 'Kualitas Kerja', 'default_weight' => 100, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'is_active' => true, 'sort_order' => 1]);

        $evaluation = app(SaveEmployeeEvaluationAction::class)->execute($actor, $employee, $period, ['intent' => 'draft', 'scores' => [['component_id' => $component->id, 'contribution_score' => 88, 'note' => 'Konsisten']], 'notes' => 'Catatan']);

        $this->assertSame('DRAFT', $evaluation->status);
        $this->assertSame('88.00', $evaluation->total_score);
        $this->assertSame(1, $evaluation->criteria_id);
        $this->assertDatabaseHas('employee_evaluation_scores', ['evaluation_id' => $evaluation->id, 'component_id' => $component->id, 'raw_score' => 88]);
    }

    public function test_employee_detail_exposes_active_period_for_authorized_evaluator(): void
    {
        $this->seed(AccessControlSeeder::class);
        $actor = User::factory()->create();
        $branch = Branch::query()->create(['code' => 'CBG-A', 'name' => 'Cabang A']);
        $employee = $this->employeeAt($branch);
        $actor->assignRole('Sub Division Head');
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'division_id' => $employee->currentAssignment->division_id, 'sub_division_id' => $employee->currentAssignment->sub_division_id, 'scope_type' => 'sub_division', 'is_active' => true]);
        $period = PerformancePeriod::query()->create([
            'name' => 'Semester Aktif',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Show')
                ->where('canEvaluate', true)
                ->where('evaluationPeriods.0.id', $period->id)
                ->where('evaluationPeriods.0.evaluation_id', null));
    }

    private function employeeAt(Branch $branch): Employee
    {
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'DIV-'.$branch->id, 'name' => 'Divisi '.$branch->name]);
        $subDivision = SubDivision::query()->create(['division_id' => $division->id, 'code' => 'SUB-'.$branch->id, 'name' => 'Sub Divisi '.$branch->name]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-'.$branch->id.'-'.Employee::query()->count(), 'full_name' => 'Pejuang '.$branch->name, 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'sub_division_id' => $subDivision->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        return $employee;
    }
}
