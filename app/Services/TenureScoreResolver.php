<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EvaluationComponent;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TenureScoreResolver
{
    public function resolve(Employee $employee, EvaluationComponent $component, Carbon|string|null $referenceDate = null): float
    {
        $reference = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : now()->startOfDay();
        $months = $employee->join_date->startOfDay()->diffInMonths($reference);
        $rule = $component->rules()->where('is_active', true)->where('rule_type', 'TENURE_MONTHS')
            ->where(fn ($query) => $query->whereNull('min_value')->orWhere('min_value', '<=', $months))
            ->where(fn ($query) => $query->whereNull('max_value')->orWhere('max_value', '>=', $months))
            ->orderBy('min_value')->first();

        if ($rule === null || $rule->score_value === null) {
            throw ValidationException::withMessages(['scores' => "Aturan masa kerja untuk komponen {$component->name} belum lengkap."]);
        }

        return (float) $rule->score_value;
    }
}
