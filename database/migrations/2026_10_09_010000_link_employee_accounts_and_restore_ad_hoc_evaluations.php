<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
        });

        $matchedUsers = DB::table('users')
            ->select('email', DB::raw('MIN(id) as id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('email')
            ->groupBy('email')
            ->having('total', '=', 1)
            ->pluck('id', 'email');

        $employeesByEmail = DB::table('employees')
            ->select('email', DB::raw('MIN(id) as id'), DB::raw('COUNT(*) as total'))
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->groupBy('email')
            ->having('total', '=', 1)
            ->get();

        foreach ($employeesByEmail as $employee) {
            $userId = $matchedUsers[$employee->email] ?? null;
            if ($userId !== null) {
                DB::table('employees')->where('id', $employee->id)->update(['user_id' => $userId]);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employee_evaluations MODIFY COLUMN period_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employee_evaluations MODIFY COLUMN period_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
