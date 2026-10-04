<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;
use App\Core\Paginacion;

final class RequerimientoModelo
{
    // Tester: solo sus proyectos.
    private const PERMITIDO = '(? = 1 OR EXISTS (SELECT 1 FROM proyecto_miembros m WHERE m.proyecto_id = p.id AND m.usuario_id = ?))';

    /**
     * $offset null: todos. Orden del índice (proyecto_id, no_funcional, numero): sin filesort.
     * Con $offset, la subconsulta salta solo por el índice y el resto se lee para 20 filas (#107).
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function listar(?array $proyectos, ?int $offset = null): array
    {
        [$condiciones, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $donde = $condiciones === [] ? '' : ' WHERE ' . $condiciones[0];
        $orden = ' ORDER BY r.proyecto_id, r.no_funcional, r.numero';
        if ($offset !== null) {
            $desde = '(SELECT r.id FROM requerimientos r' . $donde . $orden . ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset . ')
                      k JOIN requerimientos r ON r.id = k.id';
            $donde = '';
        }
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.proyecto_id, r.codigo, r.descripcion, r.no_funcional, p.nombre AS proyecto,
                    (SELECT COUNT(*) FROM casos_prueba c WHERE c.requerimiento_id = r.id) AS casos
             FROM ' . ($desde ?? 'requerimientos r') . '
             JOIN proyectos p ON p.id = r.proyecto_id' . $donde . $orden
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @param list<int>|null $proyectos Permisos::proyectos() */
    public static function contar(?array $proyectos): int
    {
        [$condiciones, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM requerimientos r' . ($condiciones === [] ? '' : ' WHERE ' . $condiciones[0]));
        $sql->execute($valores);

        return (int) $sql->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function proyectosPermitidos(int $usuarioId, bool $admin): array
    {
        $sql = Conexion::pdo()->prepare('SELECT p.id, p.nombre FROM proyectos p WHERE ' . self::PERMITIDO . ' ORDER BY p.nombre');
        $sql->execute([(int) $admin, $usuarioId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function porId(int $id): ?array
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT r.*, p.nombre AS proyecto FROM requerimientos r JOIN proyectos p ON p.id = r.proyecto_id WHERE r.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function existe(int $proyectoId, string $codigo): bool
    {
        $sql = Conexion::pdo()->prepare('SELECT 1 FROM requerimientos WHERE proyecto_id = ? AND codigo = ?');
        $sql->execute([$proyectoId, $codigo]);

        return (bool) $sql->fetchColumn();
    }

    public static function crear(int $proyectoId, string $codigo, string $descripcion, int $noFuncional): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO requerimientos (proyecto_id, codigo, descripcion, no_funcional) VALUES (?, ?, ?, ?)'
        );
        $sql->execute([$proyectoId, $codigo, $descripcion, $noFuncional]);

        return (int) Conexion::pdo()->lastInsertId();
    }
}
