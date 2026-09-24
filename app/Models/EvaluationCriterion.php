<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'min_score', 'max_score', 'color_semantic', 'sort_order'])]
class EvaluationCriterion extends Model {}
