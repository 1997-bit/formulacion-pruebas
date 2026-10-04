<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class ReporteModelo
{
    // Tester: solo sus proyectos (RF-05).
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    /**
     * Una fila por proyecto, también sin casos. $proyectoId null: todos los permitidos.
     *
     * @return list<array<string, mixed>>
     */
    public static function porProyecto(int $usuarioId, bool $admin, ?int $proyectoId = null): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT p.id, p.nombre,
                    COUNT(c.id) AS casos,
                    COALESCE(SUM(c.estado = 0), 0) AS pendientes,
                    COALESCE(SUM(c.estado = 1), 0) AS ok,
                    COALESCE(SUM(c.estado = 2), 0) AS fault,
                    COALESCE(SUM(c.estado <> 0 AND NOT EXISTS (SELECT 1 FROM evidencias e WHERE e.caso_id = c.id)), 0) AS sin_evidencia,
                    COALESCE(SUM(c.estado = 2 AND NOT EXISTS (SELECT 1 FROM incidentes i WHERE i.caso_id = c.id)), 0) AS fault_sin_incidente,
                    (SELECT COUNT(*) FROM incidentes i WHERE i.proyecto_id = p.id AND i.estado <> 2) AS incidentes_abiertos,
                    (SELECT COUNT(*) FROM incidentes i WHERE i.proyecto_id = p.id AND i.estado <> 2 AND i.es_stopper = 1) AS stoppers
             FROM proyectos p
             LEFT JOIN casos_prueba c ON c.proyecto_id = p.id
             WHERE ' . self::PERMITIDO . ($proyectoId === null ? '' : ' AND p.id = ?') . '
             GROUP BY p.id, p.nombre
             ORDER BY p.nombre'
        );
        $sql->execute($proyectoId === null ? [(int) $admin, $usuarioId] : [(int) $admin, $usuarioId, $proyectoId]);

        // SUM devuelve DECIMAL: PDO lo trae como texto.
        $filas = $sql->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            foreach (['pendientes', 'ok', 'fault', 'sin_evidencia', 'fault_sin_incidente'] as $columna) {
                $f[$columna] = (int) $f[$columna];
            }
        }
        unset($f);

        return $filas;
    }

    /**
     * Con resultado y sin evidencia: RF-24 no lo permite.
     *
     * @return list<array<string, mixed>>
     */
    public static function casosSinEvidencia(int $proyectoId): array
    {
        return self::casos($proyectoId, 'c.estado <> 0 AND NOT EXISTS (SELECT 1 FROM evidencias e WHERE e.caso_id = c.id)');
    }

    /**
     * FAULT sin defecto registrado (RF-19).
     *
     * @return list<array<string, mixed>>
     */
    public static function faultSinIncidente(int $proyectoId): array
    {
        return self::casos($proyectoId, 'c.estado = 2 AND NOT EXISTS (SELECT 1 FROM incidentes i WHERE i.caso_id = c.id)');
    }

    /** @return list<array<string, mixed>> */
    public static function stoppersAbiertos(int $proyectoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT i.id, i.codigo, i.titulo, i.severidad, i.estado, c.id AS caso_id, c.codigo AS caso
             FROM incidentes i
             JOIN casos_prueba c ON c.id = i.caso_id
             WHERE i.proyecto_id = ? AND i.es_stopper = 1 AND i.estado <> 2
             ORDER BY i.severidad DESC, i.codigo'
        );
        $sql->execute([$proyectoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * $condicion es fija del modelo, nunca de la petición.
     *
     * @return list<array<string, mixed>>
     */
    private static function casos(int $proyectoId, string $condicion): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.estado FROM casos_prueba c
             WHERE c.proyecto_id = ? AND ' . $condicion . ' ORDER BY c.codigo'
        );
        $sql->execute([$proyectoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }
}
