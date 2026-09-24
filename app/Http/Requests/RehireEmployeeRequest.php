<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RehireEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rehire', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $earliestDate = $employee?->join_date?->toDateString();

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'effective_date' => array_values(array_filter([
                'required',
                'date',
                $earliestDate ? 'after_or_equal:'.$earliestDate : null,
                'before_or_equal:today',
            ])),
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
