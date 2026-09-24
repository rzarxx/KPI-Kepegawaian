<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
            $table->index(['start_date', 'end_date']);
        });
        Schema::create('evaluation_components', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('default_weight', 5, 2);
            $table->string('measurement_type', 32);
            $table->string('scoring_method', 32);
            $table->boolean('is_auto_calculated')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('evaluation_component_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_id')->constrained('evaluation_components')->restrictOnDelete();
            $table->string('rule_type', 32);
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('score_value', 10, 4)->nullable();
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['component_id', 'is_active']);
        });
        Schema::create('evaluation_criteria', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('min_score', 6, 2);
            $table->decimal('max_score', 6, 2);
            $table->string('color_semantic', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['min_score', 'max_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_component_rules');
        Schema::dropIfExists('evaluation_components');
        Schema::dropIfExists('performance_periods');
    }
};
