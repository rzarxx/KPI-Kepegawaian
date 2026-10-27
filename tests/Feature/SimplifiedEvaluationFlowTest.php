<?php

namespace Tests\Feature;

use App\Jobs\SendPeriodEvaluationReminder;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\EvaluationTaskService;
use App\Services\ReportQueryService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimplifiedEvaluationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_division_head_can_save_draft_and_complete_in_one_form(): void
    {
        [$head, $employee, $period, $component] = $this->context();
        $payload = $this->payload($period, $component, 'draft');

        $this->actingAs($head)
            ->post(route('evaluations.store', $employee), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluations.index', ['period_id' => $period->id]));
        $evaluation = EmployeeEvaluation::query()->sole();
        $this->assertSame('DRAFT', $evaluation->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.save_draft', 'actor_id' => $head->id]);

        $payload['intent'] = 'complete';
        $this->actingAs($head)
            ->post(route('evaluations.store', $employee), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('FINALIZED', $evaluation->refresh()->status);
        $this->assertNotNull($evaluation->finalized_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.completed', 'actor_id' => $head->id]);
    }

    public function test_only_sub_division_head_in_the_same_scope_can_enter_scores(): void
    {
        [$head, $employee, $period, $component] = $this->context();
        $otherSubDivision = SubDivision::query()->create([
            'division_id' => $employee->currentAssignment->division_id,
            'code' => 'SUB-OTHER',
            'name' => 'Sub Divisi Lain',
        ]);
        $otherHead = User::factory()->create();
        $otherHead->assignRole('Sub Division Head');
        $otherHead->organizationalScopes()->create([
            'branch_id' => $employee->currentAssignment->branch_id,
            'division_id' => $employee->currentAssignment->division_id,
            'sub_division_id' => $otherSubDivision->id,
            'scope_type' => 'sub_division',
            'is_active' => true,
        ]);
        $hr = User::factory()->create();
        $hr->assignRole('HR Admin');

        $this->actingAs($otherHead)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'draft'))->assertForbidden();
        $this->actingAs($hr)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'draft'))->assertForbidden();

        $this->actingAs($head)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'draft'))->assertSessionHasNoErrors();
    }

    public function test_hr_can_reopen_completed_evaluation_with_audited_reason_but_cannot_score(): void
    {
        [$head, $employee, $period, $component] = $this->context();
        $this->actingAs($head)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'complete'));
        $evaluation = EmployeeEvaluation::query()->sole();
        $hr = User::factory()->create();
        $hr->assignRole('HR Admin');
        $hr->organizationalScopes()->create(['scope_type' => 'organization', 'is_active' => true]);

        $this->actingAs($hr)->post(route('evaluations.transition', $evaluation), [
            'status' => 'DRAFT',
            'reason' => 'Nilai perlu dikoreksi berdasarkan bukti terbaru.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('DRAFT', $evaluation->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.reopened', 'actor_id' => $hr->id]);
        $this->actingAs($hr)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'draft'))->assertForbidden();
    }

    public function test_evaluation_task_page_uses_three_simple_statuses(): void
    {
        $this->withoutVite();
        [$head, $employee, $period, $component] = $this->context();

        $this->actingAs($head)->get(route('evaluations.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Evaluations/Index')
            ->where('summary.total', 1)
            ->where('summary.not_started', 1)
            ->where('summary.draft', 0)
            ->where('summary.completed', 0)
            ->where('tasks.0.status', 'NOT_STARTED')
            ->where('tasks.0.can_evaluate', true));

        $this->actingAs($head)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'complete'));

        $this->actingAs($head)->get(route('evaluations.index'))->assertInertia(fn (Assert $page) => $page
            ->where('summary.not_started', 0)
            ->where('summary.completed', 1)
            ->where('summary.completion_percentage', 100)
            ->where('tasks.0.status', 'COMPLETED'));
    }

    public function test_employee_without_sub_division_is_not_eligible_and_is_visible_to_hr_as_needing_placement(): void
    {
        $this->withoutVite();
        [$head, $employee, $period, $component] = $this->context();
        $employee->currentAssignment()->update(['sub_division_id' => null]);
        $hr = User::factory()->create();
        $hr->assignRole('HR Admin');
        $hr->organizationalScopes()->create(['scope_type' => 'organization', 'is_active' => true]);

        $this->actingAs($head)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'draft'))->assertForbidden();
        $this->actingAs($hr)->get(route('evaluations.index'))->assertInertia(fn (Assert $page) => $page
            ->where('needsPlacement', 1)
            ->where('summary.total', 0));
    }

    public function test_retired_mutation_routes_are_not_available(): void
    {
        [$head, $employee, $period] = $this->context();

        $this->actingAs($head)->post('/penilaian-diri/'.$period->id, [])->assertNotFound();
        $this->actingAs($head)->post('/kalibrasi', [])->assertStatus(405);
    }

    public function test_reminder_is_only_sent_to_the_responsible_sub_division_head(): void
    {
        Notification::fake();
        [$head, $employee, $period, $component] = $this->context();
        $hr = User::factory()->create();
        $hr->assignRole('HR Admin');
        $hr->organizationalScopes()->create(['scope_type' => 'organization', 'is_active' => true]);

        (new SendPeriodEvaluationReminder($period->id))->handle(app(EvaluationTaskService::class));

        Notification::assertSentTo($head, SystemNotification::class);
        Notification::assertNotSentTo($hr, SystemNotification::class);

        $this->actingAs($head)->post(route('evaluations.store', $employee), $this->payload($period, $component, 'complete'));
        Notification::fake();
        (new SendPeriodEvaluationReminder($period->id))->handle(app(EvaluationTaskService::class));
        Notification::assertNothingSent();
    }

    public function test_reports_only_load_completed_supervisor_evaluations(): void
    {
        [$head, $employee, $period] = $this->context();
        $assignment = $employee->currentAssignment;
        $selfEvaluation = $employee->evaluations()->create([
            'assignment_id' => $assignment->id,
            'period_id' => $period->id,
            'evaluator_id' => $head->id,
            'evaluation_type' => EmployeeEvaluation::TYPE_SELF,
            'total_score' => 95,
            'status' => 'FINALIZED',
        ]);
        $supervisorEvaluation = $employee->evaluations()->create([
            'assignment_id' => $assignment->id,
            'period_id' => $period->id,
            'evaluator_id' => $head->id,
            'evaluation_type' => EmployeeEvaluation::TYPE_SUPERVISOR,
            'total_score' => 70,
            'status' => 'DRAFT',
        ]);

        $reported = app(ReportQueryService::class)->query($head, [])->firstOrFail();
        $this->assertCount(0, $reported->evaluations);

        $supervisorEvaluation->update(['status' => 'FINALIZED']);
        $reported = app(ReportQueryService::class)->query($head, [])->firstOrFail();
        $this->assertCount(1, $reported->evaluations);
        $this->assertTrue($reported->evaluations->first()->is($supervisorEvaluation));
        $this->assertFalse($reported->evaluations->contains($selfEvaluation));
    }

    /** @return array{User, Employee, PerformancePeriod, EvaluationComponent} */
    private function context(): array
    {
        $this->seed(AccessControlSeeder::class);
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $subDivision = SubDivision::query()->create(['division_id' => $division->id, 'code' => 'DEV', 'name' => 'Pengembangan']);
        $head = User::factory()->create();
        $head->assignRole('Sub Division Head');
        $head->organizationalScopes()->create([
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
            'name' => 'Semester 1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $component = EvaluationComponent::query()->create([
            'code' => 'KERJA',
            'name' => 'Kualitas Kerja',
            'default_weight' => 100,
            'measurement_type' => 'MANUAL',
            'scoring_method' => 'PERCENTAGE',
            'is_active' => true,
        ]);

        return [$head, $employee, $period, $component];
    }

    /** @return array<string, mixed> */
    private function payload(PerformancePeriod $period, EvaluationComponent $component, string $intent): array
    {
        return [
            'period_id' => $period->id,
            'intent' => $intent,
            'scores' => [['component_id' => $component->id, 'contribution_score' => 82, 'note' => 'Baik']],
            'notes' => 'Catatan Kasubdiv',
        ];
    }
}
