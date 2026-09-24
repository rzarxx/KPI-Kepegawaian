<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('scopes') && ($this->filled('branch_id') || $this->filled('division_id') || $this->filled('sub_division_id'))) {
            $this->merge(['scopes' => [[
                'branch_id' => $this->input('branch_id'),
                'division_id' => $this->input('division_id'),
                'sub_division_id' => $this->input('sub_division_id'),
            ]]]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'scopes' => ['nullable', 'array', 'max:20'],
            'scopes.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'scopes.*.division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'scopes.*.sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ];
    }
}
