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
     * Todos, para los selects. Orden del índice proyecto_id, no_funcional, numero.
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function todos(?array $proyectos): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.proyecto_id, r.codigo, r.descripcion, r.no_funcional, p.nombre AS proyecto
             FROM requerimientos r JOIN proyectos p ON p.id = r.proyecto_id
             WHERE ' . $permiso . ' ORDER BY r.proyecto_id, r.no_funcional, r.numero'
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Una página con el conteo de casos: 20 conteos por índice. #108.
     * La subconsulta lee solo el índice, #107.
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function pagina(?array $proyectos, int $offset): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $orden = ' ORDER BY r.proyecto_id, r.no_funcional, r.numero';
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.proyecto_id, r.codigo, r.descripcion, r.no_funcional, p.nombre AS proyecto,
                    (SELECT COUNT(*) FROM casos_prueba c WHERE c.requerimiento_id = r.id) AS casos
             FROM (SELECT r.id FROM requerimientos r WHERE ' . $permiso . $orden . ' LIMIT ' . Paginacion::POR_PAGINA . ' OFFSET ' . $offset . ') k
             JOIN requerimientos r ON r.id = k.id
             JOIN proyectos p ON p.id = r.proyecto_id' . $orden
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Matriz de cobertura: una fila por requerimiento y tipo de prueba. Sin casos: tipo_prueba null.
     *
     * @param list<int>|null $proyectos Permisos::proyectos()
     * @return list<array<string, mixed>>
     */
    public static function matriz(?array $proyectos): array
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare(
            'SELECT r.id, r.proyecto_id, p.nombre AS proyecto, r.codigo, r.descripcion, c.tipo_prueba,
                    COUNT(c.id) AS casos, COALESCE(SUM(c.estado = 2), 0) AS fault
             FROM requerimientos r
             JOIN proyectos p ON p.id = r.proyecto_id
             LEFT JOIN casos_prueba c ON c.requerimiento_id = r.id
             WHERE ' . $permiso . '
             GROUP BY r.id, c.tipo_prueba
             ORDER BY p.nombre, r.no_funcional, r.numero'
        );
        $sql->execute($valores);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @param list<int>|null $proyectos Permisos::proyectos() */
    public static function contar(?array $proyectos): int
    {
        [$permiso, $valores] = ProyectoModelo::permitidos('r.proyecto_id', $proyectos);
        $sql = Conexion::pdo()->prepare('SELECT COUNT(*) FROM requerimientos r WHERE ' . $permiso);
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

    public static function crear(int $proyectoId, string $codigo, string $descripcion, int $noFuncional): int
    {
        $sql = Conexion::pdo()->prepare(
            'INSERT INTO requerimientos (proyecto_id, codigo, descripcion, no_funcional) VALUES (?, ?, ?, ?)'
        );
        $sql->execute([$proyectoId, $codigo, $descripcion, $noFuncional]);

        return (int) Conexion::pdo()->lastInsertId();
    }

    public static function actualizar(int $id, string $codigo, string $descripcion, int $noFuncional): void
    {
        Conexion::pdo()->prepare('UPDATE requerimientos SET codigo = ?, descripcion = ?, no_funcional = ? WHERE id = ?')
            ->execute([$codigo, $descripcion, $noFuncional, $id]);
    }

    /**
     * Casos que validan el requerimiento: el impacto del cambio.
     *
     * @return list<array<string, mixed>>
     */
    public static function casos(int $id): array
    {
        $sql = Conexion::pdo()->prepare('SELECT id, codigo, estado FROM casos_prueba WHERE requerimiento_id = ? ORDER BY codigo');
        $sql->execute([$id]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Casos o filas de los formularios 2 a 5. Los formularios borran en cascada: sin este control se pierden.
    public static function enUso(int $id): bool
    {
        $sql = Conexion::pdo()->prepare(
            'SELECT EXISTS (SELECT 1 FROM casos_prueba WHERE requerimiento_id = ?)
                 OR EXISTS (SELECT 1 FROM clases_equivalencia WHERE requerimiento_id = ?)
                 OR EXISTS (SELECT 1 FROM valor_limite WHERE requerimiento_id = ?)
                 OR EXISTS (SELECT 1 FROM decision_filas WHERE requerimiento_id = ?)
                 OR EXISTS (SELECT 1 FROM cobertura_blanca WHERE requerimiento_id = ?)'
        );
        $sql->execute(array_fill(0, 5, $id));

        return (bool) $sql->fetchColumn();
    }

    // El historial queda, ASVS 16.4.2.
    public static function eliminar(int $id): void
    {
        Conexion::pdo()->prepare('DELETE FROM requerimientos WHERE id = ?')->execute([$id]);
    }
}
