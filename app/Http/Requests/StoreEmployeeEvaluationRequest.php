<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee !== null && (bool) $this->user()?->can('evaluate', $employee);
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:performance_periods,id'],
            'intent' => ['required', 'in:draft,complete'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'finalize' => ['prohibited'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.component_id' => ['required', 'integer', 'distinct', 'exists:evaluation_components,id'],
            'scores.*.raw_score' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
