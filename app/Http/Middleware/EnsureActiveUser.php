<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Email atau kata sandi tidak sesuai.',
        ]);
    }
}
