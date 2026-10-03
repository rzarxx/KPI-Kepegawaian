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
            // Drop nullable + old foreign key
            $table->dropForeign(['period_id']);
            $table->dropIndex(['employee_id', 'evaluator_id']);
        });

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
