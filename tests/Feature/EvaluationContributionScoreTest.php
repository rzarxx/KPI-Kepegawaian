<?php

namespace Tests\Feature;

use App\Actions\SaveEmployeeEvaluationAction;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EvaluationContributionScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_contribution_is_stored_directly_while_automatic_score_stays_normalized(): void
    {
        [$actor, $employee, $period] = $this->context();
        $manual = $this->evaluationComponent('ABSENSI', 'Absensi', 20, false, 10);
        $automatic = $this->evaluationComponent('MASA_KERJA', 'Masa Kerja', 80, true, 20);
        $automatic->rules()->create([
            'rule_type' => 'TENURE_MONTHS',
            'min_value' => 0,
            'max_value' => null,
            'score_value' => 75,
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('evaluations.store', $employee), [
                'period_id' => $period->id,
                'intent' => 'draft',
                'scores' => [
                    ['component_id' => $manual->id, 'contribution_score' => 18, 'note' => 'Baik'],
                    ['component_id' => $automatic->id],
                ],
            ])
            ->assertSessionHasNoErrors();

        $evaluation = EmployeeEvaluation::query()->sole();
        $this->assertSame('78.00', $evaluation->total_score);
        $this->assertDatabaseHas('employee_evaluation_scores', [
            'evaluation_id' => $evaluation->id,
            'component_id' => $manual->id,
            'raw_score' => 90,
            'weight' => 20,
            'weighted_score' => 18,
            'source_type' => 'manual',
        ]);
        $this->assertDatabaseHas('employee_evaluation_scores', [
            'evaluation_id' => $evaluation->id,
            'component_id' => $automatic->id,
            'raw_score' => 75,
            'weight' => 80,
            'weighted_score' => 60,
            'source_type' => 'automatic',
        ]);

        $this->actingAs($actor)
            ->get(route('evaluations.show', $evaluation))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Evaluations/Form')
                ->where('evaluation.scores.0.component_id', $manual->id)
                ->where('evaluation.scores.0.weighted_score', '18.0000')
                ->missing('evaluation.scores.0.raw_score'));
    }

    public function test_request_rejects_contribution_above_component_weight(): void
    {
        [$actor, $employee, $period] = $this->context();
        $component = $this->evaluationComponent('ABSENSI', 'Absensi', 100, false, 10);

        $this->actingAs($actor)
            ->post(route('evaluations.store', $employee), [
                'period_id' => $period->id,
                'intent' => 'draft',
                'scores' => [[
                    'component_id' => $component->id,
                    'contribution_score' => 100.01,
                ]],
            ])
            ->assertSessionHasErrors('scores.0.contribution_score');

        $this->assertDatabaseCount('employee_evaluations', 0);
    }

    public function test_request_rejects_negative_non_numeric_duplicate_inactive_and_legacy_scores(): void
    {
        [$actor, $employee, $period] = $this->context();
        $component = $this->evaluationComponent('ABSENSI', 'Absensi', 100, false, 10);
        $base = ['period_id' => $period->id, 'intent' => 'draft'];

        $this->actingAs($actor)->post(route('evaluations.store', $employee), $base + [
            'scores' => [['component_id' => $component->id, 'contribution_score' => -0.01]],
        ])->assertSessionHasErrors('scores.0.contribution_score');

        $this->actingAs($actor)->post(route('evaluations.store', $employee), $base + [
            'scores' => [['component_id' => $component->id, 'contribution_score' => 'bukan-angka']],
        ])->assertSessionHasErrors('scores.0.contribution_score');

        $this->actingAs($actor)->post(route('evaluations.store', $employee), $base + [
            'scores' => [
                ['component_id' => $component->id, 'contribution_score' => 80],
                ['component_id' => $component->id, 'contribution_score' => 80],
            ],
        ])->assertSessionHasErrors(['scores.0.component_id', 'scores.1.component_id']);

        $this->actingAs($actor)->post(route('evaluations.store', $employee), $base + [
            'scores' => [['component_id' => $component->id, 'raw_score' => 80]],
        ])->assertSessionHasErrors(['scores.0.contribution_score', 'scores.0.raw_score']);

        $component->update(['is_active' => false]);
        $this->actingAs($actor)->post(route('evaluations.store', $employee), $base + [
            'scores' => [['component_id' => $component->id, 'contribution_score' => 80]],
        ])->assertSessionHasErrors('scores.0.component_id');

        $this->assertDatabaseCount('employee_evaluations', 0);
    }

    public function test_action_rejects_out_of_range_contribution_when_called_without_http_request(): void
    {
        [$actor, $employee, $period] = $this->context();
        $component = $this->evaluationComponent('ABSENSI', 'Absensi', 100, false, 10);

        $this->expectException(ValidationException::class);

        app(SaveEmployeeEvaluationAction::class)->execute($actor, $employee, $period, [
            'intent' => 'draft',
            'scores' => [[
                'component_id' => $component->id,
                'contribution_score' => 100.01,
            ]],
        ]);
    }

    /** @return array{User, Employee, PerformancePeriod} */
    private function context(): array
    {
        $this->seed(AccessControlSeeder::class);
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $subDivision = SubDivision::query()->create(['division_id' => $division->id, 'code' => 'DEV', 'name' => 'Pengembangan']);
        $actor = User::factory()->create();
        $actor->assignRole('Sub Division Head');
        $actor->organizationalScopes()->create([
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'sub_division_id' => $subDivision->id,
            'scope_type' => 'sub_division',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'employee_number' => 'EMP-01',
            'full_name' => 'Rina',
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);
        $employee->assignments()->create([
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'sub_division_id' => $subDivision->id,
            'start_date' => now()->subYear(),
            'status' => 'ACTIVE',
        ]);
        $period = PerformancePeriod::query()->create([
            'name' => 'Semester Aktif',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);

        return [$actor, $employee, $period];
    }

    private function evaluationComponent(string $code, string $name, float $weight, bool $automatic, int $sortOrder): EvaluationComponent
    {
        return EvaluationComponent::query()->create([
            'code' => $code,
            'name' => $name,
            'default_weight' => $weight,
            'measurement_type' => $automatic ? 'TENURE' : 'MANUAL',
            'scoring_method' => $automatic ? 'RULE' : 'PERCENTAGE',
            'is_auto_calculated' => $automatic,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
