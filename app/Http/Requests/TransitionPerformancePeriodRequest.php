<?php

namespace App\Http\Requests;

use App\Models\PerformancePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionPerformancePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $period = $this->route('period');

        return $period instanceof PerformancePeriod && ($this->user()?->can('transition', $period) ?? false);
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['ACTIVE', 'REVIEW', 'FINALIZED', 'CLOSED'])]];
    }
}
