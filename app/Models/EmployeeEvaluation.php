<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'assignment_id', 'period_id', 'evaluator_id', 'evaluation_type', 'total_score', 'criteria_id', 'notes', 'status', 'submitted_at', 'approved_at', 'finalized_at', 'closed_at'])]
class EmployeeEvaluation extends Model
{
    /** Tipe penilaian */
    public const TYPE_SELF = 'SELF';

    public const TYPE_SUPERVISOR = 'SUPERVISOR';

    public const TYPE_PEER = 'PEER';

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'approved_at' => 'datetime', 'finalized_at' => 'datetime', 'closed_at' => 'datetime', 'total_score' => 'decimal:2'];
    }

    /** Apakah ini self-assessment */
    public function isSelfAssessment(): bool
    {
        return $this->evaluation_type === self::TYPE_SELF;
    }

    /** Apakah ini penilaian atasan */
    public function isSupervisorEvaluation(): bool
    {
        return $this->evaluation_type === self::TYPE_SUPERVISOR;
    }

    /** Scope: hanya self-assessment */
    public function scopeSelfAssessment($query)
    {
        return $query->where('evaluation_type', self::TYPE_SELF);
    }

    /** Scope: hanya penilaian atasan */
    public function scopeSupervisor($query)
    {
        return $query->where('evaluation_type', self::TYPE_SUPERVISOR);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_SELF => 'Penilaian Diri',
            self::TYPE_SUPERVISOR => 'Penilaian Atasan',
            self::TYPE_PEER => 'Penilaian Rekan',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssignment::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criteria_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EmployeeEvaluationScore::class, 'evaluation_id');
    }

    public function calibrationAdjustments(): HasMany
    {
        return $this->hasMany(CalibrationAdjustment::class, 'evaluation_id');
    }
}
