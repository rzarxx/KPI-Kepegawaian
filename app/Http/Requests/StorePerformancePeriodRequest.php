<?php

namespace App\Http\Requests;

use App\Models\PerformancePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerformancePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $period = $this->route('period');

        return $period ? ($this->user()?->can('update', $period) ?? false) : ($this->user()?->can('create', PerformancePeriod::class) ?? false);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'status' => ['required', Rule::in(['DRAFT'])], 'is_active' => ['boolean']];
    }
}
