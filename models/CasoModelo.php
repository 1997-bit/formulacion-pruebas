<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class CasoModelo
{
    // Tester: solo sus proyectos (RF-05).
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    public static function contar(int $usuarioId, bool $admin): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COUNT(*) FROM casos_prueba c JOIN proyectos p ON p.id = c.proyecto_id WHERE ' . self::PERMITIDO
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return (int) $sql->fetchColumn();
    }

    /**
     * $offset null: todos.
     *
     * @return list<array<string, mixed>>
     */
    public static function listar(int $usuarioId, bool $admin, ?int $offset = null): array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT c.id, c.codigo, c.objetivo, c.tipo_prueba, c.estado, p.nombre AS proyecto, u.nombre AS autor
             FROM casos_prueba c
             JOIN proyectos p ON p.id = c.proyecto_id
             JOIN usuarios u ON u.id = c.creado_por
             WHERE ' . self::PERMITIDO . '
             ORDER BY p.nombre, c.codigo'
             . ($offset === null ? '' : ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset)
        );
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Dentro de una transacción.
    public static function siguienteNumero(int $proyectoId, string $sigla): int
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, ?) AS UNSIGNED)), 0) + 1
             FROM casos_prueba WHERE proyecto_id = ? AND codigo LIKE ? FOR UPDATE'
        );
        $sql->execute([strlen($sigla) + 2, $proyectoId, $sigla . '-%']);

        return (int) $sql->fetchColumn();
    }

    /** @param array<string, mixed> $caso */
    public static function crear(array $caso): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO casos_prueba (' . implode(', ', array_keys($caso)) . ')
             VALUES (' . implode(', ', array_fill(0, count($caso), '?')) . ')'
        );
        $sql->execute(array_values($caso));

        return (int) Conexion::pdo()->lastInsertId();
    }
}
