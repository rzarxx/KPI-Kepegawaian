<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'default_weight', 'measurement_type', 'scoring_method', 'is_auto_calculated', 'is_active', 'sort_order'])]
class EvaluationComponent extends Model
{
    protected function casts(): array
    {
        return ['default_weight' => 'decimal:2', 'is_auto_calculated' => 'boolean', 'is_active' => 'boolean'];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EvaluationComponentRule::class, 'component_id');
    }
}
