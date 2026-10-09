<?php

namespace Tests\Feature;

use App\Actions\ManageCalibrationAction;
use App\Models\Branch;
use App\Models\CalibrationSession;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\Goal;
use App\Models\PerformancePeriod;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoalCalibrationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_policy_denies_updates_outside_the_users_organizational_scope(): void
    {
        [$actor, $allowedBranch, $outsideBranch, $period] = $this->context();
        $goal = Goal::query()->create([
            'period_id' => $period->id,
            'level' => 'BRANCH',
            'goalable_type' => Branch::class,
            'goalable_id' => $outsideBranch->id,
            'title' => 'Target luar cakupan',
            'status' => 'DRAFT',
            'created_by' => $actor->id,
        ]);

        $this->assertFalse($actor->can('view', $goal));
        $this->assertFalse($actor->can('update', $goal));

        $this->actingAs($actor)
            ->put(route('goals.update', $goal), ['title' => 'Tidak boleh berubah'])
            ->assertForbidden();
    }

    public function test_calibration_rejects_an_evaluation_outside_the_session_and_users_scope(): void
    {
        [$actor, $allowedBranch, $outsideBranch, $period] = $this->context();
        $outsideDivision = Division::query()->create(['branch_id' => $outsideBranch->id, 'code' => 'OUT', 'name' => 'Luar Cakupan']);
        $employee = Employee::query()->create([
            'employee_number' => 'EMP-SCOPE-01',
            'full_name' => 'Pejuang Luar Cakupan',
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);
        $assignment = $employee->assignments()->create([
            'branch_id' => $outsideBranch->id,
            'division_id' => $outsideDivision->id,
            'start_date' => now()->subYear(),
            'status' => 'ACTIVE',
        ]);
        $evaluation = EmployeeEvaluation::query()->create([
            'employee_id' => $employee->id,
            'assignment_id' => $assignment->id,
            'period_id' => $period->id,
            'evaluator_id' => $actor->id,
            'evaluation_type' => EmployeeEvaluation::TYPE_SUPERVISOR,
            'total_score' => 80,
            'status' => 'APPROVED',
        ]);
        $session = CalibrationSession::query()->create([
            'period_id' => $period->id,
            'name' => 'Kalibrasi Cabang Diizinkan',
            'scope_type' => 'BRANCH',
            'scope_id' => $allowedBranch->id,
            'created_by' => $actor->id,
            'status' => 'DRAFT',
        ]);

        $this->expectException(AuthorizationException::class);

        app(ManageCalibrationAction::class)->saveAdjustment($actor, $session, [
            'evaluation_id' => $evaluation->id,
            'adjusted_score' => 85,
        ]);
    }

    private function context(): array
    {
        $actor = User::factory()->create();
        foreach (['goal.view', 'goal.update', 'goal.create', 'calibration.view', 'calibration.manage'] as $permission) {
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $allowedBranch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $outsideBranch = Branch::query()->create(['code' => 'BDG', 'name' => 'Bandung']);
        $actor->organizationalScopes()->create(['branch_id' => $allowedBranch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $period = PerformancePeriod::query()->create([
            'name' => 'Periode Scope',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);

        return [$actor, $allowedBranch, $outsideBranch, $period];
    }
}
