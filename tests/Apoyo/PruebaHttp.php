<?php

declare(strict_types=1);

namespace Tests\Apoyo;

use App\Config\Conexion;
use App\Core\Env;
use App\Models\CasoModelo;
use App\Models\UsuarioModelo;
use App\Services\UsuarioServicio;
use PHPUnit\Framework\TestCase;

// Pruebas grandes: php -S con la base del .env. El servidor no ve un ROLLBACK de la prueba:
// los datos llevan nombres únicos y se borran al final de la clase.
abstract class PruebaHttp extends TestCase
{
    protected const CLAVE = 'clave-de-prueba';

    /** @var resource|null */
    private static $servidor = null;

    private static string $base = '';

    private static string $hash = '';

    /** @var array<int, string> id => usuario */
    private static array $usuarios = [];

    /** @var list<int> */
    private static array $proyectos = [];

    /** @var list<string> */
    private static array $archivos = [];

    public static function setUpBeforeClass(): void
    {
        if (self::$servidor !== null) {
            return;
        }
        Env::cargar(RAIZ . '/.env');
        self::$hash = password_hash(self::CLAVE, PASSWORD_ARGON2ID, UsuarioServicio::ARGON);

        // Un puerto libre que da el sistema.
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $direccion = stream_socket_get_name($socket, false);
        fclose($socket);
        self::$base = 'http://' . $direccion;

        $nulo = ['file', '/dev/null', 'w'];
        $servidor = proc_open([PHP_BINARY, '-S', $direccion, '-t', RAIZ . '/public', RAIZ . '/public/index.php'], [1 => $nulo, 2 => $nulo], $tubos);
        self::$servidor = $servidor ?: throw new \RuntimeException('No arrancó php -S.');
        register_shutdown_function(fn () => proc_terminate($servidor));

        // Espera a que acepte conexiones (máx. 5 s).
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', (int) parse_url(self::$base, PHP_URL_PORT)); $i++) {
            usleep(100_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        $pdo = Conexion::pdo();
        if (self::$proyectos !== []) {
            $en = implode(',', self::$proyectos);
            $pdo->exec("DELETE e FROM evidencias e JOIN casos_prueba c ON c.id = e.caso_id WHERE c.proyecto_id IN ({$en})");
            $pdo->exec("DELETE FROM casos_prueba WHERE proyecto_id IN ({$en})");
            $pdo->exec("DELETE FROM requerimientos WHERE proyecto_id IN ({$en})");
            $pdo->exec("DELETE FROM proyectos WHERE id IN ({$en})");
        }
        if (self::$usuarios !== []) {
            $pdo->exec('DELETE FROM usuarios WHERE id IN (' . implode(',', array_keys(self::$usuarios)) . ')');
            $llaves = array_map(fn ($u) => 'u:' . $u, self::$usuarios);
            $pdo->prepare('DELETE FROM intentos_acceso WHERE clave IN (' . implode(',', array_fill(0, count($llaves), '?')) . ')')
                ->execute(array_values($llaves));
        }
        array_map('unlink', self::$archivos);
        self::$usuarios = self::$proyectos = self::$archivos = [];
    }

    protected function cliente(): Cliente
    {
        return new Cliente(self::$base);
    }

    // Cliente con la sesión del usuario abierta.
    protected function entrar(int $usuarioId): Cliente
    {
        $cliente = $this->cliente();
        $cliente->post('/', ['csrf' => $cliente->token('/'), 'usuario' => self::$usuarios[$usuarioId], 'clave' => self::CLAVE]);

        return $cliente;
    }

    // Fábrica: nombres únicos; todo se borra en tearDownAfterClass().

    protected function usuario(int $rol = 0): int
    {
        $usuario = uniqid('http', true);
        $id = UsuarioModelo::crear('Usuario HTTP', $usuario, self::$hash, $rol);
        self::$usuarios[$id] = $usuario;

        return $id;
    }

    protected function nombre(int $usuarioId): string
    {
        return self::$usuarios[$usuarioId];
    }

    protected function proyecto(int ...$miembros): int
    {
        $pdo = Conexion::pdo();
        $pdo->prepare('INSERT INTO proyectos (nombre) VALUES (?)')->execute([uniqid('http', true)]);
        $id = (int) $pdo->lastInsertId();
        foreach ($miembros as $m) {
            $pdo->prepare('INSERT INTO proyecto_miembros (proyecto_id, usuario_id) VALUES (?, ?)')->execute([$id, $m]);
        }
        self::$proyectos[] = $id;

        return $id;
    }

    protected function caso(int $proyectoId, int $autor, string $codigo = 'SIS-001'): int
    {
        Conexion::pdo()->prepare('INSERT INTO requerimientos (proyecto_id, codigo, descripcion) VALUES (?, ?, ?)')
            ->execute([$proyectoId, 'RF-99', 'Requerimiento']);

        return CasoModelo::crear([
            'proyecto_id' => $proyectoId, 'requerimiento_id' => (int) Conexion::pdo()->lastInsertId(), 'codigo' => $codigo,
            'tipo_prueba' => 1, 'subtecnica' => 1, 'modulo' => 'Módulo', 'plataforma' => 1,
            'objetivo' => 'Objetivo', 'entrada' => 'Entrada', 'pasos' => 'Pasos', 'resultado_esperado' => 'Resultado',
            'fecha_inicio' => '2026-10-06', 'fecha_fin' => '2026-10-06', 'creado_por' => $autor,
        ]);
    }

    // Log de texto en storage/evidencias.
    protected function evidencia(int $casoId, int $autor): int
    {
        $archivo = bin2hex(random_bytes(16)) . '.txt';
        $ruta = RAIZ . '/storage/evidencias/' . $archivo;
        file_put_contents($ruta, 'log de prueba');
        self::$archivos[] = $ruta;
        Conexion::pdo()->prepare('INSERT INTO evidencias (caso_id, tipo, archivo, nombre_original, descripcion, subido_por) VALUES (?, 2, ?, ?, ?, ?)')
            ->execute([$casoId, $archivo, 'prueba.txt', 'Log', $autor]);

        return (int) Conexion::pdo()->lastInsertId();
    }

    protected function contar(string $sql, mixed ...$valores): int
    {
        $consulta = Conexion::pdo()->prepare($sql);
        $consulta->execute($valores);

        return (int) $consulta->fetchColumn();
    }
}
