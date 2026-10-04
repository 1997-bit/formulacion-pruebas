<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorValidacion;
use App\Core\Validador;
use App\Models\UsuarioModelo;

// RF-02, RF-03. Solo aquí se crea un admin.
final class UsuarioServicio
{
    // Mínimo de OWASP para Argon2id: 19 MiB, 2 pasadas, 1 hilo.
    public const ARGON = ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1];

    // Sin SELECT previo: el UNIQUE decide, también en una carrera (#101).
    public const USUARIO_REPETIDO = ['usuario' => 'Ese usuario ya existe.'];

    /**
     * @param array{id: int, rol: int} $actor
     * @return list<array<string, mixed>>
     */
    public static function listar(array $actor): array
    {
        Permisos::exigirAdmin($actor);

        return UsuarioModelo::listar();
    }

    /**
     * @param array{id: int, rol: int} $actor
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $actor): ?array
    {
        Permisos::exigirAdmin($actor);

        return UsuarioModelo::porId($id);
    }

    /**
     * @param array<string, string> $datos nombre, usuario, clave, rol
     * @param array{id: int, rol: int} $actor
     */
    public static function crear(array $datos, array $actor): string
    {
        Permisos::exigirAdmin($actor);
        $d = self::validar($datos, null);
        try {
            UsuarioModelo::crear($d['nombre'], $d['usuario'], password_hash($datos['clave'], PASSWORD_ARGON2ID, self::ARGON), $d['rol']);
        } catch (\PDOException $e) {
            throw ErrorValidacion::siDuplicado($e, self::USUARIO_REPETIDO);
        }

        return $d['usuario'];
    }

    /**
     * Clave vacía: no la cambia. El rol se cambia aquí.
     *
     * @param array<string, string> $datos nombre, usuario, clave, rol
     * @param array{id: int, rol: int} $actor
     */
    public static function editar(int $id, array $datos, array $actor): string
    {
        Permisos::exigirAdmin($actor);
        $d = self::validar($datos, $id);
        if ($id === $actor['id'] && $d['rol'] !== 1) {
            throw new ErrorValidacion(['rol' => 'No puede quitarse su propio rol de admin.']);
        }
        $clave = $datos['clave'] === '' ? null : password_hash($datos['clave'], PASSWORD_ARGON2ID, self::ARGON);
        try {
            UsuarioModelo::actualizar($id, $d['nombre'], $d['usuario'], $clave, $d['rol']);
        } catch (\PDOException $e) {
            throw ErrorValidacion::siDuplicado($e, self::USUARIO_REPETIDO);
        }

        return $d['usuario'];
    }

    /** @param array{id: int, rol: int} $actor */
    public static function eliminar(int $id, array $actor): void
    {
        Permisos::exigirAdmin($actor);
        if ($id === $actor['id']) {
            throw new ErrorValidacion(['general' => 'No puede eliminar su propia cuenta.']);
        }
        if (!UsuarioModelo::eliminar($id)) {
            throw new ErrorValidacion(['general' => 'Tiene casos, evidencias o formularios a su nombre: no se puede eliminar.']);
        }
    }

    /**
     * Reglas comunes con el registro público. $id: el usuario que se edita.
     *
     * @param array<string, string> $datos
     * @return array{nombre: string, usuario: string, rol: int}
     */
    public static function validar(array $datos, ?int $id): array
    {
        $nombre = trim($datos['nombre']);
        $usuario = trim($datos['usuario']);
        $rol = trim($datos['rol'] ?? '0');
        $nuevo = $id === null;

        (new Validador())
            ->requerido('nombre', $nombre)
            ->regla('nombre', mb_strlen($nombre) <= 100, 'Máximo 100 caracteres.')
            ->requerido('usuario', $usuario)
            ->regla('usuario', mb_strlen($usuario) <= 30, 'Máximo 30 caracteres.')
            ->regla('clave', (!$nuevo && $datos['clave'] === '') || mb_strlen($datos['clave']) >= 8, 'Mínimo 8 caracteres.')
            ->requerido('rol', $rol)
            ->catalogo('rol', 'rol', $rol)
            ->comprobar();

        return ['nombre' => $nombre, 'usuario' => $usuario, 'rol' => (int) $rol];
    }
}
