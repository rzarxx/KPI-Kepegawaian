<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ($this->user()?->can('evaluation.create') && $this->user()?->can('employee.view'))) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'finalize' => ['prohibited'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.component_id' => ['required', 'integer', 'distinct', 'exists:evaluation_components,id'],
            'scores.*.raw_score' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
