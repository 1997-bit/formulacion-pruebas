<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\ErrorNoEncontrado;
use App\Core\ErrorValidacion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\FilasModelo;
use App\Models\RequerimientoModelo;

final class RequerimientoServicio
{
    private const FORMATO = '/^RN?F-\d{2,3}$/';

    /**
     * RNF-xx es no funcional.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     * @return array{int, string} id y código
     */
    public static function registrar(array $datos, array $usuario): array
    {
        $proyectoId = trim($datos['proyecto_id']);
        $codigo = strtoupper(trim($datos['codigo']));
        $descripcion = trim($datos['descripcion']);
        $proyectos = array_column(self::proyectos($usuario), null, 'id');
        $formato = (bool) preg_match(self::FORMATO, $codigo);

        (new Validador())
            ->requerido('proyecto_id', $proyectoId)
            ->regla('proyecto_id', $proyectoId === '' || isset($proyectos[$proyectoId]), 'Valor no válido.')
            ->requerido('codigo', $codigo)
            ->regla('codigo', $codigo === '' || $formato, 'Use RF-01 o RNF-01.')
            ->requerido('descripcion', $descripcion)
            ->comprobar();

        try {
            $id = RequerimientoModelo::crear((int) $proyectoId, $codigo, $descripcion, str_starts_with($codigo, 'RNF') ? 1 : 0);
        } catch (\PDOException $e) {
            throw ErrorValidacion::siDuplicado($e, ['codigo' => 'Ese código ya existe en el proyecto.']);
        }

        return [$id, $codigo];
    }

    /**
     * Change request básico: motivo obligatorio y rastro en el historial. Sin cambios no escribe nada.
     * El proyecto no cambia: la llave de casos_prueba lo ata.
     *
     * @param array<string, string> $datos codigo, descripcion y motivo
     * @param array{id: int, rol: int} $usuario
     */
    public static function editar(int $id, array $datos, string $huella, array $usuario): string
    {
        $requerimiento = self::ver($id, $usuario) ?? throw new ErrorNoEncontrado();
        $codigo = strtoupper(trim($datos['codigo']));
        $descripcion = trim($datos['descripcion']);
        $motivo = trim($datos['motivo']);

        (new Validador())
            ->requerido('codigo', $codigo)
            ->regla('codigo', $codigo === '' || preg_match(self::FORMATO, $codigo) === 1, 'Use RF-01 o RNF-01.')
            ->requerido('descripcion', $descripcion)
            ->requerido('motivo', $motivo)
            ->comprobar();

        if ($codigo === $requerimiento['codigo'] && $descripcion === $requerimiento['descripcion']) {
            return $codigo;
        }
        try {
            Conexion::transaccion(function () use ($id, $huella, $requerimiento, $codigo, $descripcion, $motivo, $usuario): void {
                if (FilasModelo::huella(['requerimientos'], 'id', $id) !== $huella) {
                    throw new ErrorValidacion(ErrorValidacion::OTRO_GUARDO);
                }
                RequerimientoModelo::actualizar($id, $codigo, $descripcion, str_starts_with($codigo, 'RNF') ? 1 : 0);
                // El motivo va como un campo más: antes null, después el texto.
                Historial::registrar('requerimientos', $id, $requerimiento + ['motivo' => null], [
                    'codigo' => $codigo, 'descripcion' => $descripcion, 'motivo' => $motivo,
                ], $usuario['id']);
            });
        } catch (\PDOException $e) {
            throw ErrorValidacion::siDuplicado($e, ['codigo' => 'Ese código ya existe en el proyecto.']);
        }

        return $codigo;
    }

    /**
     * Solo admin. Con casos o formularios 2 a 5 no se elimina.
     *
     * @param array{id: int, rol: int} $usuario
     */
    public static function eliminar(int $id, array $usuario): string
    {
        Permisos::exigirAdmin($usuario);
        $requerimiento = RequerimientoModelo::porId($id) ?? throw new ErrorNoEncontrado();
        Conexion::transaccion(function () use ($id): void {
            if (RequerimientoModelo::enUso($id)) {
                throw new ErrorValidacion(['general' => 'Tiene casos o formularios de técnicas: no se puede eliminar.']);
            }
            RequerimientoModelo::eliminar($id);
        });

        return $requerimiento['codigo'];
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function listar(array $usuario): array
    {
        return RequerimientoModelo::todos(Permisos::proyectos($usuario));
    }

    /**
     * RNF-09
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function pagina(array $usuario, int $pagina): array
    {
        $proyectos = Permisos::proyectos($usuario);
        $paginacion = new Paginacion(RequerimientoModelo::contar($proyectos), $pagina);

        return [RequerimientoModelo::pagina($proyectos, $paginacion->offset()), $paginacion];
    }

    /**
     * Matriz por proyecto. Columnas: tipos de prueba con casos en el proyecto.
     * celdas[tipo] = [casos, fault]. Cobertura: celdas con casos sobre filas × columnas.
     *
     * @param array{id: int, rol: int} $usuario
     * @return list<array{id: int, nombre: string, tipos: list<int>, filas: list<array<string, mixed>>, cubiertas: int, total: int, sin_casos: list<string>}>
     */
    public static function matriz(array $usuario): array
    {
        $proyectos = [];
        foreach (RequerimientoModelo::matriz(Permisos::proyectos($usuario)) as $f) {
            $id = (int) $f['proyecto_id'];
            $proyectos[$id] ??= ['id' => $id, 'nombre' => $f['proyecto'], 'tipos' => [], 'filas' => []];
            $proyectos[$id]['filas'][$f['id']] ??= ['id' => (int) $f['id'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'], 'celdas' => []];
            if ($f['tipo_prueba'] !== null) {
                $tipo = (int) $f['tipo_prueba'];
                $proyectos[$id]['tipos'][$tipo] = true;
                $proyectos[$id]['filas'][$f['id']]['celdas'][$tipo] = ['casos' => (int) $f['casos'], 'fault' => (int) $f['fault']];
            }
        }

        return array_values(array_map(static function (array $p): array {
            $p['tipos'] = array_keys($p['tipos']);
            sort($p['tipos']);
            $p['filas'] = array_values($p['filas']);
            $p['cubiertas'] = array_sum(array_map(fn (array $f): int => count($f['celdas']), $p['filas']));
            $p['total'] = count($p['filas']) * count($p['tipos']);
            $p['sin_casos'] = array_column(array_filter($p['filas'], fn (array $f): bool => $f['celdas'] === []), 'codigo');

            return $p;
        }, $proyectos));
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $usuario): ?array
    {
        $requerimiento = RequerimientoModelo::porId($id);
        if ($requerimiento !== null) {
            Permisos::exigirMiembro($usuario, (int) $requerimiento['proyecto_id']);
        }

        return $requerimiento;
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function proyectos(array $usuario): array
    {
        return RequerimientoModelo::proyectosPermitidos($usuario['id'], $usuario['rol'] === 1);
    }
}
