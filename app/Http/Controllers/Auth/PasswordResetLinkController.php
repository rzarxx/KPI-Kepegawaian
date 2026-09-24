<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $userExists = User::query()
            ->where('email', (string) $request->string('email'))
            ->where('is_active', true)
            ->exists();

        if ($userExists) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', 'Jika email cocok dengan akun aktif, tautan atur ulang kata sandi telah dikirim.');
    }
}
