<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user, ImpersonationService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $service->start($request, $user, $data['reason']);

        return redirect()->route('dashboard')->with('success', 'Impersonasi dimulai.');
    }

    public function end(Request $request, ImpersonationService $service)
    {
        abort_unless($request->session()->has('impersonation.original_user_id'), 403);
        $service->end($request);

        return redirect()->route('dashboard')->with('success', 'Kembali ke akun Super Admin.');
    }
}
