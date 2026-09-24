<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\SubDivision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = match ($this->route('type')) {
            'cabang' => Branch::class, 'divisi' => Division::class, 'sub-divisi' => SubDivision::class, 'jabatan' => Position::class
        };
        if ($this->isMethod('post')) {
            return $this->user()?->can('create', $model) ?? false;
        }
        $unit = $model::query()->find($this->route('unit'));

        return $unit !== null && ($this->user()?->can('update', $unit) ?? false);
    }

    public function rules(): array
    {
        $table = match ($this->route('type')) {
            'cabang' => 'branches', 'divisi' => 'divisions', 'sub-divisi' => 'sub_divisions', 'jabatan' => 'positions'
        };
        $unit = $this->route('unit');

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique($table, 'code')->ignore($unit)],
            'name' => ['required', 'string', 'max:255'], 'is_active' => ['boolean'],
            'branch_id' => [Rule::requiredIf($this->route('type') === 'divisi'), 'nullable', 'integer', 'exists:branches,id'],
            'division_id' => [Rule::requiredIf($this->route('type') === 'sub-divisi'), 'nullable', 'integer', 'exists:divisions,id'],
            'level' => [Rule::requiredIf($this->route('type') === 'jabatan'), 'nullable', 'string', 'max:64'],
        ];
    }
}
