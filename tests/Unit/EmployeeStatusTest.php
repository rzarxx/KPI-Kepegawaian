<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmployeeStatusTest extends TestCase
{
    #[DataProvider('statusLabels')]
    public function test_status_has_an_indonesian_label(EmployeeStatus $status, string $expected): void
    {
        $this->assertSame($expected, $status->label());
    }

    public static function statusLabels(): array
    {
        return [
            'aktif' => [EmployeeStatus::Active, 'Aktif'],
            'masa percobaan' => [EmployeeStatus::Probation, 'Masa Percobaan'],
            'mutasi' => [EmployeeStatus::Mutated, 'Mutasi'],
            'resign' => [EmployeeStatus::Resigned, 'Resign'],
            'terminasi' => [EmployeeStatus::Terminated, 'Terminasi'],
            'tidak aktif' => [EmployeeStatus::Inactive, 'Tidak Aktif'],
        ];
    }
}
