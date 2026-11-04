<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan pengguna sudah masuk (dan punya peran yang diminta).
 * Prototipe: identitas disimpan di sesi karena database akun belum tersambung.
 */
class Peran
{
    public function handle(Request $request, Closure $next, ?string $peran = null): Response
    {
        $pengguna = $request->session()->get('pengguna');

        if (! $pengguna) {
            return redirect()->route('masuk', ['kembali' => $request->getRequestUri()]);
        }

        if ($peran && $pengguna['peran'] !== $peran) {
            abort(403, 'Halaman ini khusus akun '.$peran.'.');
        }

        return $next($request);
    }
}
