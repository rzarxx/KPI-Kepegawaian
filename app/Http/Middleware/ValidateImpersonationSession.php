<?php

namespace App\Http\Middleware;

use App\Models\ImpersonationSession;
use App\Models\User;
use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;

class ValidateImpersonationSession
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('impersonation.session_id');
        if ($id === null) {
            return $next($request);
        }
        $session = ImpersonationSession::query()->whereNull('ended_at')->find($id);
        $original = $session ? User::query()->find($session->impersonator_id) : null;
        $target = $session ? User::query()->find($session->target_id) : null;
        $invalid = $session === null || $session->expires_at->isPast() || $original === null || ! $original->is_active || ! $original->hasRole('Super Admin') || ! $original->can('impersonation.start') || $target === null || ! $target->is_active;
        if (! $invalid) {
            return $next($request);
        }
        app(ImpersonationService::class)->end($request, $session?->expires_at->isPast() ? 'timeout' : 'revoked');

        return redirect()->route('dashboard')->with('error', 'Sesi impersonasi telah berakhir.');
    }
}
