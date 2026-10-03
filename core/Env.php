<?php

declare(strict_types=1);

namespace App\Core;

// Lector mínimo de .env.
final class Env
{
    public static function cargar(string $ruta): void
    {
        if (!is_file($ruta)) {
            throw new \RuntimeException('Falta el archivo .env. Copie .env.example a .env y complete los valores.');
        }

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            $valor = trim($valor, "\"'");
            $_ENV[$clave] = $valor;
        }
    }

    public static function get(string $clave, ?string $defecto = null): ?string
    {
        return $_ENV[$clave] ?? $defecto;
    }
}
