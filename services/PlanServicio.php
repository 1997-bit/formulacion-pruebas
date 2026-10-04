<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\Validador;
use App\Models\IncidenteModelo;
use App\Models\PlanModelo;
use App\Models\ProyectoModelo;
use App\Models\RequerimientoModelo;
use App\Models\UsuarioModelo;

// RF-13. Formulario 6: un plan por proyecto; lo llena cualquiera del proyecto.
final class PlanServicio
{
    public const TEXTOS = ['alcance', 'objetivos', 'recursos', 'cronograma', 'criterios_aceptacion', 'riesgos'];
    private const OBLIGATORIOS = ['alcance', 'objetivos', 'criterios_aceptacion'];
    private const ESTADO_CERRADO = 2;

    /**
     * Proyectos del usuario con el estado de su plan; null si no tiene.
     *
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function proyectos(array $usuario): array
    {
        $proyectos = RequerimientoModelo::proyectosPermitidos($usuario['id'], $usuario['rol'] === 1);
        $estados = PlanModelo::estados();
        foreach ($proyectos as &$p) {
            $p['estado'] = $estados[$p['id']] ?? null;
        }
        unset($p);

        return $proyectos;
    }

    /**
     * Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function proyecto(int $id, array $usuario): ?array
    {
        $proyecto = ProyectoModelo::porId($id);
        if ($proyecto !== null) {
            Permisos::exigirMiembro($usuario, $id);
        }

        return $proyecto;
    }

    /**
     * Null si el proyecto aún no tiene plan.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function plan(int $proyectoId, array $usuario): ?array
    {
        Permisos::exigirMiembro($usuario, $proyectoId);

        return PlanModelo::deProyecto($proyectoId);
    }

    /**
     * El responsable sale de aquí.
     *
     * @return list<array<string, mixed>>
     */
    public static function miembros(int $proyectoId): array
    {
        return UsuarioModelo::deProyecto($proyectoId);
    }

    /**
     * @param array<string, string> $datos
     * @param array{id: int, rol: int} $usuario
     */
    public static function guardar(int $proyectoId, array $datos, array $usuario): void
    {
        Permisos::exigirMiembro($usuario, $proyectoId);

        $d = array_map('trim', $datos);
        $miembros = array_map(intval(...), array_column(self::miembros($proyectoId), 'id'));
        $v = (new Validador())
            ->requerido('version', $d['version'])
            ->regla('version', mb_strlen($d['version']) <= 20, 'Máximo 20 caracteres.')
            ->requerido('responsable_id', $d['responsable_id'])
            ->regla('responsable_id', $d['responsable_id'] === '' || in_array((int) $d['responsable_id'], $miembros, true), 'Elija un miembro del proyecto.')
            ->requerido('fecha', $d['fecha'])
            ->fecha('fecha', $d['fecha'])
            ->requerido('estrategia', $d['estrategia'])
            ->catalogo('estrategia', 'estrategia', $d['estrategia'])
            ->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_plan', $d['estado']);
        foreach (self::TEXTOS as $campo) {
            if (in_array($campo, self::OBLIGATORIOS, true)) {
                $v->requerido($campo, $d[$campo]);
            }
            $v->regla($campo, mb_strlen($d[$campo]) <= 5000, 'Máximo 5000 caracteres.');
        }
        if ($d['estado'] === (string) self::ESTADO_CERRADO) {
            $stoppers = IncidenteModelo::stoppersAbiertos($proyectoId);
            $v->regla('estado', $stoppers === 0, "No se cierra: hay {$stoppers} incidente(s) stopper sin cerrar.");
        }
        $v->comprobar();

        $opcional = fn (string $campo): ?string => $d[$campo] === '' ? null : $d[$campo];
        $plan = [
            'version' => $d['version'],
            'responsable_id' => (int) $d['responsable_id'],
            'fecha' => $d['fecha'],
            'alcance' => $d['alcance'],
            'objetivos' => $d['objetivos'],
            'estrategia' => (int) $d['estrategia'],
            'recursos' => $opcional('recursos'),
            'cronograma' => $opcional('cronograma'),
            'criterios_aceptacion' => $d['criterios_aceptacion'],
            'riesgos' => $opcional('riesgos'),
            'estado' => (int) $d['estado'],
        ];
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            // Al editar, deja rastro de lo cambiado (#115). Crear no deja rastro.
            $antes = PlanModelo::deProyecto($proyectoId);
            PlanModelo::guardar($proyectoId, $plan);
            if ($antes !== null) {
                Historial::registrar('plan_pruebas', $proyectoId, $antes, $plan, $usuario['id']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
