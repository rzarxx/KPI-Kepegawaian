<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EvaluationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_follows_draft_submit_approve_finalize_and_close_workflow(): void
    {
        [$actor, $employee, $period, $component] = $this->context();
        $payload = ['scores' => [['component_id' => $component->id, 'raw_score' => 77, 'note' => 'Baik']], 'notes' => 'Draf'];
        $this->actingAs($actor)->post(route('evaluations.store', [$employee, $period]), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_evaluations', ['employee_id' => $employee->id, 'status' => 'DRAFT']);
        $evaluation = $employee->evaluations()->sole();

        $payload['finalize'] = true;
        $this->actingAs($actor)->post(route('evaluations.store', [$employee, $period]), $payload)->assertSessionHasErrors('finalize');

        foreach (['SUBMITTED', 'APPROVED', 'FINALIZED', 'CLOSED'] as $status) {
            $this->actingAs($actor)->post(route('evaluations.transition', $evaluation), ['status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $evaluation->refresh()->status);
        }
        $this->assertNotNull($evaluation->submitted_at);
        $this->assertNotNull($evaluation->approved_at);
        $this->assertNotNull($evaluation->finalized_at);
        $this->assertNotNull($evaluation->closed_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'action' => 'evaluation.closed']);
    }

    private function context(): array
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('evaluation.view', 'web'),
            Permission::findOrCreate('evaluation.create', 'web'),
            Permission::findOrCreate('evaluation.update', 'web'),
            Permission::findOrCreate('evaluation.submit', 'web'),
            Permission::findOrCreate('evaluation.approve', 'web'),
            Permission::findOrCreate('evaluation.finalize', 'web'),
        );
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-01', 'full_name' => 'Rina', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);
        $period = PerformancePeriod::query()->create(['name' => 'Semester 1', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'DRAFT', 'is_active' => true]);
        $component = EvaluationComponent::query()->create(['code' => 'KERJA', 'name' => 'Kualitas Kerja', 'default_weight' => 100, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_active' => true]);

        return [$actor, $employee, $period, $component];
    }
}
