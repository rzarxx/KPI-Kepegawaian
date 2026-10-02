<?php

namespace App\Http\Controllers;

use App\Services\DashboardQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardQueryService $dashboard): Response
    {
        abort_unless($request->user()->can('employee.view'), 403);

        $filters = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:performance_periods,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ]);

        $data = $dashboard->gather($request->user(), $filters);

        return Inertia::render('Dashboard', [
            ...$data,
            'filters' => $filters,
        ]);
    }
}
