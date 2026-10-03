<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['period_id', 'name', 'description', 'status', 'scope_type', 'scope_id', 'created_by', 'calibrated_at'])]
class CalibrationSession extends Model
{
    protected function casts(): array
    {
        return [
            'calibrated_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CalibrationAdjustment::class, 'session_id');
    }

    /** Apakah sesi masih bisa diedit */
    public function isEditable(): bool
    {
        return $this->status === 'DRAFT';
    }

    /** Apakah sesi sudah difinalisasi */
    public function isFinalized(): bool
    {
        return in_array($this->status, ['FINALIZED', 'APPLIED'], true);
    }

    public static function statusLabels(): array
    {
        return [
            'DRAFT' => 'Draf',
            'IN_REVIEW' => 'Ditinjau',
            'FINALIZED' => 'Final',
            'APPLIED' => 'Diterapkan',
        ];
    }
}
