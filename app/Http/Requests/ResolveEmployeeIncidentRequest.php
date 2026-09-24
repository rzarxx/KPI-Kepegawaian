<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveEmployeeIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('resolve', $this->route('incident')) ?? false;
    }

    public function rules(): array
    {
        return ['resolution' => ['required', 'string', 'max:5000']];
    }
}
