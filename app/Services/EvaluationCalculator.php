<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use Illuminate\Support\Collection;

class EvaluationCalculator
{
    public function calculate(Collection $scores): array
    {
        $total = round($scores->sum(fn ($score) => (float) $score['weighted_score']), 2);
        $criterion = EvaluationCriterion::query()->where('min_score', '<=', $total)->where('max_score', '>=', $total)->orderBy('sort_order')->first();

        return ['total_score' => $total, 'criteria_id' => $criterion?->id];
    }
}
