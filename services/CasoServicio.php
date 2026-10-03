<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\Paginacion;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\CasoModelo;
use App\Models\EvidenciaModelo;
use App\Models\RequerimientoModelo;

// RF-04, RF-05, RF-24
final class CasoServicio
{
    // Tipo de evidencia => campo del formulario y extensiones permitidas (RNF-03).
    public const EVIDENCIAS = [
        1 => ['captura', ['png', 'jpg', 'jpeg']],
        2 => ['log', ['txt', 'log']],
        4 => ['enlace', []],
    ];
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Formulario 1. El proyecto sale del requerimiento y la técnica de la sub-técnica.
     *
     * @param array<string, string> $datos
     * @param list<string> $marcadas tipos de evidencia marcados
     * @param array<string, array{name: string, tmp_name: string, size: int, error: int}> $archivos por campo: captura, log
     * @param array{id: int, rol: int} $usuario
     */
    public static function registrar(array $datos, array $marcadas, array $archivos, array $usuario): string
    {
        $d = array_map('trim', $datos);
        $requerimientos = array_column(RequerimientoModelo::listar($usuario['id'], $usuario['rol'] === 1), null, 'id');
        $requerimiento = $requerimientos[$d['requerimiento_id']] ?? null;
        $tipos = array_values(array_intersect(array_keys(self::EVIDENCIAS), array_map('intval', $marcadas)));
        $anotado = in_array($d['estado'], ['1', '2'], true);

        $v = (new Validador())
            ->requerido('requerimiento_id', $d['requerimiento_id'])
            ->regla('requerimiento_id', $d['requerimiento_id'] === '' || $requerimiento !== null, 'Valor no válido.')
            ->requerido('tipo_prueba', $d['tipo_prueba'])
            ->catalogo('tipo_prueba', 'tipo_prueba', $d['tipo_prueba'])
            ->requerido('subtecnica', $d['subtecnica'])
            ->catalogo('subtecnica', 'subtecnica', $d['subtecnica'])
            ->requerido('modulo', $d['modulo'])
            ->regla('modulo', mb_strlen($d['modulo']) <= 100, 'Máximo 100 caracteres.')
            ->requerido('plataforma', $d['plataforma'])
            ->catalogo('plataforma', 'plataforma', $d['plataforma'])
            ->regla('entorno', mb_strlen($d['entorno']) <= 255, 'Máximo 255 caracteres.')
            ->requerido('objetivo', $d['objetivo'])
            ->requerido('entrada', $d['entrada'])
            ->requerido('pasos', $d['pasos'])
            ->requerido('resultado_esperado', $d['resultado_esperado'])
            ->requerido('fecha_inicio', $d['fecha_inicio'])
            ->fecha('fecha_inicio', $d['fecha_inicio'])
            ->requerido('fecha_fin', $d['fecha_fin'])
            ->fecha('fecha_fin', $d['fecha_fin'])
            ->regla('fecha_fin', $d['fecha_fin'] >= $d['fecha_inicio'], 'No puede ser anterior a la fecha de inicio.')
            // RF-24: con OK o FAULT lleva resultado, observaciones y al menos una evidencia.
            ->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_caso', $d['estado'])
            ->regla('resultado_obtenido', !$anotado || $d['resultado_obtenido'] !== '', 'Es obligatorio con OK o FAULT.')
            ->regla('observaciones', !$anotado || $d['observaciones'] !== '', 'Es obligatorio con OK o FAULT.')
            ->regla('evidencias', !$anotado || $tipos !== [], 'Con OK o FAULT hace falta al menos una.');

        foreach ($tipos as $tipo) {
            [$nombre, $extensiones] = self::EVIDENCIAS[$tipo];
            $campo = 'evidencia_' . $nombre;
            if ($extensiones === []) {
                $v->requerido($campo, $d[$campo])
                    ->regla($campo, $d[$campo] === '' || (filter_var($d[$campo], FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $d[$campo])), 'Escriba un enlace que empiece con http:// o https://.')
                    ->regla($campo, mb_strlen($d[$campo]) <= 500, 'Máximo 500 caracteres.');
            } else {
                $error = $archivos[$nombre]['error'] ?? UPLOAD_ERR_NO_FILE;
                $extension = strtolower(pathinfo($archivos[$nombre]['name'] ?? '', PATHINFO_EXTENSION));
                $v->regla($campo, $error !== UPLOAD_ERR_NO_FILE, 'Es obligatorio.')
                    ->regla($campo, !in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) && ($archivos[$nombre]['size'] ?? 0) <= self::MAX_BYTES, 'Máximo 5 MB.')
                    ->regla($campo, in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true), 'No se pudo subir el archivo.')
                    ->regla($campo, $error !== UPLOAD_ERR_OK || in_array($extension, $extensiones, true), 'Solo ' . strtoupper(implode(', ', $extensiones)) . '.');
            }
            $v->requerido('descripcion_' . $nombre, $d['descripcion_' . $nombre])
                ->regla('descripcion_' . $nombre, mb_strlen($d['descripcion_' . $nombre]) <= 255, 'Máximo 255 caracteres.');
        }
        $v->comprobar();

        $proyectoId = (int) $requerimiento['proyecto_id'];
        $sigla = Catalogo::valores('tipo_prueba')[(int) $d['tipo_prueba']]['sigla'];
        $guardados = [];

        // El bloqueo evita dos códigos iguales; UNIQUE (proyecto_id, codigo) lo respalda.
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $codigo = sprintf('%s-%03d', $sigla, CasoModelo::siguienteNumero($proyectoId, $sigla));
            $casoId = CasoModelo::crear([
                'proyecto_id' => $proyectoId,
                'requerimiento_id' => (int) $d['requerimiento_id'],
                'codigo' => $codigo,
                'tipo_prueba' => (int) $d['tipo_prueba'],
                'subtecnica' => (int) $d['subtecnica'],
                'modulo' => $d['modulo'],
                'plataforma' => (int) $d['plataforma'],
                'entorno' => $d['entorno'] ?: null,
                'objetivo' => $d['objetivo'],
                'precondiciones' => $d['precondiciones'] ?: null,
                'entrada' => $d['entrada'],
                'pasos' => $d['pasos'],
                'resultado_esperado' => $d['resultado_esperado'],
                'fecha_inicio' => $d['fecha_inicio'],
                'fecha_fin' => $d['fecha_fin'],
                'estado' => (int) $d['estado'],
                'resultado_obtenido' => $d['resultado_obtenido'] ?: null,
                'observaciones' => $d['observaciones'] ?: null,
                'creado_por' => $usuario['id'],
                'anotado_por' => $anotado ? $usuario['id'] : null,
                'anotado_en' => $anotado ? date('Y-m-d H:i:s') : null,
            ]);
            foreach ($tipos as $tipo) {
                $nombre = self::EVIDENCIAS[$tipo][0];
                $descripcion = $d['descripcion_' . $nombre];
                if ($tipo === 4) {
                    EvidenciaModelo::crear($casoId, $tipo, null, null, $d['evidencia_enlace'], $descripcion, $usuario['id']);
                    continue;
                }
                // RNF-03: nombre al azar, fuera de public/.
                $archivo = $archivos[$nombre];
                $guardado = bin2hex(random_bytes(16)) . '.' . strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                $destino = RAIZ . '/storage/evidencias/' . $guardado;
                if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
                    throw new \RuntimeException('No se pudo guardar la evidencia.');
                }
                $guardados[] = $destino;
                EvidenciaModelo::crear($casoId, $tipo, $guardado, mb_substr($archivo['name'], 0, 255), null, $descripcion, $usuario['id']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            array_map('unlink', $guardados);
            throw $e;
        }

        return $codigo;
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
        $paginacion = new Paginacion(CasoModelo::contar($usuario['id'], $admin), $pagina);

        return [CasoModelo::listar($usuario['id'], $admin, $paginacion->offset()), $paginacion];
    }
}
