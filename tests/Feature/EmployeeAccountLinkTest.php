<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_assessment_requires_an_explicit_employee_account_link(): void
    {
        $user = User::factory()->create(['email' => 'pejuang@example.test']);
        $user->givePermissionTo(Permission::findOrCreate('evaluation.self_assess', 'web'));
        $employee = Employee::query()->create([
            'employee_number' => 'EMP-LINK-01',
            'full_name' => 'Pejuang Terhubung',
            'email' => 'pejuang@example.test',
            'join_date' => now()->subYear(),
            'current_status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->get(route('self-assessment.index'))
            ->assertInertia(fn ($page) => $page->where('employee', null));

        Employee::query()->whereKey($employee->id)->update(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('self-assessment.index'))
            ->assertInertia(fn ($page) => $page->where('employee.id', $employee->id));
    }
}
