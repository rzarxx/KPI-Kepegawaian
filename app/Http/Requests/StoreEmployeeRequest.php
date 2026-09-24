<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:64', Rule::unique('employees', 'employee_number')],
            'national_id' => ['nullable', 'string', 'max:64', Rule::unique('employees', 'national_id')],
            'full_name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date', 'before:today'], 'gender' => ['nullable', Rule::in(['L', 'P'])], 'join_date' => ['required', 'date', 'before_or_equal:today'],
            'current_status' => ['required', Rule::enum(EmployeeStatus::class)], 'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'], 'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'], 'position_id' => ['nullable', 'integer', 'exists:positions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.unique' => 'Nomor karyawan sudah digunakan. Periksa data karyawan lama dan gunakan proses rehire bila orang yang sama kembali bekerja.',
            'national_id.unique' => 'NIK sudah terhubung ke karyawan lama. Verifikasi identitas lalu gunakan proses rehire pada profil karyawan tersebut.',
        ];
    }
}
