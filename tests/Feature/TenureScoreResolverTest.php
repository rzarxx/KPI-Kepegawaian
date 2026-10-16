<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EvaluationComponent;
use App\Services\TenureScoreResolver;
use Database\Seeders\DomainDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenureScoreResolverTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, float}> */
    public static function partialMonthTenures(): array
    {
        return [
            'antara 2 dan 3 bulan' => ['2026-08-16', '2026-10-31', 40.0],
            'antara 5 dan 6 bulan' => ['2026-05-16', '2026-10-31', 60.0],
            'antara 11 dan 12 bulan' => ['2025-11-16', '2026-10-31', 75.0],
            'antara 24 dan 25 bulan' => ['2024-10-16', '2026-10-31', 85.0],
            'lebih dari 25 bulan' => ['2023-01-10', '2026-10-31', 100.0],
        ];
    }

    #[DataProvider('partialMonthTenures')]
    public function test_partial_months_use_completed_months_of_tenure(string $joinDate, string $referenceDate, float $expected): void
    {
        $this->seed(DomainDefaultsSeeder::class);
        $component = EvaluationComponent::query()->where('code', 'MASA_KERJA')->sole();
        $employee = Employee::query()->create(['employee_number' => 'TNR-001', 'full_name' => 'Uji Masa Kerja', 'join_date' => $joinDate, 'current_status' => 'ACTIVE']);

        $this->assertSame($expected, app(TenureScoreResolver::class)->resolve($employee, $component, $referenceDate));
        $this->assertSame($joinDate, $employee->join_date->toDateString());
    }
}
