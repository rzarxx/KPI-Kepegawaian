<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\Division;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ArchiveOrganizationUnitAction
{
    public function execute(Model $unit, string $type, User $actor, AuditLogger $audit): bool
    {
        $dependencies = $this->dependencies($unit);
        $shouldArchive = array_sum($dependencies) > 0;
        $before = $unit->toArray();

        DB::transaction(function () use ($unit, $type, $actor, $audit, $dependencies, $shouldArchive, $before): void {
            if (! $shouldArchive) {
                $unit->delete();
                $audit->log("organization.{$type}.delete", $actor, $unit, $before, null);

                return;
            }

            $this->archiveRelatedUnits($unit);
            $unit->update(['is_active' => false]);
            $audit->log("organization.{$type}.archive", $actor, $unit, $before, $unit->fresh()->toArray(), [
                'dependencies' => $dependencies,
            ]);
        });

        return $shouldArchive;
    }

    /** @return array<string, int> */
    private function dependencies(Model $unit): array
    {
        if ($unit instanceof Branch) {
            return [
                'divisions' => Division::query()->where('branch_id', $unit->id)->count(),
                'employee_assignments' => EmployeeAssignment::query()->where('branch_id', $unit->id)->count(),
            ];
        }

        if ($unit instanceof Division) {
            return [
                'sub_divisions' => SubDivision::query()->where('division_id', $unit->id)->count(),
                'employee_assignments' => EmployeeAssignment::query()->where('division_id', $unit->id)->count(),
            ];
        }

        if ($unit instanceof SubDivision) {
            return [
                'employee_assignments' => EmployeeAssignment::query()->where('sub_division_id', $unit->id)->count(),
            ];
        }

        if ($unit instanceof Position) {
            return [
                'employee_assignments' => EmployeeAssignment::query()->where('position_id', $unit->id)->count(),
            ];
        }

        return [];
    }

    private function archiveRelatedUnits(Model $unit): void
    {
        if ($unit instanceof Branch) {
            $divisionIds = Division::query()->where('branch_id', $unit->id)->pluck('id');
            SubDivision::query()->whereIn('division_id', $divisionIds)->update(['is_active' => false]);
            Division::query()->whereIn('id', $divisionIds)->update(['is_active' => false]);
        }

        if ($unit instanceof Division) {
            SubDivision::query()->where('division_id', $unit->id)->update(['is_active' => false]);
        }
    }
}
