<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Models\IncidenteModelo;
use App\Models\UsuarioModelo;

// RF-17, RF-19
final class IncidenteServicio
{
    /**
     * Formulario 10. El proyecto sale del caso; el código es BUG y un consecutivo del proyecto.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     * @return array{0: int, 1: string} id y código
     */
    public static function registrar(int $casoId, array $datos, array $usuario): array
    {
        $caso = CasoServicio::ver($casoId, $usuario) ?? throw new \DomainException('Caso no encontrado.');
        $d = array_map('trim', $datos);
        $asignables = array_column(self::asignables($caso), 'id');

        $v = (new Validador())
            ->requerido('titulo', $d['titulo'])
            ->regla('titulo', mb_strlen($d['titulo']) <= 150, 'Máximo 150 caracteres.')
            ->requerido('modulo', $d['modulo'])
            ->regla('modulo', mb_strlen($d['modulo']) <= 100, 'Máximo 100 caracteres.')
            ->requerido('severidad', $d['severidad'])
            ->catalogo('severidad', 'severidad', $d['severidad'])
            ->requerido('prioridad', $d['prioridad'])
            ->catalogo('prioridad', 'prioridad', $d['prioridad'])
            ->requerido('descripcion', $d['descripcion'])
            ->requerido('pasos', $d['pasos'])
            ->requerido('resultado_esperado', $d['resultado_esperado'])
            ->requerido('resultado_obtenido', $d['resultado_obtenido']);
        self::validarSeguimiento($v, $d, $asignables);
        $v->comprobar();

        // El bloqueo evita dos códigos iguales; UNIQUE (proyecto_id, codigo) lo respalda.
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $codigo = sprintf('BUG-%03d', IncidenteModelo::siguienteNumero($caso['proyecto_id']));
            $id = IncidenteModelo::crear([
                'proyecto_id' => $caso['proyecto_id'],
                'caso_id' => $casoId,
                'codigo' => $codigo,
                'titulo' => $d['titulo'],
                'modulo' => $d['modulo'],
                'descripcion' => $d['descripcion'],
                'pasos' => $d['pasos'],
                'resultado_esperado' => $d['resultado_esperado'],
                'resultado_obtenido' => $d['resultado_obtenido'],
                'severidad' => (int) $d['severidad'],
                'prioridad' => (int) $d['prioridad'],
                'estado' => (int) $d['estado'],
                'es_stopper' => (int) ($d['es_stopper'] === '1'),
                'asignado_id' => $d['asignado_id'] === '' ? null : (int) $d['asignado_id'],
                'creado_por' => $usuario['id'],
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [$id, $codigo];
    }

    /**
     * Estado, asignado y stopper: cualquiera del proyecto.
     *
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function seguimiento(int $id, array $datos, array $usuario): string
    {
        $incidente = self::ver($id, $usuario) ?? throw new \DomainException('Incidente no encontrado.');
        $d = array_map('trim', $datos);
        $v = new Validador();
        self::validarSeguimiento($v, $d, array_column(self::asignables($incidente), 'id'));
        $v->comprobar();

        IncidenteModelo::actualizar(
            $id,
            (int) $d['estado'],
            $d['asignado_id'] === '' ? null : (int) $d['asignado_id'],
            (int) ($d['es_stopper'] === '1'),
        );

        return $incidente['codigo'];
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function ver(int $id, array $usuario): ?array
    {
        $incidente = IncidenteModelo::porId($id);
        if ($incidente !== null) {
            Permisos::exigirMiembro($usuario, $incidente['proyecto_id']);
        }

        return $incidente;
    }

    /**
     * RNF-09
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{0: list<array<string, mixed>>, 1: Paginacion}
     */
    public static function pagina(array $usuario, int $pagina): array
    {
        $admin = $usuario['rol'] === 1;
        $paginacion = new Paginacion(IncidenteModelo::contar($usuario['id'], $admin), $pagina);

        return [IncidenteModelo::listar($usuario['id'], $admin, $paginacion->offset()), $paginacion];
    }

    /**
     * Miembros del proyecto del caso o del incidente.
     *
     * @param array<string, mixed> $registro
     * @return list<array<string, mixed>>
     */
    public static function asignables(array $registro): array
    {
        return UsuarioModelo::deProyecto($registro['proyecto_id']);
    }

    /**
     * @param array<string, string> $d
     * @param list<int> $asignables
     */
    private static function validarSeguimiento(Validador $v, array $d, array $asignables): void
    {
        $v->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_incidente', $d['estado'])
            ->regla('asignado_id', $d['asignado_id'] === '' || in_array((int) $d['asignado_id'], $asignables, true), 'Valor no válido.');
    }
}
