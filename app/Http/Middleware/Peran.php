<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan pengguna sudah masuk (dan punya peran yang diminta).
 */
class Peran
{
    public function handle(Request $request, Closure $next, ?string $peran = null): Response
    {
        $pengguna = $request->user();
        $roles = $peran ? explode(',', $peran) : [];
        if (! $pengguna) {
            return redirect()->route('masuk', ['kembali' => $request->getRequestUri()]);
        }

        if ($roles && ! in_array($pengguna->peran, $roles, true)) {
            abort(403, 'Akun ini tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
