<?php

namespace Tests\Feature;

use App\Models\PerformancePeriod;
use App\Models\User;
use Database\Seeders\E2ESeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class E2ESeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_an_active_period_and_local_role_accounts(): void
    {
        $this->seed(E2ESeeder::class);

        $this->assertDatabaseHas('performance_periods', [
            'name' => 'Periode E2E Aktif',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $this->assertTrue(User::query()->where('email', 'sub.division.head@kpi.local.test')->exists());
        $this->assertSame(1, PerformancePeriod::query()->where('is_active', true)->count());
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $originalEnvironment = app()->environment();

        try {
            app()->detectEnvironment(fn () => 'production');
            $this->expectException(RuntimeException::class);
            app(E2ESeeder::class)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
