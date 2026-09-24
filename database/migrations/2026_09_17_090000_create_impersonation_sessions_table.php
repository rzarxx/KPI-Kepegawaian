<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('target_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['impersonator_id', 'ended_at']);
            $table->index(['target_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_sessions');
    }
};
