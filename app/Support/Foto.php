<?php

namespace App\Support;

class Foto
{
    /** Lebar dan tinggi asli foto di public/images/foto, supaya setiap <img> punya ukuran. */
    public static function ukuran(string $berkas): array
    {
        static $cache = [];
        if (! isset($cache[$berkas])) {
            $path = public_path('images/foto/'.$berkas);
            $info = is_file($path) ? @getimagesize($path) : false;
            $cache[$berkas] = $info ? [$info[0], $info[1]] : [1600, 1000];
        }

        return $cache[$berkas];
    }

    public static function url(?string $berkas): ?string
    {
        return $berkas ? asset('images/foto/'.$berkas) : null;
    }
}
