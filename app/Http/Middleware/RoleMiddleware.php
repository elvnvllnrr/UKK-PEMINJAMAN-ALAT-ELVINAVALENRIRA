<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Memeriksa role pengguna sebelum permintaan diteruskan. */
class RoleMiddleware
{
    /** Meneruskan permintaan hanya jika pengguna terautentikasi dan rolenya diizinkan. */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (in_array(Auth::user()->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}









