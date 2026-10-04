<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProyectoModelo;
use App\Models\ReporteModelo;

// RF-23. El panel y el cierre salen de las mismas cifras.
final class ReporteServicio
{
    private const CIFRAS = ['casos', 'pendientes', 'ok', 'fault', 'sin_evidencia', 'fault_sin_incidente', 'incidentes_abiertos', 'stoppers'];

    /**
     * Totales por proyecto y del conjunto. Cada proyecto lleva sus criterios Go / No-Go.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array{total: array<string, int>, proyectos: list<array<string, mixed>>}
     */
    public static function avance(array $usuario): array
    {
        $proyectos = array_map(
            self::evaluar(...),
            ReporteModelo::porProyecto($usuario['id'], $usuario['rol'] === 1),
        );
        $total = [];
        foreach (self::CIFRAS as $cifra) {
            $total[$cifra] = array_sum(array_column($proyectos, $cifra));
        }

        $ejecutados = $total['ok'] + $total['fault'];
        $total['aprobacion'] = $ejecutados === 0 ? 0 : (int) round($total['ok'] / $ejecutados * 100);

        return ['total' => $total, 'proyectos' => $proyectos];
    }

    /**
     * Cierre de un proyecto. Null si no existe.
     *
     * @param array{id: int, rol: int} $usuario
     * @return array<string, mixed>|null
     */
    public static function cierre(int $proyectoId, array $usuario): ?array
    {
        if (ProyectoModelo::porId($proyectoId) === null) {
            return null;
        }
        Permisos::exigirMiembro($usuario, $proyectoId);

        $proyecto = self::evaluar(ReporteModelo::porProyecto($usuario['id'], $usuario['rol'] === 1, $proyectoId)[0]);
        $proyecto['casos_sin_evidencia'] = ReporteModelo::casosSinEvidencia($proyectoId);
        $proyecto['casos_fault_sin_incidente'] = ReporteModelo::faultSinIncidente($proyectoId);
        $proyecto['stoppers_abiertos'] = ReporteModelo::stoppersAbiertos($proyectoId);

        return $proyecto;
    }

    /**
     * Go si hay casos y se cumplen todos los criterios.
     *
     * @param array<string, mixed> $p
     * @return array<string, mixed>
     */
    private static function evaluar(array $p): array
    {
        $p['criterios'] = [
            ['texto' => 'Todos los casos tienen resultado', 'cumple' => $p['pendientes'] === 0, 'detalle' => "{$p['pendientes']} pendiente(s)"],
            ['texto' => 'Los casos con resultado tienen evidencia', 'cumple' => $p['sin_evidencia'] === 0, 'detalle' => "{$p['sin_evidencia']} sin evidencia"],
            ['texto' => 'Los casos FAULT tienen incidente', 'cumple' => $p['fault_sin_incidente'] === 0, 'detalle' => "{$p['fault_sin_incidente']} sin incidente"],
            ['texto' => 'Sin incidentes stopper abiertos', 'cumple' => $p['stoppers'] === 0, 'detalle' => "{$p['stoppers']} abierto(s)"],
        ];
        $ejecutados = $p['ok'] + $p['fault'];
        $p['aprobacion'] = $ejecutados === 0 ? 0 : (int) round($p['ok'] / $ejecutados * 100);
        $p['go'] = $p['casos'] > 0 && !in_array(false, array_column($p['criterios'], 'cumple'), true);

        return $p;
    }
}
