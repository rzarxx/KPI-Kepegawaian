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
        // Delete any orphaned evaluations with no period (ad-hoc) before enforcing constraint
        DB::statement('DELETE FROM employee_evaluations WHERE period_id IS NULL');

        // Helper: cek apakah FK constraint ada di tabel
        $hasFk = function (string $constraint): bool {
            return ! empty(DB::select(
                "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'employee_evaluations'
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = ?",
                [$constraint]
            ));
        };

        // Helper: cek apakah index ada di tabel
        $hasIndex = function (string $keyName): bool {
            return ! empty(DB::select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'employee_evaluations'
                   AND INDEX_NAME = ?",
                [$keyName]
            ));
        };

        // Helper: cek apakah unique constraint ada
        $hasUnique = function (string $keyName): bool {
            return ! empty(DB::select(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'employee_evaluations'
                   AND INDEX_NAME = ?
                   AND NON_UNIQUE = 0",
                [$keyName]
            ));
        };

        // 1. Drop period_id FK jika masih ada
        if ($hasFk('employee_evaluations_period_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations DROP FOREIGN KEY employee_evaluations_period_id_foreign');
        }

        // 2. Drop composite index (employee_id, evaluator_id) jika masih ada
        //    Index ini dipakai sebagai backing index untuk evaluator_id FK,
        //    jadi harus drop FK-nya dulu
        if ($hasIndex('employee_evaluations_employee_id_evaluator_id_index')) {
            // Drop evaluator_id FK dulu (backing index ini)
            if ($hasFk('employee_evaluations_evaluator_id_foreign')) {
                DB::statement('ALTER TABLE employee_evaluations DROP FOREIGN KEY employee_evaluations_evaluator_id_foreign');
            }

            DB::statement('ALTER TABLE employee_evaluations DROP INDEX employee_evaluations_employee_id_evaluator_id_index');

            // Re-add evaluator_id FK dengan index-nya sendiri
            if (! $hasFk('employee_evaluations_evaluator_id_foreign')) {
                DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_evaluator_id_foreign FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE RESTRICT');
            }
        }

        // 3. Ubah period_id jadi NOT NULL
        DB::statement('ALTER TABLE employee_evaluations MODIFY COLUMN period_id BIGINT UNSIGNED NOT NULL');

        // 4. Re-add period_id FK jika belum ada
        if (! $hasFk('employee_evaluations_period_id_foreign')) {
            DB::statement('ALTER TABLE employee_evaluations ADD CONSTRAINT employee_evaluations_period_id_foreign FOREIGN KEY (period_id) REFERENCES performance_periods(id) ON DELETE RESTRICT');
        }

        // 5. Tambah unique constraint jika belum ada
        if (! $hasUnique('evaluations_employee_period_evaluator_unique')) {
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
