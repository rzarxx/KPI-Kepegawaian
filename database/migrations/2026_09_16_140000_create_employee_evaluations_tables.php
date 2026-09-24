<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignment_id')->constrained('employee_assignments')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('performance_periods')->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_score', 6, 2)->default(0);
            $table->foreignId('criteria_id')->nullable()->constrained('evaluation_criteria')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'period_id', 'evaluator_id']);
            $table->index(['period_id', 'status']);
        });
        Schema::create('employee_evaluation_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('employee_evaluations')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('evaluation_components')->restrictOnDelete();
            $table->decimal('raw_score', 8, 2);
            $table->decimal('weight', 5, 2);
            $table->decimal('weighted_score', 10, 4);
            $table->text('note')->nullable();
            $table->string('source_type', 32);
            $table->timestamps();
            $table->unique(['evaluation_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_evaluation_scores');
        Schema::dropIfExists('employee_evaluations');
    }
};
