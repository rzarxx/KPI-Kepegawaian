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

    protected function prepareForValidation(): void
    {
        if ($this->has('scopes') && is_array($this->input('scopes'))) {
            $this->merge([
                'scopes' => collect($this->input('scopes'))->map(fn ($scope) => [
                    'branch_id' => ($scope['branch_id'] ?? '') !== '' ? $scope['branch_id'] : null,
                    'division_id' => ($scope['division_id'] ?? '') !== '' ? $scope['division_id'] : null,
                    'sub_division_id' => ($scope['sub_division_id'] ?? '') !== '' ? $scope['sub_division_id'] : null,
                ])->all(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'employee_id' => ['nullable', 'integer', 'required_if:role,Employee', 'exists:employees,id'],
            'scopes' => ['nullable', 'array', 'max:20'],
            'scopes.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'scopes.*.division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'scopes.*.sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ];
    }
}
