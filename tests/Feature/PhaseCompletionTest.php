<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeAttentionRule;
use App\Models\EvaluationComponent;
use App\Models\SubDivision;
use App\Models\User;
use App\Notifications\SystemNotification;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DomainDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_evaluation_trend_uses_qualified_columns_when_scope_has_no_employees(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('evaluation.view', 'web'),
        );
        [$branch] = $this->organization('EMPTY');
        $user->organizationalScopes()->create([
            'branch_id' => $branch->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.total', 0)
                ->where('metrics.average', 0)
                ->has('trend', 0));
    }

    public function test_dashboard_attention_respects_permission_and_organizational_scope(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee_incident.view', 'web'),
        );
        [$branch, $division] = $this->organization('JKT');
        [$outsideBranch, $outsideDivision] = $this->organization('SBY');
        $insideSubDivision = SubDivision::query()->create(['division_id' => $division->id, 'code' => 'SUB-JKT', 'name' => 'Sub Divisi JKT']);
        SubDivision::query()->create(['division_id' => $outsideDivision->id, 'code' => 'SUB-SBY', 'name' => 'Sub Divisi SBY']);
        $user->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $inside = $this->employeeAt($branch, $division, 'EMP-IN');
        $outside = $this->employeeAt($outsideBranch, $outsideDivision, 'EMP-OUT');
        EmployeeAttentionRule::query()->updateOrCreate(
            ['name' => 'Insiden tinggi'],
            ['rule_type' => 'INCIDENT_SEVERITY_COUNT', 'minimum_severity' => 'HIGH', 'operator' => '>=', 'threshold' => 1, 'config' => ['statuses' => ['OPEN']], 'is_active' => true],
        );
        foreach ([$inside, $outside] as $employee) {
            $employee->incidents()->create([
                'category' => 'discipline',
                'title' => 'Pelanggaran disiplin',
                'severity' => 'HIGH',
                'description' => 'Catatan pengujian cakupan.',
                'occurred_at' => now(),
                'status' => 'OPEN',
                'reported_by' => $user->id,
            ]);
        }

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.total', 1)
                ->where('metrics.attention', 1)
                ->has('filterOptions.subDivisions', 1)
                ->where('filterOptions.subDivisions.0.id', $insideSubDivision->id)
                ->has('priorities', 1)
                ->where('priorities.0.id', $inside->id));
    }

    public function test_private_employee_document_requires_permission_and_scope(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $owner->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee_document.view', 'web'),
            Permission::findOrCreate('employee_document.create', 'web'),
            Permission::findOrCreate('employee_document.delete', 'web'),
        );
        [$branch, $division] = $this->organization('JKT');
        $owner->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = $this->employeeAt($branch, $division, 'EMP-DOC');

        $this->actingAs($owner)->post(route('employees.documents.store', $employee), [
            'category' => 'KONTRAK',
            'document' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $document = $employee->documents()->sole();
        Storage::disk('local')->assertExists($document->path);
        $this->actingAs($owner)->get(route('employees.documents.download', $document))->assertOk();

        $outsider = User::factory()->create();
        $outsider->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee_document.view', 'web'),
        );
        [$outsideBranch] = $this->organization('SBY');
        $outsider->organizationalScopes()->create(['branch_id' => $outsideBranch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $this->actingAs($outsider)->get(route('employees.documents.download', $document))->assertForbidden();

        $this->actingAs($owner)->delete(route('employees.documents.destroy', $document))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_private_employee_document_rejects_invalid_type_and_oversized_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee_document.create', 'web'),
        );
        [$branch, $division] = $this->organization('JKT');
        $user->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = $this->employeeAt($branch, $division, 'EMP-UPLOAD');

        $this->actingAs($user)->post(route('employees.documents.store', $employee), [
            'category' => 'KONTRAK',
            'document' => UploadedFile::fake()->create('bukan-pdf.pdf', 100, 'text/plain'),
        ])->assertSessionHasErrors('document');

        $this->actingAs($user)->post(route('employees.documents.store', $employee), [
            'category' => 'KONTRAK',
            'document' => UploadedFile::fake()->create('terlalu-besar.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('employee_documents', 0);
    }

    public function test_notification_read_action_is_limited_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->notifyNow(new SystemNotification('Laporan siap', 'File dapat diunduh.'));
        $notification = $owner->notifications()->sole();

        $this->actingAs($other)->post(route('notifications.read', $notification->id))->assertNotFound();
        $this->actingAs($owner)->post(route('notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_domain_defaults_seeder_is_idempotent(): void
    {
        $this->seed(DomainDefaultsSeeder::class);
        $this->seed(DomainDefaultsSeeder::class);

        $this->assertDatabaseCount('evaluation_components', 7);
        $this->assertDatabaseCount('evaluation_component_rules', 5);
        $this->assertDatabaseCount('evaluation_criteria', 4);
        $this->assertDatabaseCount('incident_categories', 10);
        $this->assertDatabaseCount('employee_attention_rules', 5);
        $this->assertSame(100.0, (float) EvaluationComponent::query()->where('is_active', true)->sum('default_weight'));
    }

    public function test_access_control_seeder_is_idempotent(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'audit.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'employee_document.delete', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->assertSame(
            Permission::query()->count(),
            User::factory()->create()->assignRole('Super Admin')->getAllPermissions()->count(),
        );
    }

    private function organization(string $code): array
    {
        $branch = Branch::query()->create(['code' => $code, 'name' => 'Cabang '.$code]);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'DIV-'.$code, 'name' => 'Divisi '.$code]);

        return [$branch, $division];
    }

    private function employeeAt(Branch $branch, Division $division, string $number): Employee
    {
        $employee = Employee::query()->create([
            'employee_number' => $number,
            'full_name' => 'Karyawan '.$number,
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);
        $employee->assignments()->create([
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'start_date' => now()->subYear(),
            'status' => 'ACTIVE',
        ]);

        return $employee;
    }
}
