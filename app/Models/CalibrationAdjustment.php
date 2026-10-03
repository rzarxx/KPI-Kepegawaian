<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'evaluation_id', 'original_score', 'adjusted_score', 'adjusted_criteria_id', 'reason', 'adjusted_by'])]
class CalibrationAdjustment extends Model
{
    protected function casts(): array
    {
        return [
            'original_score' => 'decimal:2',
            'adjusted_score' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CalibrationSession::class, 'session_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(EmployeeEvaluation::class, 'evaluation_id');
    }

    public function adjustedCriteria(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'adjusted_criteria_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /** Selisih perubahan skor */
    public function scoreDifference(): float
    {
        return round((float) $this->adjusted_score - (float) $this->original_score, 2);
    }
}
