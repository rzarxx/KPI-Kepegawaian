<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmployeeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $allowedStatuses = ['RESIGNED', 'TERMINATED', 'INACTIVE'];

        if ($employee?->current_status->value === 'PROBATION') {
            $allowedStatuses[] = 'ACTIVE';
        }

        return [
            'status' => ['required', Rule::in($allowedStatuses)],
            'effective_date' => ['required', 'date', 'after_or_equal:'.$employee->join_date->toDateString(), 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
