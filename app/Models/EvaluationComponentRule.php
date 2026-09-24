<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rule_type', 'min_value', 'max_value', 'score_value', 'config_json', 'is_active'])]
class EvaluationComponentRule extends Model
{
    protected function casts(): array
    {
        return ['config_json' => 'array', 'is_active' => 'boolean'];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(EvaluationComponent::class, 'component_id');
    }
}
