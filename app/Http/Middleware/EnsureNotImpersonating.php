<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotImpersonating
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->session()->has('impersonation.original_user_id'), 403, 'Aksi ini tidak tersedia selama impersonasi.');

        return $next($request);
    }
}
