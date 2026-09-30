<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropForeign(['period_id']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropUnique(['employee_id', 'period_id', 'evaluator_id']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->unsignedBigInteger('period_id')->nullable()->change();
            $table->foreign('period_id')->references('id')->on('performance_periods')->restrictOnDelete();
            $table->index(['employee_id', 'evaluator_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropIndex(['employee_id', 'evaluator_id']);
            $table->dropForeign(['period_id']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->unsignedBigInteger('period_id')->nullable(false)->change();
            $table->foreign('period_id')->references('id')->on('performance_periods')->restrictOnDelete();
            $table->unique(['employee_id', 'period_id', 'evaluator_id']);
        });
    }
};
