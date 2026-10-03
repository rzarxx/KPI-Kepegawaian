<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_periods', function (Blueprint $table): void {
            // Frekuensi penilaian: ONCE, DAILY, WEEKLY, MONTHLY, QUARTERLY, YEARLY
            $table->string('frequency', 20)->default('ONCE')->after('status');
            // Berapa hari sebelum end_date sistem kirim pengingat ke evaluator
            $table->unsignedTinyInteger('notify_before_days')->default(3)->after('frequency');
            // Apakah periode otomatis ditutup setelah end_date lewat
            $table->boolean('auto_close')->default(false)->after('notify_before_days');
            // Timestamp kapan notifikasi pengingat terakhir dikirim
            $table->timestamp('reminder_sent_at')->nullable()->after('auto_close');
        });
    }

    public function down(): void
    {
        Schema::table('performance_periods', function (Blueprint $table): void {
            $table->dropColumn(['frequency', 'notify_before_days', 'auto_close', 'reminder_sent_at']);
        });
    }
};
