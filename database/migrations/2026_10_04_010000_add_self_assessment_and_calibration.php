<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah evaluation_type pada employee_evaluations
        //    untuk membedakan self-assessment vs supervisor vs peer
        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->string('evaluation_type', 20)->default('SUPERVISOR')->after('evaluator_id');
            $table->index(['employee_id', 'period_id', 'evaluation_type']);
        });

        // Update unique constraint: satu pejuang hanya satu penilaian per tipe per periode per evaluator
        if (DB::getDriverName() === 'mysql') {
            Schema::table('employee_evaluations', function (Blueprint $table): void {
                $table->dropUnique('evaluations_employee_period_evaluator_unique');
            });
        }

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->unique(
                ['employee_id', 'period_id', 'evaluator_id', 'evaluation_type'],
                'evaluations_emp_period_evaluator_type_unique'
            );
        });

        // 2. Tabel sesi kalibrasi
        Schema::create('calibration_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained('performance_periods')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('DRAFT')->index();
            // scope: bisa per cabang, divisi, atau semua
            $table->string('scope_type', 20)->nullable(); // BRANCH, DIVISION, ALL
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('calibrated_at')->nullable();
            $table->timestamps();
            $table->index(['period_id', 'status']);
        });

        // 3. Penyesuaian nilai per pejuang dalam sesi kalibrasi
        Schema::create('calibration_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('calibration_sessions')->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained('employee_evaluations')->restrictOnDelete();
            $table->decimal('original_score', 6, 2);
            $table->decimal('adjusted_score', 6, 2);
            $table->foreignId('adjusted_criteria_id')->nullable()->constrained('evaluation_criteria')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('adjusted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['session_id', 'evaluation_id']);
        });

        // 4. Tabel goal/target (cascading KPI)
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->foreignId('period_id')->constrained('performance_periods')->restrictOnDelete();
            $table->string('level', 20); // COMPANY, BRANCH, DIVISION, INDIVIDUAL
            // polymorphic scope: bisa Branch, Division, Employee
            $table->nullableMorphs('goalable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('target_unit', 50)->nullable(); // %, jumlah, Rp, dll
            $table->decimal('actual_value', 10, 2)->nullable();
            $table->decimal('achievement_percentage', 6, 2)->nullable();
            $table->string('status', 20)->default('DRAFT')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['period_id', 'level']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        Schema::dropIfExists('calibration_adjustments');
        Schema::dropIfExists('calibration_sessions');

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropUnique('evaluations_emp_period_evaluator_type_unique');
            if (DB::getDriverName() === 'mysql') {
                $table->unique(
                    ['employee_id', 'period_id', 'evaluator_id'],
                    'evaluations_employee_period_evaluator_unique'
                );
            }
            $table->dropIndex(['employee_id', 'period_id', 'evaluation_type']);
            $table->dropColumn('evaluation_type');
        });
    }
};
