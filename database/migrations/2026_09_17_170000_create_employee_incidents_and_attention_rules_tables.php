<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('category', 100);
            $table->string('severity', 20)->index();
            $table->text('description');
            $table->date('occurred_at')->index();
            $table->string('status', 20)->default('OPEN')->index();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });
        Schema::create('employee_attention_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('minimum_severity', 20);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        DB::table('employee_attention_rules')->insert(['name' => 'Insiden tinggi dan kritis terbuka', 'minimum_severity' => 'HIGH', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attention_rules');
        Schema::dropIfExists('employee_incidents');
    }
};
