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

class AdHocEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_can_be_saved_without_period(): void
    {
        [$actor, $employee, $component] = $this->context();
        $payload = [
            'period_id' => '',
            'scores' => [['component_id' => $component->id, 'raw_score' => 85, 'note' => 'Bagus']],
            'notes' => 'Penilaian langsung',
        ];

        $this->actingAs($actor)
            ->post(route('evaluations.store', $employee), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employee_evaluations', [
            'employee_id' => $employee->id,
            'evaluator_id' => $actor->id,
            'period_id' => null,
            'status' => 'DRAFT',
        ]);
    }

    public function test_evaluation_can_still_be_saved_with_period(): void
    {
        [$actor, $employee, $component] = $this->context();
        $period = PerformancePeriod::query()->create([
            'name' => 'Semester 1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $payload = [
            'period_id' => $period->id,
            'scores' => [['component_id' => $component->id, 'raw_score' => 90, 'note' => 'Sangat baik']],
            'notes' => 'Penilaian dengan periode',
        ];

        $this->actingAs($actor)
            ->post(route('evaluations.store', $employee), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employee_evaluations', [
            'employee_id' => $employee->id,
            'evaluator_id' => $actor->id,
            'period_id' => $period->id,
            'status' => 'DRAFT',
        ]);
    }

    public function test_ad_hoc_evaluation_follows_full_workflow(): void
    {
        [$actor, $employee, $component] = $this->context();
        $payload = [
            'period_id' => '',
            'scores' => [['component_id' => $component->id, 'raw_score' => 77, 'note' => 'Baik']],
            'notes' => 'Draf',
        ];

        $this->actingAs($actor)
            ->post(route('evaluations.store', $employee), $payload)
            ->assertSessionHasNoErrors();

        $evaluation = $employee->evaluations()->sole();
        $this->assertNull($evaluation->period_id);

        foreach (['SUBMITTED', 'APPROVED', 'FINALIZED', 'CLOSED'] as $status) {
            $this->actingAs($actor)
                ->post(route('evaluations.transition', $evaluation), ['status' => $status])
                ->assertSessionHasNoErrors();
            $this->assertSame($status, $evaluation->refresh()->status);
        }
    }

    public function test_create_page_renders_without_period(): void
    {
        [$actor, $employee] = $this->context();

        $this->actingAs($actor)
            ->get(route('evaluations.create', $employee))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Evaluations/Form')
                ->where('period', null)
                ->has('periods')
                ->has('components')
            );
    }

    public function test_create_page_renders_with_period_query(): void
    {
        [$actor, $employee] = $this->context();
        $period = PerformancePeriod::query()->create([
            'name' => 'Q1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->addMonths(3),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->get(route('evaluations.create', $employee).'?period_id='.$period->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Evaluations/Form')
                ->where('period.id', $period->id)
            );
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
        $employee = Employee::query()->create(['employee_number' => 'EMP-AH-01', 'full_name' => 'Budi', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);
        $component = EvaluationComponent::query()->create(['code' => 'KERJA_AH', 'name' => 'Kualitas Kerja', 'default_weight' => 100, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_active' => true]);

        return [$actor, $employee, $component];
    }
}
