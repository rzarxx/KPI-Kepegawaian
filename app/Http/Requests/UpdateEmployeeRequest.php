<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'employee_number' => ['required', 'string', 'max:64', Rule::unique('employees', 'employee_number')->ignore($employee)],
            'national_id' => ['nullable', 'string', 'max:64'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'join_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
