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

    public function execute(User $actor, Employee $employee, ?PerformancePeriod $period, array $data): EmployeeEvaluation
    {
        if (! $actor->can('evaluate', $employee)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $employee, $period, $data) {
            // Serialisasi penyimpanan per karyawan agar dua akun Kasubdiv tidak
            // dapat membuat dua penilaian supervisor untuk periode yang sama.
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $employee->load('currentAssignment');
            $assignment = $employee->currentAssignment;
            if (! $assignment) {
                throw ValidationException::withMessages(['employee' => 'Karyawan tidak memiliki penempatan aktif.']);
            }
            if (! $period || $period->status !== 'ACTIVE' || ! $period->is_active) {
                throw ValidationException::withMessages(['period_id' => 'Periode penilaian tidak dapat digunakan saat ini.']);
            }
            $components = EvaluationComponent::query()->where('is_active', true)->with('rules')->orderBy('sort_order')->get()->keyBy('id');
            $submitted = collect($data['scores'])->keyBy('component_id');
            if ($submitted->count() !== count($data['scores']) || $submitted->keys()->diff($components->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['scores' => 'Komponen penilaian tidak valid.']);
            }
            if ($components->where('is_auto_calculated', false)->keys()->diff($submitted->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['scores' => 'Semua komponen manual wajib dinilai.']);
            }
            $hasBlankManualScore = $components
                ->where('is_auto_calculated', false)
                ->contains(fn (EvaluationComponent $component): bool => ! is_numeric($submitted[$component->id]['raw_score'] ?? null));
            if ($hasBlankManualScore) {
                throw ValidationException::withMessages(['scores' => 'Nilai setiap komponen manual wajib diisi.']);
            }
            $weight = $components->sum('default_weight');
            if (round((float) $weight, 2) !== 100.0) {
                throw ValidationException::withMessages(['scores' => 'Total bobot komponen aktif harus tepat 100%.']);
            }
            $referenceDate = $period->end_date;
            $scores = $components->map(function (EvaluationComponent $component) use ($submitted, $employee, $referenceDate): array {
                $isAutomatic = $component->is_auto_calculated;
                $rawScore = $isAutomatic ? $this->tenureScores->resolve($employee, $component, $referenceDate) : (float) $submitted[$component->id]['raw_score'];

                return ['component_id' => $component->id, 'raw_score' => $rawScore, 'weight' => (float) $component->default_weight, 'weighted_score' => round($rawScore * ((float) $component->default_weight / 100), 4), 'note' => $isAutomatic ? null : ($submitted[$component->id]['note'] ?? null), 'source_type' => $isAutomatic ? 'automatic' : 'manual'];
            });
            $calculated = $this->calculator->calculate($scores);
            $lookupAttributes = ['employee_id' => $employee->id, 'period_id' => $period->id, 'evaluation_type' => EmployeeEvaluation::TYPE_SUPERVISOR];
            $evaluation = EmployeeEvaluation::query()
                ->where($lookupAttributes)
                ->latest('id')
                ->first() ?? new EmployeeEvaluation($lookupAttributes);
            if ($evaluation->exists && ($evaluation->evaluator_id !== $actor->id || $evaluation->status !== 'DRAFT')) {
                throw ValidationException::withMessages(['evaluation' => 'Penilaian ini tidak dapat diubah. Hubungi HR jika perlu dibuka kembali.']);
            }
            $isComplete = ($data['intent'] ?? 'draft') === 'complete';
            $before = $evaluation->exists ? $evaluation->toArray() : null;
            $evaluation->fill([
                'assignment_id' => $assignment->id,
                'period_id' => $period->id,
                'evaluator_id' => $actor->id,
                'evaluation_type' => EmployeeEvaluation::TYPE_SUPERVISOR,
                'total_score' => $calculated['total_score'],
                'criteria_id' => $calculated['criteria_id'],
                'notes' => $data['notes'] ?? null,
                'status' => $isComplete ? 'FINALIZED' : 'DRAFT',
                'finalized_at' => $isComplete ? now() : null,
            ]);
            $evaluation->save();
            $evaluation->scores()->delete();
            $evaluation->scores()->createMany($scores->all());
            $this->audit->log(
                $isComplete ? 'evaluation.completed' : 'evaluation.save_draft',
                $actor,
                $evaluation,
                $before,
                ['total_score' => $evaluation->total_score, 'status' => $evaluation->status],
            );

            return $evaluation;
        });
    }
}
