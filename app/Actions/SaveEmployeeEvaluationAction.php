<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationCalculator;
use App\Services\TenureScoreResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveEmployeeEvaluationAction
{
    public function __construct(private EvaluationCalculator $calculator, private TenureScoreResolver $tenureScores, private AuditLogger $audit) {}

    public function execute(User $actor, Employee $employee, PerformancePeriod $period, array $data): EmployeeEvaluation
    {
        if (! $actor->can('evaluate', $employee)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $employee, $period, $data) {
            $assignment = $employee->currentAssignment;
            if (! $assignment) {
                throw ValidationException::withMessages(['employee' => 'Karyawan tidak memiliki penempatan aktif.']);
            }
            if (! in_array($period->status, ['DRAFT', 'ACTIVE'], true) || ! $period->is_active) {
                throw ValidationException::withMessages(['period' => 'Periode penilaian tidak dapat digunakan.']);
            }
            $components = EvaluationComponent::query()->where('is_active', true)->with('rules')->orderBy('sort_order')->get()->keyBy('id');
            $submitted = collect($data['scores'])->keyBy('component_id');
            if ($submitted->count() !== count($data['scores']) || $submitted->keys()->diff($components->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['scores' => 'Komponen penilaian tidak valid.']);
            }
            if ($components->where('is_auto_calculated', false)->keys()->diff($submitted->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['scores' => 'Semua komponen manual wajib dinilai.']);
            }
            $weight = $components->sum('default_weight');
            if (round((float) $weight, 2) !== 100.0) {
                throw ValidationException::withMessages(['scores' => 'Total bobot komponen aktif harus tepat 100%.']);
            }
            $scores = $components->map(function (EvaluationComponent $component) use ($submitted, $employee): array {
                $isAutomatic = $component->is_auto_calculated;
                $rawScore = $isAutomatic ? $this->tenureScores->resolve($employee, $component) : (float) $submitted[$component->id]['raw_score'];

                return ['component_id' => $component->id, 'raw_score' => $rawScore, 'weight' => (float) $component->default_weight, 'weighted_score' => round($rawScore * ((float) $component->default_weight / 100), 4), 'note' => $isAutomatic ? null : ($submitted[$component->id]['note'] ?? null), 'source_type' => $isAutomatic ? 'automatic' : 'manual'];
            });
            $calculated = $this->calculator->calculate($scores);
            $evaluation = EmployeeEvaluation::query()->firstOrNew(['employee_id' => $employee->id, 'period_id' => $period->id, 'evaluator_id' => $actor->id]);
            if ($evaluation->exists && $evaluation->status !== 'DRAFT') {
                throw ValidationException::withMessages(['evaluation' => 'Penilaian yang sudah diajukan tidak dapat diubah.']);
            }
            $before = $evaluation->exists ? $evaluation->toArray() : null;
            $evaluation->fill(['assignment_id' => $assignment->id, 'total_score' => $calculated['total_score'], 'criteria_id' => $calculated['criteria_id'], 'notes' => $data['notes'] ?? null, 'status' => 'DRAFT']);
            $evaluation->save();
            $evaluation->scores()->delete();
            $evaluation->scores()->createMany($scores->all());
            $this->audit->log('evaluation.save_draft', $actor, $evaluation, $before, ['total_score' => $evaluation->total_score, 'status' => $evaluation->status]);

            return $evaluation;
        });
    }
}
