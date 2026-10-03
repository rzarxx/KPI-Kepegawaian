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

        // Safely drop period_id FK only if it still exists
        // (may have been dropped in a previous partial migration attempt)
        $periodFk = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employee_evaluations'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = 'employee_evaluations_period_id_foreign'"
        );

        if (! empty($periodFk)) {
            Schema::table('employee_evaluations', function (Blueprint $table): void {
                $table->dropForeign(['period_id']);
            });
        }

        // Safely drop composite index only if it exists and isn't needed by a FK.
        // On some MySQL installations this index backs the evaluator_id FK,
        // so we must drop that FK first, remove the index, then re-add the FK.
        $indexName = 'employee_evaluations_employee_id_evaluator_id_index';
        $hasIndex = collect(DB::select("SHOW INDEX FROM employee_evaluations WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if ($hasIndex) {
            // Check if a FK depends on this index
            $fkDeps = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'employee_evaluations'
                   AND COLUMN_NAME = 'evaluator_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL"
            );

            foreach ($fkDeps as $fk) {
                Schema::table('employee_evaluations', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }

            Schema::table('employee_evaluations', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });

            // Re-add evaluator_id FK
            if (! empty($fkDeps)) {
                Schema::table('employee_evaluations', function (Blueprint $table) {
                    $table->foreign('evaluator_id')->references('id')->on('users')->restrictOnDelete();
                });
            }
        }

        // Check if unique constraint already exists (from previous partial run)
        $hasUnique = collect(DB::select(
            "SHOW INDEX FROM employee_evaluations WHERE Key_name = 'evaluations_employee_period_evaluator_unique'"
        ))->isNotEmpty();

        Schema::table('employee_evaluations', function (Blueprint $table) use ($hasUnique): void {
            // Make period_id required
            $table->unsignedBigInteger('period_id')->nullable(false)->change();

            // Only add FK & unique if they don't exist yet
            $existingPeriodFk = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'employee_evaluations'
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = 'employee_evaluations_period_id_foreign'"
            );

            if (empty($existingPeriodFk)) {
                $table->foreign('period_id')->references('id')->on('performance_periods')->restrictOnDelete();
            }

            if (! $hasUnique) {
                $table->unique(['employee_id', 'period_id', 'evaluator_id'], 'evaluations_employee_period_evaluator_unique');
            }
        });
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
