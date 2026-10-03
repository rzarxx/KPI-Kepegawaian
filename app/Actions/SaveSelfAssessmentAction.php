<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EvaluationComponent;
use App\Models\PerformancePeriod;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSelfAssessmentAction
{
    public function __construct(
        private EvaluationCalculator $calculator,
        private AuditLogger $audit,
    ) {}

    public function execute(User $actor, Employee $employee, PerformancePeriod $period, array $data): EmployeeEvaluation
    {
        // Verifikasi: yang menilai adalah karyawan itu sendiri (via user linked to employee)
        if (! $actor->can('evaluation.self_assess')) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk melakukan penilaian diri.');
        }

        // Validasi: employee harus terhubung dengan user via email
        if ($employee->email !== $actor->email) {
            throw new AuthorizationException('Anda hanya dapat menilai diri sendiri.');
        }

        if (! in_array($period->status, ['ACTIVE'], true) || ! $period->is_active) {
            throw ValidationException::withMessages(['period_id' => 'Periode penilaian tidak dalam status aktif.']);
        }

        return DB::transaction(function () use ($actor, $employee, $period, $data) {
            $assignment = $employee->currentAssignment;
            if (! $assignment) {
                throw ValidationException::withMessages(['employee' => 'Karyawan tidak memiliki penempatan aktif.']);
            }

            $components = EvaluationComponent::query()
                ->where('is_active', true)
                ->where('is_auto_calculated', false) // Self-assessment hanya komponen manual
                ->orderBy('sort_order')
                ->get()
                ->keyBy('id');

            $submitted = collect($data['scores'])->keyBy('component_id');

            // Validasi semua komponen manual diisi
            $missingComponents = $components->keys()->diff($submitted->keys());
            if ($missingComponents->isNotEmpty()) {
                throw ValidationException::withMessages(['scores' => 'Semua komponen penilaian wajib diisi.']);
            }

            $allComponents = EvaluationComponent::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('id');

            $weight = $allComponents->sum('default_weight');
            if (round((float) $weight, 2) !== 100.0) {
                throw ValidationException::withMessages(['scores' => 'Total bobot komponen aktif harus tepat 100%.']);
            }

            $scores = $components->map(function (EvaluationComponent $component) use ($submitted): array {
                $rawScore = (float) $submitted[$component->id]['raw_score'];

                return [
                    'component_id' => $component->id,
                    'raw_score' => $rawScore,
                    'weight' => (float) $component->default_weight,
                    'weighted_score' => round($rawScore * ((float) $component->default_weight / 100), 4),
                    'note' => $submitted[$component->id]['note'] ?? null,
                    'source_type' => 'self_assessment',
                ];
            });

            $calculated = $this->calculator->calculate($scores);

            $lookupAttributes = [
                'employee_id' => $employee->id,
                'evaluator_id' => $actor->id,
                'period_id' => $period->id,
                'evaluation_type' => EmployeeEvaluation::TYPE_SELF,
            ];

            $evaluation = EmployeeEvaluation::query()->firstOrNew($lookupAttributes);

            if ($evaluation->exists && ! in_array($evaluation->status, ['DRAFT'], true)) {
                throw ValidationException::withMessages(['evaluation' => 'Penilaian diri yang sudah diajukan tidak dapat diubah.']);
            }

            $before = $evaluation->exists ? $evaluation->toArray() : null;

            $evaluation->fill([
                'assignment_id' => $assignment->id,
                'period_id' => $period->id,
                'evaluation_type' => EmployeeEvaluation::TYPE_SELF,
                'total_score' => $calculated['total_score'],
                'criteria_id' => $calculated['criteria_id'],
                'notes' => $data['notes'] ?? null,
                'status' => 'DRAFT',
            ]);
            $evaluation->save();

            $evaluation->scores()->delete();
            $evaluation->scores()->createMany($scores->all());

            $this->audit->log('evaluation.self_assessment_draft', $actor, $evaluation, $before, [
                'total_score' => $evaluation->total_score,
                'status' => $evaluation->status,
            ]);

            return $evaluation;
        });
    }
}
