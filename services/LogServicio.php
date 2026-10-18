<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Log;
use App\Core\Paginacion;

// RNF-11. Solo lectura de storage/logs: sin descargar ni borrar, ASVS 16.4.2
final class LogServicio
{
    // Nivel mínimo => texto. Monolog, RFC 5424
    public const NIVELES = [100 => 'Debug', 200 => 'Info', 250 => 'Notice', 300 => 'Warning', 400 => 'Error', 500 => 'Critical'];

    public const CANALES = ['http', 'seguridad', 'app'];

    /**
     * Líneas del día, la más nueva primero. Con uid: todos los días, sin otro filtro
     * El código de la página 500 lleva a su petición
     *
     * @param array{id: int, rol: int} $actor
     * @param array<string, string> $filtros dia, canal, nivel, evento, uid
     * @return array{list<array<string, mixed>>, Paginacion}
     */
    public static function listar(array $actor, array $filtros, int $pagina): array
    {
        self::abrir($actor);
        $f = self::filtros($filtros);
        $lineas = $f['uid'] !== ''
            ? array_filter(self::leer(null), fn (array $l): bool => ($l['extra']['uid'] ?? null) === $f['uid'])
            : array_filter(self::leer($f['dia']), fn (array $l): bool => ($f['canal'] === '' || $l['channel'] === $f['canal'])
                && $l['level'] >= $f['nivel']
                && ($f['evento'] === '' || str_contains($l['message'], $f['evento'])));
        $lineas = array_reverse(array_values($lineas));
        $paginacion = new Paginacion(count($lineas), $pagina);

        return [array_slice($lineas, $paginacion->offset(), Paginacion::POR_PAGINA), $paginacion];
    }

    /**
     * Conteo por evento y login fallido por IP, top 10
     *
     * @param array{id: int, rol: int} $actor
     * @return array{eventos: array<string, int>, ips: array<string, int>}
     */
    public static function seguridad(array $actor, string $dia): array
    {
        self::abrir($actor);
        $eventos = $ips = [];
        foreach (self::leer(self::filtros(['dia' => $dia])['dia']) as $l) {
            if ($l['channel'] !== 'seguridad') {
                continue;
            }
            $evento = strtok($l['message'], ':');
            $eventos[$evento] = ($eventos[$evento] ?? 0) + 1;
            if ($evento === 'authn_login_fail') {
                $ip = (string) ($l['extra']['ip'] ?? '-');
                $ips[$ip] = ($ips[$ip] ?? 0) + 1;
            }
        }
        arsort($eventos);
        arsort($ips);

        return ['eventos' => $eventos, 'ips' => array_slice($ips, 0, 10, true)];
    }

    /**
     * Golden signals por ruta: tráfico, p50, p95 y tasa 5xx
     *
     * @param array{id: int, rol: int} $actor
     * @return list<array{ruta: string, peticiones: int, p50: float, p95: float, errores: float}>
     */
    public static function rendimiento(array $actor, string $dia): array
    {
        self::abrir($actor);
        $rutas = [];
        foreach (self::leer(self::filtros(['dia' => $dia])['dia']) as $l) {
            if ($l['channel'] === 'http') {
                $rutas[(string) $l['context']['ruta']][] = $l['context'];
            }
        }
        ksort($rutas);
        $filas = [];
        foreach ($rutas as $ruta => $peticiones) {
            $tiempos = array_map(fn (array $c): float => (float) $c['duracion_ms'], $peticiones);
            sort($tiempos);
            $n = count($tiempos);
            $filas[] = [
                'ruta' => $ruta,
                'peticiones' => $n,
                'p50' => $tiempos[(int) floor($n * 0.5)],
                'p95' => $tiempos[(int) floor($n * 0.95)],
                'errores' => round(100 * count(array_filter($peticiones, fn (array $c): bool => $c['codigo'] >= 500)) / $n, 1),
            ];
        }

        return $filas;
    }

    /**
     * Días con log, el más nuevo primero
     *
     * @return list<string>
     */
    public static function dias(): array
    {
        $dias = array_map(fn (string $f): string => substr(basename($f, '.log'), 4), self::archivos());
        rsort($dias);

        return $dias;
    }

    /**
     * Día inválido: hoy. Un ../ no sale de storage/logs
     *
     * @param array<string, string> $filtros
     * @return array{dia: string, canal: string, nivel: int, evento: string, uid: string}
     */
    public static function filtros(array $filtros): array
    {
        $dia = $filtros['dia'] ?? '';
        $canal = $filtros['canal'] ?? '';
        $nivel = (int) ($filtros['nivel'] ?? 0);
        $uid = $filtros['uid'] ?? '';

        return [
            'dia' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia) ? $dia : date('Y-m-d'),
            'canal' => in_array($canal, self::CANALES, true) ? $canal : '',
            'nivel' => isset(self::NIVELES[$nivel]) ? $nivel : 0,
            'evento' => trim($filtros['evento'] ?? ''),
            'uid' => preg_match('/^[0-9a-f]{8}$/', $uid) ? $uid : '',
        ];
    }

    // Toda lectura de logs se registra, OWASP Logging Cheat Sheet
    /** @param array{id: int, rol: int} $actor */
    private static function abrir(array $actor): void
    {
        Permisos::exigirAdmin($actor);
        Log::evento('info', 'sensitive_read:{id},logs');
    }

    /**
     * Null: todos los días
     *
     * @return list<array<string, mixed>>
     */
    private static function leer(?string $dia): array
    {
        $archivos = $dia === null ? self::archivos() : array_filter([self::carpeta() . "/app-{$dia}.log"], 'is_file');
        $lineas = [];
        foreach ($archivos as $archivo) {
            foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $texto) {
                $linea = json_decode($texto, true);
                if (is_array($linea)) {
                    $lineas[] = $linea;
                }
            }
        }

        return $lineas;
    }

    /** @return list<string> */
    private static function archivos(): array
    {
        $archivos = glob(self::carpeta() . '/app-*.log') ?: [];
        sort($archivos);

        return $archivos;
    }

    private static function carpeta(): string
    {
        return RAIZ . '/storage/logs';
    }
}
