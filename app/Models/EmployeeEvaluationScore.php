<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['component_id', 'raw_score', 'weight', 'weighted_score', 'note', 'source_type'])]
class EmployeeEvaluationScore extends Model
{
    protected function casts(): array
    {
        return ['raw_score' => 'decimal:2', 'weight' => 'decimal:2', 'weighted_score' => 'decimal:4'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(EmployeeEvaluation::class, 'evaluation_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(EvaluationComponent::class, 'component_id');
    }
}
