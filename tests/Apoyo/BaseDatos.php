<?php

declare(strict_types=1);

namespace Tests\Apoyo;

use App\Config\Conexion;
use App\Core\Env;
use App\Core\ErrorValidacion;
use App\Models\CasoModelo;
use App\Models\IncidenteModelo;
use App\Models\UsuarioModelo;
use PHPUnit\Framework\TestCase;

// Pruebas medianas: base del .env, cada prueba en una transacción que se deshace.
abstract class BaseDatos extends TestCase
{
    private static ?PdoPruebas $pdo = null;

    private static int $incidentes = 0;

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            Env::cargar(RAIZ . '/.env');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST', '127.0.0.1'),
                Env::get('DB_PORT', '3306'),
                Env::get('DB_NAME'),
            );
            self::$pdo = new PdoPruebas($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            (new \ReflectionProperty(Conexion::class, 'pdo'))->setValue(null, self::$pdo);
        }
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$pdo?->rollBack();
    }

    // Fábrica: valores válidos por defecto, nombres únicos.

    protected function usuario(int $rol = 0, string $nombre = 'Usuario'): int
    {
        return UsuarioModelo::crear($nombre, uniqid('u', true), 'x', $rol);
    }

    protected function proyecto(int ...$miembros): int
    {
        $pdo = Conexion::pdo();
        $pdo->prepare('INSERT INTO proyectos (nombre) VALUES (?)')->execute([uniqid('p', true)]);
        $id = (int) $pdo->lastInsertId();
        foreach ($miembros as $m) {
            $pdo->prepare('INSERT INTO proyecto_miembros (proyecto_id, usuario_id) VALUES (?, ?)')->execute([$id, $m]);
        }

        return $id;
    }

    protected function requerimiento(int $proyectoId, string $codigo = 'RF-01'): int
    {
        Conexion::pdo()->prepare('INSERT INTO requerimientos (proyecto_id, codigo, descripcion) VALUES (?, ?, ?)')
            ->execute([$proyectoId, $codigo, 'Requerimiento']);

        return (int) Conexion::pdo()->lastInsertId();
    }

    protected function caso(int $proyectoId, int $requerimientoId, int $autor, string $codigo = 'SIS-001'): int
    {
        return CasoModelo::crear([
            'proyecto_id' => $proyectoId, 'requerimiento_id' => $requerimientoId, 'codigo' => $codigo,
            'tipo_prueba' => 1, 'subtecnica' => 1, 'modulo' => 'Módulo', 'plataforma' => 1,
            'objetivo' => 'Objetivo', 'entrada' => 'Entrada', 'pasos' => 'Pasos', 'resultado_esperado' => 'Resultado',
            'fecha_inicio' => '2026-10-06', 'fecha_fin' => '2026-10-06', 'creado_por' => $autor,
        ]);
    }

    protected function incidente(int $proyectoId, int $casoId, int $autor, int $estado = 0, int $stopper = 0): int
    {
        return IncidenteModelo::crear([
            'proyecto_id' => $proyectoId, 'caso_id' => $casoId, 'codigo' => 'BUG-' . ++self::$incidentes,
            'titulo' => 'Título', 'modulo' => 'Módulo', 'descripcion' => 'Descripción', 'pasos' => 'Pasos',
            'resultado_esperado' => 'Esperado', 'resultado_obtenido' => 'Obtenido',
            'severidad' => 1, 'prioridad' => 1, 'estado' => $estado, 'es_stopper' => $stopper, 'creado_por' => $autor,
        ]);
    }

    /**
     * Como queda en la sesión: con sus proyectos.
     *
     * @return array{id: int, rol: int, proyectos: list<int>}
     */
    protected function sesion(int $usuarioId): array
    {
        $u = UsuarioModelo::deSesion($usuarioId) ?? throw new \LogicException('Usuario no existe.');

        return ['id' => (int) $u['id'], 'rol' => (int) $u['rol'], 'proyectos' => $u['proyectos']];
    }

    protected function contar(string $sql, mixed ...$valores): int
    {
        $consulta = Conexion::pdo()->prepare($sql);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Errores de validación de la acción; [] si pasa.
     *
     * @return array<string, string>
     */
    protected function errores(callable $accion): array
    {
        try {
            $accion();
        } catch (ErrorValidacion $e) {
            return $e->errores;
        }

        return [];
    }
}
