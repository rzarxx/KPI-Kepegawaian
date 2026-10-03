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

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            // Drop old foreign key on period_id so we can change the column
            $table->dropForeign(['period_id']);
        });

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

            $droppedFks = [];
            foreach ($fkDeps as $fk) {
                Schema::table('employee_evaluations', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
                $droppedFks[] = $fk->CONSTRAINT_NAME;
            }

            Schema::table('employee_evaluations', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });

            // Re-add evaluator_id FK
            if (! empty($droppedFks)) {
                Schema::table('employee_evaluations', function (Blueprint $table) {
                    $table->foreign('evaluator_id')->references('id')->on('users')->restrictOnDelete();
                });
            }
        }

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            // Make period_id required
            $table->unsignedBigInteger('period_id')->nullable(false)->change();
            $table->foreign('period_id')->references('id')->on('performance_periods')->restrictOnDelete();
            // One evaluation per employee+period+evaluator
            $table->unique(['employee_id', 'period_id', 'evaluator_id'], 'evaluations_employee_period_evaluator_unique');
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
