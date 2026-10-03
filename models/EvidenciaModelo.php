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
}
