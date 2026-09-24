<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'rule_type', 'minimum_severity', 'operator', 'threshold', 'config', 'is_active'])]
class EmployeeAttentionRule extends Model
{
    protected function casts(): array
    {
        return ['threshold' => 'decimal:2', 'config' => 'array', 'is_active' => 'boolean'];
    }
}
