<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('goal.create');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:goals,id'],
            'period_id' => ['required', 'exists:performance_periods,id'],
            'level' => ['required', 'in:COMPANY,BRANCH,DIVISION,INDIVIDUAL'],
            'goalable_type' => ['nullable', 'string', 'in:App\Models\Branch,App\Models\Division,App\Models\Employee'],
            'goalable_id' => ['nullable', 'integer', 'required_with:goalable_type'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'target_unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}
