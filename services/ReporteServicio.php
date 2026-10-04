<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProyectoModelo;
use App\Models\ReporteModelo;

// RF-23. El panel y el cierre salen de las mismas cifras.
final class ReporteServicio
{
    private const CIFRAS = ['casos', 'pendientes', 'ok', 'fault', 'sin_evidencia', 'incidentes_abiertos', 'stoppers'];

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
            ['texto' => 'Todos los casos tienen evidencia', 'cumple' => $p['sin_evidencia'] === 0, 'detalle' => "{$p['sin_evidencia']} sin evidencia"],
            ['texto' => 'Sin incidentes stopper abiertos', 'cumple' => $p['stoppers'] === 0, 'detalle' => "{$p['stoppers']} abierto(s)"],
        ];
        $p['go'] = $p['casos'] > 0 && !in_array(false, array_column($p['criterios'], 'cumple'), true);

        return $p;
    }
}
