<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// F7: una fila por (proyecto, criterio). El total es SUM(puntos).
final class RubricaModelo
{
    /**
     * Null si el proyecto no tiene rúbrica.
     *
     * @return array{puntos: array<int, int>, total: int, autor: string, guardado_en: string}|null
     */
    public static function deProyecto(int $proyectoId): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT r.criterio, r.puntos, u.nombre AS autor, r.evaluado_en
             FROM rubrica_evaluaciones r JOIN usuarios u ON u.id = r.evaluado_por
             WHERE r.proyecto_id = ? ORDER BY r.criterio'
        );
        $sql->execute([$proyectoId]);
        $filas = $sql->fetchAll(\PDO::FETCH_ASSOC);
        if ($filas === []) {
            return null;
        }

        $puntos = array_map(intval(...), array_column($filas, 'puntos', 'criterio'));

        // Quién y cuándo: el último criterio que cambió. Guardar sin cambios no escribe (#98).
        $fechas = array_column($filas, 'evaluado_en');
        $ultima = $filas[array_search(max($fechas), $fechas, true)];

        return ['puntos' => $puntos, 'total' => array_sum($puntos), 'autor' => $ultima['autor'], 'guardado_en' => $ultima['evaluado_en']];
    }

    /** @return array<int, int> proyecto_id => total */
    public static function totales(): array
    {
        $sql = Conexion::pdo()->query('SELECT proyecto_id, SUM(puntos) FROM rubrica_evaluaciones GROUP BY proyecto_id');

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    /**
     * Por diferencia. Dentro de una transacción.
     *
     * @param array<int, int> $puntos criterio => puntos
     */
    public static function guardar(int $proyectoId, int $usuarioId, array $puntos): void
    {
        $filas = [];
        foreach ($puntos as $criterio => $p) {
            $filas[] = ['criterio' => $criterio, 'puntos' => $p];
        }
        FilasModelo::sincronizar('rubrica_evaluaciones', ['proyecto_id' => $proyectoId], ['criterio'], $filas, ['evaluado_por' => $usuarioId]);
    }
}
