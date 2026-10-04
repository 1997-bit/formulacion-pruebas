<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// F8: una fila por (proyecto, evaluador, evaluado, aspecto). Auto si evaluador = evaluado.
final class AutoevaluacionModelo
{
    /** @return list<array<string, mixed>> */
    public static function deEvaluador(int $proyectoId, int $evaluadorId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT a.evaluado_id, u.nombre AS evaluado, a.aspecto, a.puntos, a.comentario
             FROM autoevaluaciones a JOIN usuarios u ON u.id = a.evaluado_id
             WHERE a.proyecto_id = ? AND a.evaluador_id = ? ORDER BY a.aspecto'
        );
        $sql->execute([$proyectoId, $evaluadorId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cada miembro con el promedio de su autoevaluación y de la coevaluación que recibió. Null si no hay.
     *
     * @return list<array{id: int, nombre: string, auto: ?float, co: ?float}>
     */
    public static function promedios(int $proyectoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT u.id, u.nombre,
                    (SELECT AVG(a.puntos) FROM autoevaluaciones a
                     WHERE a.proyecto_id = m.proyecto_id AND a.evaluador_id = u.id AND a.evaluado_id = u.id) AS auto,
                    (SELECT AVG(a.puntos) FROM autoevaluaciones a
                     WHERE a.proyecto_id = m.proyecto_id AND a.evaluador_id <> u.id AND a.evaluado_id = u.id) AS co
             FROM proyecto_miembros m JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.proyecto_id = ? ORDER BY u.nombre'
        );
        $sql->execute([$proyectoId]);

        return array_map(fn (array $f): array => [
            'id' => (int) $f['id'],
            'nombre' => $f['nombre'],
            'auto' => $f['auto'] === null ? null : (float) $f['auto'],
            'co' => $f['co'] === null ? null : (float) $f['co'],
        ], $sql->fetchAll(\PDO::FETCH_ASSOC));
    }

    /** @return array<int, int> proyecto_id => personas que ya guardaron */
    public static function contar(): array
    {
        $sql = Conexion::pdo()->query('SELECT proyecto_id, COUNT(DISTINCT evaluador_id) FROM autoevaluaciones GROUP BY proyecto_id');

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    /**
     * Lo del evaluador en el proyecto, por diferencia. Dentro de una transacción.
     *
     * @param list<array{evaluado_id: int, aspecto: int, puntos: int, comentario: ?string}> $filas
     */
    public static function guardar(int $proyectoId, int $evaluadorId, array $filas): void
    {
        FilasModelo::sincronizar('autoevaluaciones', ['proyecto_id' => $proyectoId, 'evaluador_id' => $evaluadorId], ['evaluado_id', 'aspecto'], $filas);
    }
}
