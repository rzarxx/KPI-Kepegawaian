<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_date', 'end_date', 'status', 'is_active', 'frequency', 'notify_before_days', 'auto_close', 'reminder_sent_at'])]
class PerformancePeriod extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'auto_close' => 'boolean',
            'reminder_sent_at' => 'datetime',
            'notify_before_days' => 'integer',
        ];
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EmployeeEvaluation::class, 'period_id');
    }

    public function calibrationSessions(): HasMany
    {
        return $this->hasMany(CalibrationSession::class, 'period_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'period_id');
    }

    /** Apakah periode ini sudah melewati tanggal akhir */
    public function isOverdue(): bool
    {
        return $this->end_date->isPast() && in_array($this->status, ['ACTIVE', 'REVIEW'], true);
    }

    /** Apakah periode ini mendekati batas waktu (masuk window pengingat) */
    public function isInReminderWindow(): bool
    {
        if (! in_array($this->status, ['ACTIVE', 'REVIEW'], true)) {
            return false;
        }
        $daysLeft = now()->diffInDays($this->end_date, false);

        return $daysLeft >= 0 && $daysLeft <= $this->notify_before_days;
    }

    public static function frequencyLabels(): array
    {
        return [
            'ONCE' => 'Satu kali',
            'DAILY' => 'Harian',
            'WEEKLY' => 'Mingguan',
            'MONTHLY' => 'Bulanan',
            'QUARTERLY' => 'Per kuartal',
            'YEARLY' => 'Tahunan',
        ];
    }
}
