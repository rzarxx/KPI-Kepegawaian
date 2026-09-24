<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee_incident.create') && $this->user()?->can('view', $this->route('employee'));
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::exists('incident_categories', 'code')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
            'description' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
