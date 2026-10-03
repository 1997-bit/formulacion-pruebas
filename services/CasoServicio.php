<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
use App\Core\Validador;
use App\Helpers\Catalogo;
use App\Models\CasoModelo;
use App\Models\EvidenciaModelo;

// RF-04, RF-05, RF-24
final class CasoServicio
{
    // Extensión => tipo de evidencia; enlace es 4 (RNF-03).
    private const EXTENSIONES = ['png' => 1, 'jpg' => 1, 'jpeg' => 1, 'txt' => 2, 'log' => 2, 'pdf' => 3];
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * La técnica no se guarda: sale de la sub-técnica.
     *
     * @param array<string, string> $datos
     * @param array{name: string, tmp_name: string, size: int, error: int}|null $archivo
     * @param array{id: int, rol: int} $usuario
     */
    public static function registrar(array $datos, ?array $archivo, array $usuario): string
    {
        $d = array_map('trim', $datos);
        $requerimientos = array_column(CasoModelo::requerimientosPermitidos($usuario['id'], $usuario['rol'] === 1), null, 'id');
        $hayArchivo = $archivo !== null && $archivo['error'] !== UPLOAD_ERR_NO_FILE;
        $extension = $hayArchivo ? strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)) : '';

        (new Validador())
            ->requerido('requerimiento_id', $d['requerimiento_id'])
            ->regla('requerimiento_id', $d['requerimiento_id'] === '' || isset($requerimientos[$d['requerimiento_id']]), 'Valor no válido.')
            ->requerido('tipo_prueba', $d['tipo_prueba'])
            ->catalogo('tipo_prueba', 'tipo_prueba', $d['tipo_prueba'])
            ->requerido('tecnica', $d['tecnica'])
            ->regla('tecnica', in_array($d['tecnica'], ['', '1', '2'], true), 'Valor no válido.')
            ->requerido('subtecnica', $d['subtecnica'])
            ->catalogo('subtecnica', 'subtecnica', $d['subtecnica'])
            ->regla('subtecnica', $d['tecnica'] === '' || $d['subtecnica'] === '' || ((int) $d['subtecnica'] > 10 ? '2' : '1') === $d['tecnica'], 'No es de la técnica elegida.')
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
            ->requerido('estado', $d['estado'])
            ->regla('estado', in_array($d['estado'], ['', '1', '2'], true), 'Valor no válido.')
            ->regla('evidencia', $hayArchivo || $d['evidencia_enlace'] !== '', 'Suba un archivo o escriba un enlace.')
            ->regla('evidencia', !$hayArchivo || !in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true), 'Máximo 5 MB.')
            ->regla('evidencia', !$hayArchivo || $archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] <= self::MAX_BYTES, 'Máximo 5 MB.')
            ->regla('evidencia', !$hayArchivo || $archivo['error'] === UPLOAD_ERR_OK, 'No se pudo subir el archivo.')
            ->regla('evidencia', !$hayArchivo || isset(self::EXTENSIONES[$extension]), 'Solo PNG, JPG, PDF, TXT o LOG.')
            ->regla('evidencia_enlace', $d['evidencia_enlace'] === '' || (filter_var($d['evidencia_enlace'], FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $d['evidencia_enlace'])), 'Escriba un enlace que empiece con http:// o https://.')
            ->regla('evidencia_enlace', mb_strlen($d['evidencia_enlace']) <= 500, 'Máximo 500 caracteres.')
            ->requerido('evidencia_descripcion', $d['evidencia_descripcion'])
            ->regla('evidencia_descripcion', mb_strlen($d['evidencia_descripcion']) <= 255, 'Máximo 255 caracteres.')
            ->comprobar();

        $proyectoId = (int) $requerimientos[$d['requerimiento_id']]['proyecto_id'];
        $sigla = Catalogo::valores('tipo_prueba')[(int) $d['tipo_prueba']]['sigla'] ?? 'CP';
        // RNF-03
        $guardado = $hayArchivo ? bin2hex(random_bytes(16)) . '.' . $extension : null;
        $destino = $guardado !== null ? RAIZ . '/storage/evidencias/' . $guardado : null;

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
                'anotado_por' => $usuario['id'],
                'anotado_en' => date('Y-m-d H:i:s'),
            ]);
            if ($destino !== null) {
                if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
                    throw new \RuntimeException('No se pudo guardar la evidencia.');
                }
                EvidenciaModelo::crear($casoId, self::EXTENSIONES[$extension], $guardado, mb_substr($archivo['name'], 0, 255), null, $d['evidencia_descripcion'], $usuario['id']);
            }
            if ($d['evidencia_enlace'] !== '') {
                EvidenciaModelo::crear($casoId, 4, null, null, $d['evidencia_enlace'], $d['evidencia_descripcion'], $usuario['id']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            if ($destino !== null && is_file($destino)) {
                unlink($destino);
            }
            throw $e;
        }

        return $codigo;
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function listar(array $usuario): array
    {
        return CasoModelo::listar($usuario['id'], $usuario['rol'] === 1);
    }

    /**
     * @param array{id: int, rol: int} $usuario
     * @return list<array<string, mixed>>
     */
    public static function requerimientos(array $usuario): array
    {
        return CasoModelo::requerimientosPermitidos($usuario['id'], $usuario['rol'] === 1);
    }
}
