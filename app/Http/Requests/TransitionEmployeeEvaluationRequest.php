<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionEmployeeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['SUBMITTED', 'APPROVED', 'FINALIZED', 'CLOSED'])]];
    }
}
