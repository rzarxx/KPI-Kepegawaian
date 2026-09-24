<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->index();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'start_date']);
            $table->index(['branch_id', 'division_id', 'sub_division_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
    }
};
