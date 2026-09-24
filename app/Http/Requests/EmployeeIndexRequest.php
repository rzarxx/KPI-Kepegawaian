<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:ACTIVE,PROBATION,MUTATED,RESIGNED,TERMINATED,INACTIVE'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ];
    }
}
