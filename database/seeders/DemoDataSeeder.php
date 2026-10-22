<?php

namespace Database\Seeders;

use App\Actions\SaveEmployeeEvaluationAction;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EmployeeIncident;
use App\Models\EvaluationComponent;
use App\Models\IncidentCategory;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Data uji coba: satu cabang lengkap sampai sub divisi, pejuang, akun kepala,
 * catatan khusus, serta penilaian periode lalu (selesai) dan periode aktif.
 *
 * Semua kode, nomor induk, dan email memakai awalan DEMO agar mudah dikenali.
 * Di luar local/testing wajib DEMO_SEED_CONFIRM=DATA-DUMMY-OK.
 */
class DemoDataSeeder extends Seeder
{
    public const CONFIRMATION = 'DATA-DUMMY-OK';

    private const EMAIL_DOMAIN = 'demo.albahjah.or.id';

    /** Selisih nilai per komponen agar tiap nilai tidak seragam. */
    private const SCORE_OFFSETS = [
        'TANGGUNG_JAWAB' => 2,
        'ABSENSI' => 0,
        'INISIATIF' => -3,
        'SIKAP' => 1,
        'KOMUNIKASI' => -1,
        'KERJASAMA_TIM' => 1,
    ];

    public function __construct(private readonly SaveEmployeeEvaluationAction $saveEvaluation) {}

    public function run(): void
    {
        $this->guardEnvironment();
        $password = $this->password();

        if (Branch::query()->where('code', 'DEMO-CBG')->exists()) {
            throw new RuntimeException('Data demo sudah ada (cabang DEMO-CBG). Seeder ini hanya boleh dijalankan sekali.');
        }

        foreach (['Branch Head', 'Division Head', 'Sub Division Head'] as $role) {
            if (! Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
                throw new RuntimeException("Role {$role} belum tersedia. Jalankan AccessControlSeeder terlebih dahulu.");
            }
        }

        $summary = DB::transaction(function () use ($password): array {
            $branch = Branch::query()->create(['code' => 'DEMO-CBG', 'name' => 'DEMO Cabang Uji Coba', 'is_active' => true]);
            $finance = Division::query()->create(['branch_id' => $branch->id, 'code' => 'DEMO-KEU', 'name' => 'DEMO Keuangan', 'is_active' => true]);
            $operations = Division::query()->create(['branch_id' => $branch->id, 'code' => 'DEMO-OPS', 'name' => 'DEMO Operasional', 'is_active' => true]);
            $accounting = SubDivision::query()->create(['division_id' => $finance->id, 'code' => 'DEMO-KEU-AKT', 'name' => 'DEMO Akuntansi', 'is_active' => true]);
            $field = SubDivision::query()->create(['division_id' => $operations->id, 'code' => 'DEMO-OPS-LAP', 'name' => 'DEMO Lapangan', 'is_active' => true]);

            $branchHead = $this->account('kepala.cabang', 'DEMO Kepala Cabang', 'Branch Head', $password, $branch->id, null, null);
            $divisionHead = $this->account('kepala.divisi.keuangan', 'DEMO Kepala Divisi Keuangan', 'Division Head', $password, $branch->id, $finance->id, null);
            $accountingHead = $this->account('kasubdiv.akuntansi', 'DEMO Kasubdiv Akuntansi', 'Sub Division Head', $password, $branch->id, $finance->id, $accounting->id);
            $fieldHead = $this->account('kasubdiv.lapangan', 'DEMO Kasubdiv Lapangan', 'Sub Division Head', $password, $branch->id, $operations->id, $field->id);

            $placeAccounting = [$branch->id, $finance->id, $accounting->id];
            $placeField = [$branch->id, $operations->id, $field->id];
            $placeOperationsOnly = [$branch->id, $operations->id, null];

            $employees = [
                'siti' => $this->employee('DEMO-001', 'Siti Rahmawati', now()->subMonths(40), 'ACTIVE', $placeAccounting),
                'budi' => $this->employee('DEMO-002', 'Budi Santoso', now()->subMonths(19), 'ACTIVE', $placeAccounting),
                'dewi' => $this->employee('DEMO-003', 'Dewi Lestari', now()->subMonths(9), 'ACTIVE', $placeAccounting),
                'rizky' => $this->employee('DEMO-004', 'Rizky Pratama', now()->subMonths(26), 'ACTIVE', $placeAccounting),
                'nur' => $this->employee('DEMO-005', 'Nur Aini', now()->subMonths(2)->startOfMonth(), 'PROBATION', $placeAccounting),
                'joko' => $this->employee('DEMO-007', 'Joko Susilo', now()->subMonths(57), 'ACTIVE', $placeField),
                'fitri' => $this->employee('DEMO-008', 'Fitri Handayani', now()->subMonths(11), 'ACTIVE', $placeField),
                'hendra' => $this->employee('DEMO-009', 'Hendra Gunawan', now()->subMonths(29), 'ACTIVE', $placeField),
                'yusuf' => $this->employee('DEMO-010', 'Yusuf Maulana', now()->subMonths(37), 'ACTIVE', $placeField),
                // Belum punya Sub Divisi: tampil sebagai "perlu penempatan" bagi HR.
                'lina' => $this->employee('DEMO-011', 'Lina Marlina', now()->subWeeks(4), 'ACTIVE', $placeOperationsOnly),
            ];
            // Riwayat pejuang yang sudah resign; tidak muncul di daftar penilaian.
            $this->employee('DEMO-006', 'Agus Wijaya', now()->subMonths(30), 'RESIGNED', $placeAccounting, now()->subMonths(2)->endOfMonth());

            $pastStart = now()->subMonthNoOverflow()->startOfMonth();
            $activeStart = now()->startOfMonth();
            $pastPeriod = $this->period('DEMO Penilaian '.$pastStart->translatedFormat('F Y'), $pastStart);
            $activePeriod = $this->period('DEMO Penilaian '.$activeStart->translatedFormat('F Y'), $activeStart);

            // Periode lalu: semua pejuang yang memenuhi syarat sudah dinilai sampai Selesai.
            $pastBases = ['siti' => 95, 'budi' => 82, 'dewi' => 72, 'rizky' => 58, 'nur' => 68, 'joko' => 86, 'fitri' => 74, 'hendra' => 70, 'yusuf' => 60];
            foreach ($pastBases as $key => $base) {
                $evaluator = in_array($key, ['joko', 'fitri', 'hendra', 'yusuf'], true) ? $fieldHead : $accountingHead;
                $this->evaluate($evaluator, $employees[$key], $pastPeriod, $base, 'complete');
            }
            EmployeeEvaluation::query()->where('period_id', $pastPeriod->id)->update(['finalized_at' => $pastPeriod->end_date->copy()->subDays(2)->setTime(10, 0)]);
            $pastPeriod->update(['status' => 'CLOSED', 'is_active' => false]);

            // Periode aktif: campuran Selesai, Draf, dan Belum Dinilai.
            $this->evaluate($accountingHead, $employees['siti'], $activePeriod, 96, 'complete');
            $this->evaluate($accountingHead, $employees['budi'], $activePeriod, 84, 'complete');
            $this->evaluate($accountingHead, $employees['dewi'], $activePeriod, 75, 'draft');
            $this->evaluate($fieldHead, $employees['joko'], $activePeriod, 88, 'complete');
            $this->evaluate($fieldHead, $employees['fitri'], $activePeriod, 71, 'draft');

            $this->incident($employees['rizky'], 'late', 'Terlambat tiga kali dalam seminggu', 'MEDIUM', 'OPEN', now()->subDays(9), $accountingHead);
            $this->incident($employees['yusuf'], 'discipline', 'Meninggalkan lokasi kerja tanpa izin', 'HIGH', 'UNDER_REVIEW', now()->subDays(6), $fieldHead);
            $this->incident($employees['budi'], 'late', 'Terlambat menyerahkan laporan bulanan', 'LOW', 'RESOLVED', now()->subDays(40), $accountingHead, $branchHead, 'Pembinaan lisan; laporan berikutnya diserahkan tepat waktu.');
            $this->incident($employees['hendra'], 'sop_violation', 'Tidak memakai APD saat bertugas', 'MEDIUM', 'CLOSED', now()->subDays(35), $fieldHead, $branchHead, 'Teguran tertulis dan pelatihan ulang K3.');

            return [
                ['Kepala Cabang', $branchHead->email],
                ['Kepala Divisi Keuangan', $divisionHead->email],
                ['Kasubdiv Akuntansi', $accountingHead->email],
                ['Kasubdiv Lapangan', $fieldHead->email],
            ];
        });

        $this->command?->newLine();
        $this->command?->info('Data demo berhasil dibuat: 1 cabang, 2 divisi, 2 sub divisi, 11 pejuang, 2 periode, 4 catatan khusus.');
        $this->command?->table(['Akun demo', 'Email'], $summary);
        $this->command?->warn('Password semua akun demo adalah nilai DEMO_SEED_PASSWORD yang Anda masukkan.');
    }

    private function guardEnvironment(): void
    {
        if (app()->environment(['local', 'testing'])) {
            return;
        }

        if (env('DEMO_SEED_CONFIRM') !== self::CONFIRMATION) {
            throw new RuntimeException('Seeder demo di environment '.app()->environment().' memerlukan DEMO_SEED_CONFIRM='.self::CONFIRMATION.'.');
        }
    }

    private function password(): string
    {
        $password = (string) env('DEMO_SEED_PASSWORD', app()->environment('testing') ? 'DemoTesting2026!' : '');

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('DEMO_SEED_PASSWORD wajib diisi minimal 12 karakter.');
        }

        return $password;
    }

    private function account(string $local, string $name, string $role, string $password, int $branchId, ?int $divisionId, ?int $subDivisionId): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $local.'@'.self::EMAIL_DOMAIN,
            'password' => $password,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([$role]);
        $user->organizationalScopes()->create([
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'sub_division_id' => $subDivisionId,
            'scope_type' => $subDivisionId ? 'sub_division' : ($divisionId ? 'division' : 'branch'),
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * @param  array{int, int, int|null}  $placement
     */
    private function employee(string $number, string $name, Carbon $joinDate, string $status, array $placement, ?Carbon $exitDate = null): Employee
    {
        [$branchId, $divisionId, $subDivisionId] = $placement;

        $employee = Employee::query()->create([
            'employee_number' => $number,
            'full_name' => $name,
            'email' => strtolower($number).'@'.self::EMAIL_DOMAIN,
            'join_date' => $joinDate->toDateString(),
            'current_status' => $status,
            'exit_date' => $exitDate?->toDateString(),
            'exit_reason' => $exitDate ? 'Data demo: mengundurkan diri' : null,
        ]);

        $employee->assignments()->create([
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'sub_division_id' => $subDivisionId,
            'position_id' => null,
            'start_date' => $joinDate->toDateString(),
            'end_date' => $exitDate?->toDateString(),
            'status' => $status,
            'reason' => 'Data demo',
        ]);

        return $employee;
    }

    private function period(string $name, Carbon $start): PerformancePeriod
    {
        return PerformancePeriod::query()->create([
            'name' => $name,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => 'ACTIVE',
            'is_active' => true,
            'frequency' => 'MONTHLY',
            'notify_before_days' => 3,
            'auto_close' => false,
            'reminder_sent_at' => null,
        ]);
    }

    private function evaluate(User $evaluator, Employee $employee, PerformancePeriod $period, int $base, string $intent): void
    {
        $scores = EvaluationComponent::query()
            ->where('is_active', true)
            ->where('is_auto_calculated', false)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EvaluationComponent $component): array => [
                'component_id' => $component->id,
                'raw_score' => max(0, min(100, $base + (self::SCORE_OFFSETS[$component->code] ?? 0))),
                'note' => null,
            ])
            ->all();

        $this->saveEvaluation->execute($evaluator, $employee, $period->fresh(), [
            'scores' => $scores,
            'intent' => $intent,
            'notes' => 'Data demo',
        ]);
    }

    private function incident(Employee $employee, string $category, string $title, string $severity, string $status, Carbon $occurredAt, User $reporter, ?User $resolver = null, ?string $resolution = null): void
    {
        if (! IncidentCategory::query()->where('code', $category)->exists()) {
            $category = 'other';
        }

        EmployeeIncident::query()->create([
            'employee_id' => $employee->id,
            'employee_assignment_id' => $employee->currentAssignment()->value('id'),
            'category' => $category,
            'title' => $title,
            'severity' => $severity,
            'description' => 'Data demo untuk uji coba fitur Catatan Khusus.',
            'occurred_at' => $occurredAt->toDateString(),
            'status' => $status,
            'resolution' => $resolution,
            'reported_by' => $reporter->id,
            'resolved_by' => $resolver?->id,
            'resolved_at' => $resolver ? $occurredAt->copy()->addDays(3) : null,
        ]);
    }
}
