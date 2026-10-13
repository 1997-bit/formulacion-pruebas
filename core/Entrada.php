<?php

declare(strict_types=1);

namespace App\Core;

// Campos de $_POST. Lo que no es texto queda vacío.
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

    /**
     * name="columna[]" → filas, en el orden de la pantalla.
     *
     * @param list<string> $columnas
     * @return list<array<string, string>>
     */
    public static function filas(array $columnas): array
    {
        $total = is_array($_POST[$columnas[0]] ?? null) ? count($_POST[$columnas[0]]) : 0;
        $filas = [];
        for ($i = 0; $i < $total; $i++) {
            foreach ($columnas as $columna) {
                $valor = $_POST[$columna][$i] ?? '';
                $filas[$i][$columna] = is_string($valor) ? $valor : '';
            }
        }

        return $filas;
    }
}
