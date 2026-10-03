<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus data evaluasi tanpa periode sebelum enforce constraint
        DB::statement('DELETE FROM employee_evaluations WHERE period_id IS NULL');

        // Ambil SEMUA FK yang saat ini ada di tabel
        $existingFks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employee_evaluations'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        // Drop SEMUA FK terlebih dahulu agar bebas memanipulasi index apapun
        foreach ($existingFks as $fk) {
            DB::statement("ALTER TABLE employee_evaluations DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Drop composite index (employee_id, evaluator_id) jika masih ada
        // Setelah semua FK di-drop, tidak ada lagi yang memblokir
        $hasCompositeIndex = ! empty(DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employee_evaluations'
               AND INDEX_NAME = 'employee_evaluations_employee_id_evaluator_id_index'
             LIMIT 1"
        ));

        if ($hasCompositeIndex) {
            DB::statement('ALTER TABLE employee_evaluations DROP INDEX employee_evaluations_employee_id_evaluator_id_index');
        }

        // Ubah period_id menjadi NOT NULL
        DB::statement('ALTER TABLE employee_evaluations MODIFY COLUMN period_id BIGINT UNSIGNED NOT NULL');

        // Helper closure untuk cek apakah FK sudah ada
        $hasFk = fn (string $name): bool => ! empty(DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employee_evaluations'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = ?",
            [$name]
        ));

        // Tambahkan kembali semua FK yang dibutuhkan (idempotent)
        if (! $hasFk('employee_evaluations_employee_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT');
        }

        if (! $hasFk('employee_evaluations_assignment_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES employee_assignments(id) ON DELETE RESTRICT');
        }

        if (! $hasFk('employee_evaluations_period_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_period_id_foreign FOREIGN KEY (period_id) REFERENCES performance_periods(id) ON DELETE RESTRICT');
        }

        if (! $hasFk('employee_evaluations_evaluator_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_evaluator_id_foreign FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE RESTRICT');
        }

        if (! $hasFk('employee_evaluations_criteria_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_criteria_id_foreign FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(id) ON DELETE SET NULL');
        }

        // Tambah unique constraint jika belum ada
        $hasUnique = ! empty(DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employee_evaluations'
               AND INDEX_NAME = 'evaluations_employee_period_evaluator_unique'
               AND NON_UNIQUE = 0
             LIMIT 1"
        ));

        if (! $hasUnique) {
            DB::statement('ALTER TABLE employee_evaluations ADD UNIQUE KEY evaluations_employee_period_evaluator_unique (employee_id, period_id, evaluator_id)');
        }
    }

    public function down(): void
    {
        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropUnique('evaluations_employee_period_evaluator_unique');
            $table->dropForeign(['period_id']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->unsignedBigInteger('period_id')->nullable()->change();
            $table->foreign('period_id')->references('id')->on('performance_periods')->restrictOnDelete();
            $table->index(['employee_id', 'evaluator_id']);
        });
    }
};
