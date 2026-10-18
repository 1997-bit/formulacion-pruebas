<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Html;
use App\Models\ProyectoModelo;
use App\Models\ReporteModelo;

// RF-23. Panel y cierre usan las mismas cifras
final class ReporteServicio
{
    private const CIFRAS = ['casos', 'pendientes', 'ok', 'fault', 'casos_hoy', 'ok_hoy', 'fault_hoy', 'sin_evidencia', 'fault_sin_incidente', 'incidentes_abiertos', 'stoppers', 'sin_asignar'];

    /**
     * Totales por proyecto y del conjunto, con criterios Go / No-Go
     *
     * @param array{id: int, rol: int, activo?: ?int} $usuario
     * @return array{total: array<string, int>, proyectos: list<array<string, mixed>>}
     */
    public static function avance(array $usuario): array
    {
        $proyectos = array_map(
            self::evaluar(...),
            ReporteModelo::porProyecto($usuario['id'], $usuario['rol'] === 1, $usuario['activo'] ?? null),
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
     * Null si no existe
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
        $proyecto['graficos'] = self::graficos($proyectoId);

        return $proyecto;
    }

    /**
     * Series por día. Del primer al último dato, máximo 60 días. Null sin datos
     * plan_acum: casos con fecha final hasta el día. real_acum: anotados hasta el día, null después de hoy
     *
     * @return array<string, mixed>|null
     */
    private static function graficos(int $proyectoId): ?array
    {
        $casos = ReporteModelo::casosPorDia($proyectoId);
        $incidentes = ReporteModelo::incidentesPorDia($proyectoId);
        $fechas = [...array_keys($casos['plan']), ...array_keys($casos['anotados']), ...array_column($incidentes, 'dia')];
        if ($fechas === []) {
            return null;
        }

        $hoy = date('Y-m-d');
        // Hasta hoy o el último dato: el avance se mide a hoy
        $hasta = new \DateTimeImmutable(max([...$fechas, $hoy]));
        $desde = max(new \DateTimeImmutable(min($fechas)), $hasta->modify('-59 days'));
        // Lo anterior al rango cuenta en los acumulados
        $antes = fn (array $porDia): int => array_sum(array_filter($porDia, fn (string $d): bool => $d < $desde->format('Y-m-d'), ARRAY_FILTER_USE_KEY));
        $plan = $antes($casos['plan']);
        $real = $antes($casos['anotados']);

        $porDia = [];
        foreach ($incidentes as $i) {
            $porDia[$i['dia']]['severidad'][$i['severidad']] = ($porDia[$i['dia']]['severidad'][$i['severidad']] ?? 0) + $i['n'];
            $porDia[$i['dia']]['stoppers'] = ($porDia[$i['dia']]['stoppers'] ?? 0) + $i['n'] * $i['es_stopper'];
        }

        $dias = [];
        for ($d = $desde; $d <= $hasta; $d = $d->modify('+1 day')) {
            $dia = $d->format('Y-m-d');
            $anotados = $casos['anotados'][$dia] ?? 0;
            $plan += $casos['plan'][$dia] ?? 0;
            $real += $anotados;
            $dias[] = [
                'dia' => $dia,
                'anotados' => $anotados,
                'plan_acum' => $plan,
                'real_acum' => $dia <= $hoy ? $real : null,
                'severidad' => ($porDia[$dia]['severidad'] ?? []) + [1 => 0, 2 => 0, 3 => 0, 4 => 0],
                'stoppers' => $porDia[$dia]['stoppers'] ?? 0,
            ];
        }

        $pasados = array_values(array_filter($dias, fn (array $d): bool => $d['real_acum'] !== null));
        $pico = $dias[array_search(max(array_column($dias, 'anotados')), array_column($dias, 'anotados'), true)];
        $ultimo = end($pasados);

        return [
            'dias' => $dias,
            'pico' => $pico,
            'promedio' => $pasados === [] ? 0.0 : round(array_sum(array_column($pasados, 'anotados')) / count($pasados), 1),
            'sin_anotar' => count(array_filter($pasados, fn (array $d): bool => $d['anotados'] === 0)),
            'diferencia' => $ultimo === false ? 0 : $ultimo['real_acum'] - $ultimo['plan_acum'],
            'incidentes' => array_sum(array_column($incidentes, 'n')),
            'horas_cierre' => ReporteModelo::horasDeCierre($proyectoId),
        ];
    }

    /**
     * Go: hay casos y se cumplen todos los criterios
     *
     * @param array<string, mixed> $p
     * @return array<string, mixed>
     */
    private static function evaluar(array $p): array
    {
        $p['criterios'] = [
            ['texto' => 'Todos los casos tienen resultado', 'cumple' => $p['pendientes'] === 0, 'detalle' => Html::cantidad($p['pendientes'], 'pendiente', 'pendientes')],
            ['texto' => 'Los casos con resultado tienen evidencia', 'cumple' => $p['sin_evidencia'] === 0, 'detalle' => "{$p['sin_evidencia']} sin evidencia"],
            ['texto' => 'Los casos FAULT tienen incidente', 'cumple' => $p['fault_sin_incidente'] === 0, 'detalle' => "{$p['fault_sin_incidente']} sin incidente"],
            ['texto' => 'Sin incidentes stopper abiertos', 'cumple' => $p['stoppers'] === 0, 'detalle' => Html::cantidad($p['stoppers'], 'abierto', 'abiertos')],
        ];
        $ejecutados = $p['ok'] + $p['fault'];
        $p['aprobacion'] = $ejecutados === 0 ? 0 : (int) round($p['ok'] / $ejecutados * 100);
        $p['go'] = $p['casos'] > 0 && !in_array(false, array_column($p['criterios'], 'cumple'), true);

        // Ritmo de los últimos 7 días. Sin ritmo o sin pendientes: sin fecha
        $p['ritmo'] = round($p['anotados_7d'] / 7, 1);
        $p['fin_estimado'] = $p['pendientes'] === 0 || $p['anotados_7d'] === 0
            ? null
            : (new \DateTimeImmutable('today'))->modify('+' . (int) ceil($p['pendientes'] * 7 / $p['anotados_7d']) . ' days')->format('Y-m-d');

        return $p;
    }
}
