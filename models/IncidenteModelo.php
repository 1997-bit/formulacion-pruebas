<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class IncidenteModelo
{
    /** @return list<array<string, mixed>> */
    public static function deCaso(int $casoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT id, codigo, titulo, severidad, estado, es_stopper FROM incidentes WHERE caso_id = ? ORDER BY codigo'
        );
        $sql->execute([$casoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Dentro de una transacción.
    public static function siguienteNumero(int $proyectoId): int
    {
        $sql = Conexion::pdo()->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1
             FROM incidentes WHERE proyecto_id = ? AND codigo LIKE 'BUG-%' FOR UPDATE"
        );
        $sql->execute([$proyectoId]);

        return (int) $sql->fetchColumn();
    }

    /** @param array<string, mixed> $incidente */
    public static function crear(array $incidente): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO incidentes (' . implode(', ', array_keys($incidente)) . ')
             VALUES (' . implode(', ', array_fill(0, count($incidente), '?')) . ')'
        );
        $sql->execute(array_values($incidente));

        return (int) Conexion::pdo()->lastInsertId();
    }
}
