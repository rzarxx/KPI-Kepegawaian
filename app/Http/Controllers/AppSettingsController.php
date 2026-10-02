<?php

namespace App\Http\Controllers;

use App\Actions\UpdateBrandingAction;
use App\Http\Requests\UpdateBrandingRequest;
use App\Models\AppSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        return Inertia::render('Settings/Appearance', [
            'settings' => AppSetting::branding(),
        ]);
    }

    public function update(UpdateBrandingRequest $request, UpdateBrandingAction $action, AuditLogger $audit): RedirectResponse
    {
        $before = AppSetting::branding();

        $action->execute(
            $request->validated(),
            $request->file('app_logo'),
            $request->boolean('remove_logo'),
        );

        $after = AppSetting::branding();
        $audit->log('settings.branding.update', $request->user(), null, $before, $after);

        return back()->with('success', 'Tampilan sistem berhasil diperbarui.');
    }
}
