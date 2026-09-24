<?php

namespace Tests\Feature;

use App\Models\EvaluationComponent;
use App\Models\EvaluationCriterion;
use App\Models\PerformancePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EvaluationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_configure_tenure_component_and_audit_it(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post(route('evaluation-components.store'), ['code' => 'MSK', 'name' => 'Masa Kerja', 'default_weight' => 25, 'measurement_type' => 'TENURE', 'scoring_method' => 'RULE', 'is_auto_calculated' => true, 'is_active' => true, 'rules' => [['rule_type' => 'TENURE_MONTHS', 'min_value' => 0, 'max_value' => 11, 'score_value' => 70], ['rule_type' => 'TENURE_MONTHS', 'min_value' => 12, 'max_value' => null, 'score_value' => 100]]])->assertSessionHasNoErrors();
        $component = EvaluationComponent::query()->where('code', 'MSK')->firstOrFail();

        $this->assertCount(2, $component->rules);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $manager->id, 'action' => 'evaluation.component.create']);
    }

    public function test_rejects_overlapping_tenure_rules_and_active_weights_above_one_hundred(): void
    {
        $manager = $this->manager();
        EvaluationComponent::query()->create(['code' => 'A', 'name' => 'A', 'default_weight' => 80, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_active' => true]);
        $payload = ['code' => 'B', 'name' => 'B', 'default_weight' => 30, 'measurement_type' => 'TENURE', 'scoring_method' => 'RULE', 'is_auto_calculated' => true, 'is_active' => true, 'rules' => [['rule_type' => 'TENURE_MONTHS', 'min_value' => 0, 'max_value' => 12, 'score_value' => 50], ['rule_type' => 'TENURE_MONTHS', 'min_value' => 12, 'max_value' => 24, 'score_value' => 80]]];
        $this->actingAs($manager)->from(route('evaluations.configuration'))->post(route('evaluation-components.store'), $payload)->assertSessionHasErrors('rules.1.min_value');
        $payload['rules'][1]['min_value'] = 13;
        $this->actingAs($manager)->from(route('evaluations.configuration'))->post(route('evaluation-components.store'), $payload)->assertSessionHasErrors('default_weight');
    }

    public function test_finalized_period_cannot_be_updated(): void
    {
        $manager = $this->manager();
        $period = PerformancePeriod::query()->create(['name' => 'Semester 1', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'FINALIZED', 'is_active' => true]);
        $this->actingAs($manager)->put(route('evaluation-periods.update', $period), ['name' => 'Diubah', 'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'status' => 'FINALIZED', 'is_active' => true])->assertForbidden();
    }

    public function test_rejects_overlapping_result_criteria(): void
    {
        $manager = $this->manager();
        EvaluationCriterion::query()->create(['name' => 'Baik', 'min_score' => 80, 'max_score' => 100, 'color_semantic' => 'success']);

        $this->actingAs($manager)->from(route('evaluations.configuration'))->post(route('evaluation-criteria.store'), ['name' => 'Cukup', 'min_score' => 70, 'max_score' => 85, 'color_semantic' => 'warning'])->assertSessionHasErrors('min_score');
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('organization.manage', 'web'));

        return $user;
    }
}
