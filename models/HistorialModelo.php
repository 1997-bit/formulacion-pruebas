<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

final class HistorialModelo
{
    /** @return list<array<string, mixed>> */
    public static function deCaso(int $casoId): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT l.campo, l.antes, l.despues, l.fecha, u.nombre AS usuario
             FROM logs_cambios l JOIN usuarios u ON u.id = l.usuario_id
             WHERE l.caso_id = ? ORDER BY l.fecha DESC, l.id DESC'
        );
        $sql->execute([$casoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }
}
