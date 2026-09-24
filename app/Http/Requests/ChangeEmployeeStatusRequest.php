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

        return [
            'status' => ['required', Rule::in(['RESIGNED', 'TERMINATED', 'INACTIVE'])],
            'effective_date' => ['required', 'date', 'after_or_equal:'.$employee->join_date->toDateString()],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
