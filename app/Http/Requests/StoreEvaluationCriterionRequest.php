<?php

namespace App\Http\Requests;

use App\Models\EvaluationCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $criterion = $this->route('criterion');

        return $criterion ? ($this->user()?->can('update', $criterion) ?? false) : ($this->user()?->can('create', EvaluationCriterion::class) ?? false);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'min_score' => ['required', 'numeric', 'between:0,100'], 'max_score' => ['required', 'numeric', 'between:0,100', 'gte:min_score'], 'color_semantic' => ['required', Rule::in(['success', 'warning', 'danger', 'info', 'neutral'])], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $criterion = $this->route('criterion');
            $overlaps = EvaluationCriterion::query()
                ->when($criterion, fn ($query) => $query->whereKeyNot($criterion->id))
                ->where('min_score', '<=', $this->input('max_score'))
                ->where('max_score', '>=', $this->input('min_score'))
                ->exists();
            if ($overlaps) {
                $validator->errors()->add('min_score', 'Rentang nilai tidak boleh bertumpang tindih dengan kriteria lain.');
            }
        }];
    }
}
