<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_number', 64)->unique();
            $table->string('national_id', 64)->nullable()->index();
            $table->string('full_name')->index();
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->date('join_date');
            $table->string('current_status', 20)->index();
            $table->date('exit_date')->nullable();
            $table->string('exit_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
