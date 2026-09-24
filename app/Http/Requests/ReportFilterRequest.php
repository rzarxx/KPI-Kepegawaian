<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->routeIs('reports.export') ? 'report.export' : 'report.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'criteria_id' => ['nullable', 'integer', 'exists:evaluation_criteria,id'],
            'problem_status' => ['nullable', Rule::in(['OPEN', 'UNDER_REVIEW', 'RESOLVED', 'CLOSED'])],
        ];
    }
}
