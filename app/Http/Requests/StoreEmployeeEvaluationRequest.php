<?php

namespace App\Http\Requests;

use App\Models\EvaluationComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee !== null && (bool) $this->user()?->can('evaluate', $employee);
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:performance_periods,id'],
            'intent' => ['required', 'in:draft,complete'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'finalize' => ['prohibited'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.component_id' => ['required', 'integer', 'distinct', 'exists:evaluation_components,id'],
            'scores.*.contribution_score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.raw_score' => ['prohibited'],
            'scores.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $scores = collect($this->input('scores', []));
            $componentIds = $scores
                ->pluck('component_id')
                ->filter(fn ($id): bool => is_numeric($id))
                ->map(fn ($id): int => (int) $id)
                ->unique();
            $components = EvaluationComponent::query()
                ->whereIn('id', $componentIds)
                ->get()
                ->keyBy('id');

            foreach ($scores as $index => $score) {
                if (! is_array($score) || ! is_numeric($score['component_id'] ?? null)) {
                    continue;
                }

                $component = $components->get((int) $score['component_id']);
                if (! $component || ! $component->is_active) {
                    $validator->errors()->add(
                        "scores.{$index}.component_id",
                        'Komponen penilaian tidak aktif atau tidak valid.',
                    );

                    continue;
                }

                $contribution = $score['contribution_score'] ?? null;
                if ($component->is_auto_calculated) {
                    if ($contribution !== null && $contribution !== '') {
                        $validator->errors()->add(
                            "scores.{$index}.contribution_score",
                            'Nilai komponen otomatis dihitung oleh sistem.',
                        );
                    }

                    continue;
                }

                if ($contribution === null || $contribution === '') {
                    $validator->errors()->add(
                        "scores.{$index}.contribution_score",
                        "Nilai {$component->name} wajib diisi.",
                    );

                    continue;
                }

                if (is_numeric($contribution) && (float) $contribution > (float) $component->default_weight) {
                    $maximum = rtrim(rtrim(number_format((float) $component->default_weight, 2, '.', ''), '0'), '.');
                    $validator->errors()->add(
                        "scores.{$index}.contribution_score",
                        "Nilai {$component->name} maksimal {$maximum} sesuai bobot komponen.",
                    );
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'scores.*.component_id.distinct' => 'Komponen penilaian tidak boleh dikirim lebih dari satu kali.',
            'scores.*.contribution_score.numeric' => 'Nilai kontribusi harus berupa angka.',
            'scores.*.contribution_score.min' => 'Nilai kontribusi tidak boleh negatif.',
            'scores.*.raw_score.prohibited' => 'Gunakan nilai kontribusi sesuai bobot komponen.',
        ];
    }
}
