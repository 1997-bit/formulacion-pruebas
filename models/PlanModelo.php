<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// F6: un plan por proyecto; proyecto_id es la llave.
final class PlanModelo
{
    /** @return array<string, mixed>|null */
    public static function deProyecto(int $proyectoId): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT pl.*, u.nombre AS responsable
             FROM plan_pruebas pl JOIN usuarios u ON u.id = pl.responsable_id
             WHERE pl.proyecto_id = ?'
        );
        $sql->execute([$proyectoId]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    // Plan en estado Cerrado (2): no admite stoppers abiertos (BUG-035).
    public static function cerrado(int $proyectoId): bool
    {
        $sql = Conexion::pdo()->prepare('SELECT 1 FROM plan_pruebas WHERE proyecto_id = ? AND estado = 2 FOR UPDATE');
        $sql->execute([$proyectoId]);

        return (bool) $sql->fetchColumn();
    }

    /** @return array<int, int> proyecto_id => estado */
    public static function estados(): array
    {
        $sql = Conexion::pdo()->query('SELECT proyecto_id, estado FROM plan_pruebas');

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    /**
     * Crea o reemplaza el plan del proyecto.
     *
     * @param array{version: string, responsable_id: int, fecha: string, alcance: string, objetivos: string, estrategia: int, recursos: ?string, cronograma: ?string, criterios_aceptacion: string, riesgos: ?string, estado: int} $plan
     */
    public static function guardar(int $proyectoId, array $plan): void
    {
        Conexion::pdo()->prepare(
            'INSERT INTO plan_pruebas (proyecto_id, version, responsable_id, fecha, alcance, objetivos, estrategia,
                                       recursos, cronograma, criterios_aceptacion, riesgos, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE version = VALUES(version), responsable_id = VALUES(responsable_id), fecha = VALUES(fecha),
                alcance = VALUES(alcance), objetivos = VALUES(objetivos), estrategia = VALUES(estrategia),
                recursos = VALUES(recursos), cronograma = VALUES(cronograma),
                criterios_aceptacion = VALUES(criterios_aceptacion), riesgos = VALUES(riesgos), estado = VALUES(estado)'
        )->execute([$proyectoId, ...array_values($plan)]);
    }
}
