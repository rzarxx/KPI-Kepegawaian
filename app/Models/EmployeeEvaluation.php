<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'assignment_id', 'period_id', 'evaluator_id', 'total_score', 'criteria_id', 'notes', 'status', 'submitted_at', 'approved_at', 'finalized_at', 'closed_at'])]
class EmployeeEvaluation extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'approved_at' => 'datetime', 'finalized_at' => 'datetime', 'closed_at' => 'datetime', 'total_score' => 'decimal:2'];
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
}
