<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class TransferEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transfer', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'effective_date' => ['required', 'date', 'after_or_equal:'.$employee->join_date->toDateString()],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
