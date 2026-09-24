<?php

namespace App\Services;

use App\Models\ImpersonationSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public function start(Request $request, User $target, string $reason): void
    {
        /** @var User $actor */
        $actor = $request->user();
        if (! $actor->hasRole('Super Admin') || ! $actor->can('impersonation.start') || ! $actor->is_active || ! $target->is_active || $actor->is($target) || $target->hasRole('Super Admin')) {
            throw new AuthorizationException;
        }
        $session = ImpersonationSession::query()->create(['impersonator_id' => $actor->id, 'target_id' => $target->id, 'reason' => $reason, 'started_at' => now(), 'expires_at' => now()->addMinutes(30), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        app(AuditLogger::class)->log('impersonation.start', $actor, $target, null, null, ['impersonation_session_id' => $session->id, 'reason' => $reason]);
        $request->session()->regenerate();
        Auth::login($target);
        $request->session()->put('impersonation.original_user_id', $actor->id);
        $request->session()->put('impersonation.session_id', $session->id);
    }

    public function end(Request $request, string $reason = 'ended'): void
    {
        $id = $request->session()->get('impersonation.session_id');
        $originalId = $request->session()->get('impersonation.original_user_id');
        $session = $id ? ImpersonationSession::query()->whereNull('ended_at')->find($id) : null;
        $original = $originalId ? User::query()->find($originalId) : null;
        if ($session !== null) {
            $session->update(['ended_at' => now(), 'ended_reason' => $reason]);
            app(AuditLogger::class)->log('impersonation.'.$reason, $original, $session->target, null, null, ['impersonation_session_id' => $session->id]);
        }
        $request->session()->forget(['impersonation.original_user_id', 'impersonation.session_id']);
        $request->session()->regenerate();
        if ($original !== null && $original->is_active && $original->hasRole('Super Admin') && $original->can('impersonation.start')) {
            Auth::login($original);
        } else {
            Auth::logout();
        }
    }
}
