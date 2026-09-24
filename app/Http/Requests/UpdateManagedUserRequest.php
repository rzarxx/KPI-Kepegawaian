<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'scopes' => ['nullable', 'array', 'max:20'],
            'scopes.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'scopes.*.division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'scopes.*.sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ];
    }
}
