<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['parent_id', 'period_id', 'level', 'goalable_type', 'goalable_id', 'title', 'description', 'target_value', 'target_unit', 'actual_value', 'achievement_percentage', 'status', 'created_by'])]
class Goal extends Model
{
    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'achievement_percentage' => 'decimal:2',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    /** Polymorphic: bisa Branch, Division, Employee */
    public function goalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Hitung persentase pencapaian */
    public function calculateAchievement(): ?float
    {
        if ($this->target_value === null || (float) $this->target_value === 0.0) {
            return null;
        }

        return round(((float) $this->actual_value / (float) $this->target_value) * 100, 2);
    }

    /** Update actual dari rata-rata children */
    public function syncFromChildren(): void
    {
        $children = $this->children()->get();
        if ($children->isEmpty()) {
            return;
        }

        $avg = $children->avg('achievement_percentage');
        $this->update([
            'achievement_percentage' => $avg !== null ? round($avg, 2) : null,
        ]);
    }

    public static function levelLabels(): array
    {
        return [
            'COMPANY' => 'Perusahaan',
            'BRANCH' => 'Cabang',
            'DIVISION' => 'Divisi',
            'INDIVIDUAL' => 'Individu',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'DRAFT' => 'Draf',
            'ACTIVE' => 'Aktif',
            'COMPLETED' => 'Selesai',
            'CANCELLED' => 'Dibatalkan',
        ];
    }
}
