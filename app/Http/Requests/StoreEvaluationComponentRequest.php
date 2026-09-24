<?php

namespace App\Http\Requests;

use App\Models\EvaluationComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $component = $this->route('component');

        return $component ? ($this->user()?->can('update', $component) ?? false) : ($this->user()?->can('create', EvaluationComponent::class) ?? false);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:64', Rule::unique('evaluation_components', 'code')->ignore($this->route('component'))], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'default_weight' => ['required', 'numeric', 'between:0,100'], 'measurement_type' => ['required', Rule::in(['MANUAL', 'TENURE'])], 'scoring_method' => ['required', Rule::in(['PERCENTAGE', 'RULE'])], 'is_auto_calculated' => ['boolean'], 'is_active' => ['boolean'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'rules' => ['array'], 'rules.*.rule_type' => ['required', 'in:TENURE_MONTHS'], 'rules.*.min_value' => ['required', 'numeric', 'min:0'], 'rules.*.max_value' => ['nullable', 'numeric', 'gte:rules.*.min_value'], 'rules.*.score_value' => ['required', 'numeric', 'between:0,100']];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('measurement_type') !== 'TENURE') {
                return;
            }
            $rules = collect($this->input('rules', []))->sortBy('min_value')->values();
            if ($rules->isEmpty()) {
                $validator->errors()->add('rules', 'Komponen masa kerja harus memiliki minimal satu aturan.');

                return;
            }
            foreach ($rules as $index => $rule) {
                if ($index > 0 && $rules[$index - 1]['max_value'] !== null && (float) $rule['min_value'] <= (float) $rules[$index - 1]['max_value']) {
                    $validator->errors()->add("rules.$index.min_value", 'Rentang aturan masa kerja tidak boleh bertumpang tindih.');
                }
            }
        }];
    }
}
