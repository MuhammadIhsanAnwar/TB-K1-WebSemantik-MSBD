<?php

namespace App\Http\Middleware;

use App\Support\SupabaseApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan pengguna sudah masuk (dan punya peran yang diminta).
 */
class Peran
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $pengguna = $request->session()->get('pengguna');
        if (! $pengguna) {
            return redirect()->route('masuk', ['kembali' => $request->getRequestUri()]);
        }

        if (
            $roles
            && ! in_array($pengguna['peran'], $roles, true)
            && $request->is('admin', 'admin/*')
            && ! empty($pengguna['email'])
        ) {
            $akun = (new SupabaseApi)->findUserByEmail($pengguna['email']);
            if ($akun) {
                $pengguna['id'] = $akun['id'];
                $pengguna['nama'] = $akun['name'];
                $pengguna['peran'] = $akun['peran'];
                $pengguna['foto_profil'] = $akun['foto_profil'] ?? null;
                $pengguna['tempat_slug'] = $akun['tempat_slug'] ?? null;
                $request->session()->put('pengguna', $pengguna);
            }
        }

        if ($roles && ! in_array($pengguna['peran'], $roles, true)) {
            abort(403, 'Akun ini tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
