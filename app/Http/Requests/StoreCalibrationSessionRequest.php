<?php

namespace App\Http\Requests;

use App\Models\CalibrationSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalibrationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CalibrationSession::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:performance_periods,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'scope_type' => ['nullable', 'in:BRANCH,DIVISION,ALL'],
            'scope_id' => ['nullable', 'integer', 'required_with:scope_type'],
        ];
    }
}
