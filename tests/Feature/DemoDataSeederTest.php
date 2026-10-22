<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EmployeeIncident;
use App\Models\PerformancePeriod;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DomainDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AccessControlSeeder::class, DomainDefaultsSeeder::class]);
    }

    public function test_it_builds_a_branch_with_past_and_active_evaluations(): void
    {
        $this->seed(DemoDataSeeder::class);

        $past = PerformancePeriod::query()->where('status', 'CLOSED')->sole();
        $active = PerformancePeriod::query()->where('status', 'ACTIVE')->where('is_active', true)->sole();

        $this->assertFalse($past->is_active);
        $this->assertSame(9, EmployeeEvaluation::query()->where('period_id', $past->id)->where('status', 'FINALIZED')->count());
        $this->assertSame(3, EmployeeEvaluation::query()->where('period_id', $active->id)->where('status', 'FINALIZED')->count());
        $this->assertSame(2, EmployeeEvaluation::query()->where('period_id', $active->id)->where('status', 'DRAFT')->count());
        $this->assertSame(11, Employee::query()->where('employee_number', 'like', 'DEMO-%')->count());
        $this->assertSame(4, EmployeeIncident::query()->count());

        // Siti: masa kerja > 24 bulan dan nilai manual tinggi -> Istimewa.
        $siti = Employee::query()->where('employee_number', 'DEMO-001')->sole();
        $evaluation = EmployeeEvaluation::query()->where('employee_id', $siti->id)->where('period_id', $past->id)->with('criterion')->sole();
        $this->assertSame('Istimewa', $evaluation->criterion?->name);
    }

    public function test_demo_sub_division_head_only_sees_own_sub_division_tasks(): void
    {
        $this->seed(DemoDataSeeder::class);

        $head = User::query()->where('email', 'kasubdiv.akuntansi@demo.albahjah.or.id')->sole();
        $fieldEmployee = Employee::query()->where('employee_number', 'DEMO-007')->sole();
        $ownEmployee = Employee::query()->where('employee_number', 'DEMO-004')->sole();

        $this->assertTrue($head->can('evaluate', $ownEmployee));
        $this->assertFalse($head->can('evaluate', $fieldEmployee));
    }

    public function test_it_refuses_to_run_twice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->expectException(RuntimeException::class);
        app(DemoDataSeeder::class)->run();
    }

    public function test_it_requires_confirmation_in_production(): void
    {
        $originalEnvironment = app()->environment();

        try {
            app()->detectEnvironment(fn () => 'production');
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('DEMO_SEED_CONFIRM');
            app(DemoDataSeeder::class)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
