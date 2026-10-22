<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('goal.create');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:goals,id'],
            'period_id' => ['required', 'exists:performance_periods,id'],
            'level' => ['required', 'in:COMPANY,BRANCH,DIVISION,INDIVIDUAL'],
            'goalable_type' => ['prohibited'],
            'goalable_id' => [
                Rule::requiredIf(fn (): bool => $this->input('level') !== 'COMPANY'),
                Rule::prohibitedIf(fn (): bool => $this->input('level') === 'COMPANY'),
                'nullable',
                'integer',
                'min:1',
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'target_unit' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'goalable_type.prohibited' => 'Jenis sasaran ditentukan otomatis oleh sistem.',
            'goalable_id.required' => 'Pilih sasaran target sesuai level yang dipilih.',
            'goalable_id.prohibited' => 'Target tingkat perusahaan tidak memerlukan sasaran organisasi.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'parent_id' => 'target induk',
            'period_id' => 'periode',
            'level' => 'level target',
            'goalable_id' => 'sasaran target',
            'title' => 'judul target',
            'description' => 'deskripsi',
            'target_value' => 'nilai target',
            'target_unit' => 'satuan',
        ];
    }
}
