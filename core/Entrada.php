<?php

declare(strict_types=1);

namespace App\Core;

// Campos de $_POST: lo que no sea texto queda vacío.
final class Entrada
{
    /**
     * @param list<string> $campos
     * @return array<string, string>
     */
    public static function textos(array $campos): array
    {
        $datos = [];
        foreach ($campos as $campo) {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }

        return $datos;
    }
}
