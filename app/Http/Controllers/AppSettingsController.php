<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'app_logo' => ['nullable', 'image', 'mimes:png,svg,webp', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $before = AppSetting::branding();

        AppSetting::setValue('app_name', $data['app_name']);
        AppSetting::setValue('primary_color', $data['primary_color']);
        AppSetting::setValue('footer_text', $data['footer_text'] ?? '');

        if ($request->boolean('remove_logo')) {
            $oldLogo = AppSetting::getValue('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            AppSetting::setValue('app_logo', null);
        } elseif ($request->hasFile('app_logo')) {
            $oldLogo = AppSetting::getValue('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('app_logo')->store('branding', 'public');
            AppSetting::setValue('app_logo', $path);
        }

        $after = AppSetting::branding();
        $audit->log('settings.branding.update', $request->user(), null, $before, $after);

        return back()->with('success', 'Tampilan sistem berhasil diperbarui.');
    }
}
