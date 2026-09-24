<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAttentionRule;
use Illuminate\Support\Collection;

class EmployeeAttentionService
{
    private const SEVERITY = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];

    public function reasons(Employee $employee, ?Collection $rules = null): array
    {
        $rules ??= EmployeeAttentionRule::query()->where('is_active', true)->get();
        $relations = [];
        if ($rules->contains('rule_type', 'INCIDENT_SEVERITY_COUNT')) {
            $relations[] = 'incidents';
        }
        if ($rules->whereIn('rule_type', ['EVALUATION_BELOW', 'ATTENDANCE_BELOW'])->isNotEmpty()) {
            $relations[] = 'evaluations.scores.component';
        }
        $employee->loadMissing($relations);
        $reasons = [];

        foreach ($rules as $rule) {
            $value = match ($rule->rule_type) {
                'EVALUATION_BELOW' => optional($employee->evaluations->sortByDesc('created_at')->first())->total_score,
                'ATTENDANCE_BELOW' => optional(optional($employee->evaluations->sortByDesc('created_at')->first())->scores?->first(fn ($score) => $score->component?->code === 'ABSENSI'))->raw_score,
                'INCIDENT_SEVERITY_COUNT' => $employee->incidents
                    ->whereIn('status', $rule->config['statuses'] ?? ['OPEN', 'UNDER_REVIEW'])
                    ->filter(fn ($incident) => (self::SEVERITY[$incident->severity] ?? 0) >= (self::SEVERITY[$rule->minimum_severity] ?? 0))->count(),
                default => null,
            };

            if ($value !== null && $this->matches((float) $value, $rule->operator, (float) $rule->threshold)) {
                $reasons[] = ['rule' => $rule->name, 'value' => (float) $value, 'type' => $rule->rule_type];
            }
        }

        return $reasons;
    }

    private function matches(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '=' => $value === $threshold,
            default => false,
        };
    }
}
