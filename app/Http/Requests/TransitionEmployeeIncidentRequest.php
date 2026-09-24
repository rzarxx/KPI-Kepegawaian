<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionEmployeeIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['UNDER_REVIEW', 'RESOLVED', 'CLOSED'])],
            'resolution' => ['nullable', 'string', 'max:5000', Rule::requiredIf($this->input('status') === 'RESOLVED')],
        ];
    }
}
