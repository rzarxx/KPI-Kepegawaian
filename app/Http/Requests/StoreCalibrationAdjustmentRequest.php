<?php

namespace App\Http\Requests;

use App\Models\CalibrationSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalibrationAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');

        return $session instanceof CalibrationSession
            && $this->user()->can('update', $session);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'exists:employee_evaluations,id'],
            'adjusted_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
