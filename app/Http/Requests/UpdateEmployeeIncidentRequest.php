<?php

namespace App\Http\Requests;

use App\Models\EmployeeIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof EmployeeIncident && ($this->user()?->can('update', $incident) ?? false);
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
