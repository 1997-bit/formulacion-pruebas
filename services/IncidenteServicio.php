<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Conexion;
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
     */
    public static function registrar(int $casoId, array $datos, array $usuario): string
    {
        $caso = CasoServicio::ver($casoId, $usuario) ?? throw new \DomainException('Caso no encontrado.');
        $d = array_map('trim', $datos);
        $asignables = array_column(self::asignables($caso), 'id');

        (new Validador())
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
            ->requerido('resultado_obtenido', $d['resultado_obtenido'])
            ->requerido('estado', $d['estado'])
            ->catalogo('estado', 'estado_incidente', $d['estado'])
            ->regla('asignado_id', $d['asignado_id'] === '' || in_array((int) $d['asignado_id'], $asignables, true), 'Valor no válido.')
            ->comprobar();

        // El bloqueo evita dos códigos iguales; UNIQUE (proyecto_id, codigo) lo respalda.
        $pdo = Conexion::pdo();
        $pdo->beginTransaction();
        try {
            $codigo = sprintf('BUG-%03d', IncidenteModelo::siguienteNumero($caso['proyecto_id']));
            IncidenteModelo::crear([
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

        return $codigo;
    }

    /**
     * Miembros del proyecto del caso.
     *
     * @param array<string, mixed> $caso
     * @return list<array<string, mixed>>
     */
    public static function asignables(array $caso): array
    {
        return UsuarioModelo::deProyecto($caso['proyecto_id']);
    }
}
