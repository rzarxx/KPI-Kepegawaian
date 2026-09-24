<?php

namespace Database\Seeders;

use App\Models\EmployeeAttentionRule;
use App\Models\EvaluationComponent;
use App\Models\EvaluationCriterion;
use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;

class DomainDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'MASA_KERJA', 'name' => 'Masa Kerja', 'default_weight' => 10, 'measurement_type' => 'TENURE', 'scoring_method' => 'RULE', 'is_auto_calculated' => true, 'sort_order' => 10],
            ['code' => 'TANGGUNG_JAWAB', 'name' => 'Tanggung Jawab', 'default_weight' => 20, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 20],
            ['code' => 'ABSENSI', 'name' => 'Absensi', 'default_weight' => 20, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 30],
            ['code' => 'INISIATIF', 'name' => 'Inisiatif', 'default_weight' => 10, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 40],
            ['code' => 'SIKAP', 'name' => 'Sikap', 'default_weight' => 15, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 50],
            ['code' => 'KOMUNIKASI', 'name' => 'Komunikasi', 'default_weight' => 10, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 60],
            ['code' => 'KERJASAMA_TIM', 'name' => 'Kerjasama Tim', 'default_weight' => 15, 'measurement_type' => 'MANUAL', 'scoring_method' => 'PERCENTAGE', 'is_auto_calculated' => false, 'sort_order' => 70],
        ];

        foreach ($components as $componentData) {
            $component = EvaluationComponent::query()->updateOrCreate(
                ['code' => $componentData['code']],
                [...$componentData, 'description' => null, 'is_active' => true],
            );
            if ($component->code === 'MASA_KERJA') {
                $component->rules()->delete();
                $component->rules()->createMany([
                    ['rule_type' => 'TENURE_MONTHS', 'min_value' => 0, 'max_value' => 2, 'score_value' => 40, 'is_active' => true],
                    ['rule_type' => 'TENURE_MONTHS', 'min_value' => 3, 'max_value' => 5, 'score_value' => 60, 'is_active' => true],
                    ['rule_type' => 'TENURE_MONTHS', 'min_value' => 6, 'max_value' => 11, 'score_value' => 75, 'is_active' => true],
                    ['rule_type' => 'TENURE_MONTHS', 'min_value' => 12, 'max_value' => 24, 'score_value' => 85, 'is_active' => true],
                    ['rule_type' => 'TENURE_MONTHS', 'min_value' => 25, 'max_value' => null, 'score_value' => 100, 'is_active' => true],
                ]);
            }
        }

        foreach ([
            ['name' => 'Perlu Perhatian', 'min_score' => 0, 'max_score' => 64.99, 'color_semantic' => 'danger', 'sort_order' => 10],
            ['name' => 'Baik', 'min_score' => 65, 'max_score' => 79.99, 'color_semantic' => 'warning', 'sort_order' => 20],
            ['name' => 'Sangat Baik', 'min_score' => 80, 'max_score' => 89.99, 'color_semantic' => 'success', 'sort_order' => 30],
            ['name' => 'Istimewa', 'min_score' => 90, 'max_score' => 100, 'color_semantic' => 'info', 'sort_order' => 40],
        ] as $criterion) {
            EvaluationCriterion::query()->updateOrCreate(['name' => $criterion['name']], $criterion);
        }

        foreach ([
            'attendance' => 'Absensi', 'late' => 'Keterlambatan', 'attitude' => 'Sikap',
            'sop_violation' => 'Pelanggaran SOP', 'communication' => 'Komunikasi',
            'teamwork' => 'Kerjasama Tim', 'work_target' => 'Target Kerja',
            'responsibility' => 'Tanggung Jawab', 'discipline' => 'Kedisiplinan', 'other' => 'Lainnya',
        ] as $code => $name) {
            IncidentCategory::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        foreach ([
            ['name' => 'Absensi di bawah 70', 'rule_type' => 'ATTENDANCE_BELOW', 'minimum_severity' => 'LOW', 'operator' => '<', 'threshold' => 70],
            ['name' => 'Nilai total di bawah 65', 'rule_type' => 'EVALUATION_BELOW', 'minimum_severity' => 'LOW', 'operator' => '<', 'threshold' => 65],
            ['name' => 'Minimal satu insiden tinggi', 'rule_type' => 'INCIDENT_SEVERITY_COUNT', 'minimum_severity' => 'HIGH', 'operator' => '>=', 'threshold' => 1],
            ['name' => 'Minimal tiga insiden sedang', 'rule_type' => 'INCIDENT_SEVERITY_COUNT', 'minimum_severity' => 'MEDIUM', 'operator' => '>=', 'threshold' => 3],
        ] as $rule) {
            EmployeeAttentionRule::query()->updateOrCreate(
                ['name' => $rule['name']],
                [...$rule, 'config' => ['statuses' => ['OPEN', 'UNDER_REVIEW']], 'is_active' => true],
            );
        }
    }
}
