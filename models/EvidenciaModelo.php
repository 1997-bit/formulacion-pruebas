<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class EvidenciaModelo
{
    public static function crear(int $casoId, int $tipo, ?string $archivo, ?string $nombreOriginal, ?string $enlace, string $descripcion, int $usuarioId): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO evidencias (caso_id, tipo, archivo, nombre_original, enlace, descripcion, subido_por) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $sql->execute([$casoId, $tipo, $archivo, $nombreOriginal, $enlace, $descripcion, $usuarioId]);

        return (int) Conexion::pdo()->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public static function deCaso(int $casoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT e.id, e.tipo, e.archivo, e.nombre_original, e.enlace, e.descripcion, e.subido_en, u.nombre AS autor
             FROM evidencias e JOIN usuarios u ON u.id = e.subido_por
             WHERE e.caso_id = ? ORDER BY e.subido_en, e.id'
        );
        $sql->execute([$casoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Con el proyecto del caso, para el permiso.
     *
     * @return array<string, mixed>|null
     */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT e.archivo, e.nombre_original, e.enlace, c.proyecto_id
             FROM evidencias e JOIN casos_prueba c ON c.id = e.caso_id WHERE e.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
