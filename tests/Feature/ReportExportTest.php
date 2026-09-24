<?php

namespace Tests\Feature;

use App\Jobs\GenerateEmployeeReportExport;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EvaluationComponent;
use App\Models\EvaluationCriterion;
use App\Models\PerformancePeriod;
use App\Models\ReportExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeSnapshot;
use App\Services\ReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_queues_scoped_export(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('report.export', 'web'));
        $this->actingAs($user)->post(route('reports.export'), ['status' => 'ACTIVE'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('report_exports', ['requested_by' => $user->id, 'status' => 'QUEUED']);
        Queue::assertPushed(GenerateEmployeeReportExport::class);
    }

    public function test_user_cannot_download_another_users_export(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $export = ReportExport::query()->create(['requested_by' => $owner->id, 'filters' => [], 'status' => 'COMPLETED', 'file_path' => 'reports/missing.xlsx']);
        $this->actingAs($other)->get(route('reports.download', $export))->assertForbidden();
    }

    public function test_export_contains_required_columns_formats_and_scoped_rows(): void
    {
        Storage::fake('local');
        Notification::fake();

        $user = User::factory()->create();
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $user->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create([
            'employee_number' => 'EMP-001',
            'full_name' => 'Rina Pratama',
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);
        $assignment = $employee->assignments()->create([
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'start_date' => now()->subYear(),
            'status' => 'ACTIVE',
        ]);
        $outsideBranch = Branch::query()->create(['code' => 'SBY', 'name' => 'Surabaya']);
        $outsideDivision = Division::query()->create(['branch_id' => $outsideBranch->id, 'code' => 'OPS', 'name' => 'Operasional']);
        $outside = Employee::query()->create([
            'employee_number' => 'EMP-OUT',
            'full_name' => 'Di Luar Cakupan',
            'join_date' => now()->subMonths(3),
            'current_status' => 'ACTIVE',
        ]);
        $outside->assignments()->create([
            'branch_id' => $outsideBranch->id,
            'division_id' => $outsideDivision->id,
            'start_date' => now()->subMonths(3),
            'status' => 'ACTIVE',
        ]);
        $period = PerformancePeriod::query()->create([
            'name' => 'September 2026',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $criterion = EvaluationCriterion::query()->create([
            'name' => 'Sangat Baik',
            'min_score' => 80,
            'max_score' => 89.99,
            'color_semantic' => 'success',
            'sort_order' => 1,
        ]);
        $component = EvaluationComponent::query()->create([
            'code' => 'MASA_KERJA',
            'name' => 'Masa Kerja',
            'default_weight' => 100,
            'measurement_type' => 'TENURE',
            'scoring_method' => 'RULE',
            'is_auto_calculated' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $evaluation = $employee->evaluations()->create([
            'assignment_id' => $assignment->id,
            'period_id' => $period->id,
            'evaluator_id' => $user->id,
            'total_score' => 85,
            'criteria_id' => $criterion->id,
            'notes' => 'Kinerja konsisten.',
            'status' => 'FINALIZED',
        ]);
        $evaluation->scores()->create([
            'component_id' => $component->id,
            'raw_score' => 85,
            'weight' => 100,
            'weighted_score' => 85,
            'source_type' => 'AUTO',
        ]);

        $snapshot = app(OrganizationalScopeSnapshot::class)->capture($user);
        $export = ReportExport::query()->create([
            'requested_by' => $user->id,
            'filters' => ['status' => 'ACTIVE'],
            'scope_snapshot' => $snapshot,
            'status' => 'QUEUED',
        ]);

        (new GenerateEmployeeReportExport($export->id))->handle(
            app(AuditLogger::class),
            app(ReportQueryService::class),
        );

        $export->refresh();
        $this->assertSame('COMPLETED', $export->status);
        $this->assertSame(1, $export->row_count);
        $this->assertNotNull($export->expires_at);
        Storage::disk('local')->assertExists($export->file_path);
        Notification::assertSentTo($user, SystemNotification::class);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'report.export.complete',
        ]);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($export->file_path));
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame([
            'No', 'NIK / ID Karyawan', 'Nama', 'Cabang', 'Divisi', 'Sub Divisi',
            'Jabatan', 'Tanggal Masuk', 'Masa Kerja %', 'Tanggung Jawab %',
            'Absensi %', 'Inisiatif %', 'Sikap %', 'Komunikasi %',
            'Kerjasama Tim %', 'Total %', 'Kriteria Penilaian', 'Keterangan',
            'Status', 'Penilai', 'Tanggal Penilaian',
        ], $sheet->rangeToArray('A1:U1')[0]);
        $this->assertSame('EMP-001', $sheet->getCell('B2')->getValue());
        $this->assertSame('Rina Pratama', $sheet->getCell('C2')->getValue());
        $this->assertSame('Jakarta', $sheet->getCell('D2')->getValue());
        $this->assertSame(85.0, (float) $sheet->getCell('P2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertSame('A1:U2', $sheet->getAutoFilter()->getRange());
        $this->assertSame('0.00"%"', $sheet->getStyle('P2')->getNumberFormat()->getFormatCode());
    }
}
