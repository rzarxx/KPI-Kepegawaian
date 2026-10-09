<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\SubDivision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->model();

        return $model !== null && ($this->user()?->can('delete', $model) ?? false);
    }

    public function rules(): array
    {
        return [
            'verification_code' => ['required', 'string', 'max:32', Rule::in([$this->model()?->code])],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_code.in' => 'Kode verifikasi harus sama dengan kode unit yang akan dihapus.',
        ];
    }

    private function model(): Branch|Division|SubDivision|Position|null
    {
        $class = match ($this->route('type')) {
            'cabang' => Branch::class,
            'divisi' => Division::class,
            'sub-divisi' => SubDivision::class,
            'jabatan' => Position::class,
            default => null,
        };

        return $class === null ? null : $class::query()->find($this->route('unit'));
    }
}
