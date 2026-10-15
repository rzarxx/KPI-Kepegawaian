<?php

namespace Database\Seeders;

use App\Models\PerformancePeriod;
use Illuminate\Database\Seeder;
use RuntimeException;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder E2E hanya boleh dijalankan pada environment local atau testing.');
        }

        $this->call(DomainDefaultsSeeder::class);
        $this->call(LocalRoleAccountSeeder::class);

        PerformancePeriod::query()->updateOrCreate(
            ['name' => 'Periode E2E Aktif'],
            [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'ACTIVE',
                'is_active' => true,
                'frequency' => 'MONTHLY',
                'notify_before_days' => 3,
                'auto_close' => false,
                'reminder_sent_at' => null,
            ],
        );
    }
}
