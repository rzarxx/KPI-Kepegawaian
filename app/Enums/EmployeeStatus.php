<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'ACTIVE';
    case Probation = 'PROBATION';
    case Mutated = 'MUTATED';
    case Resigned = 'RESIGNED';
    case Terminated = 'TERMINATED';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif', self::Probation => 'Masa Percobaan', self::Mutated => 'Mutasi',
            self::Resigned => 'Resign', self::Terminated => 'Terminasi', self::Inactive => 'Tidak Aktif',
        };
    }
}
