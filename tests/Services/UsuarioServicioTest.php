<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Core\ErrorPermiso;
use App\Models\UsuarioModelo;
use App\Services\UsuarioServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-02, RF-03
#[CoversClass(UsuarioServicio::class)]
#[Medium]
final class UsuarioServicioTest extends BaseDatos
{
    /** @return array<string, string> */
    private static function datos(string $usuario, string $rol = '0', string $clave = '12345678'): array
    {
        return ['nombre' => 'Nombre', 'usuario' => $usuario, 'clave' => $clave, 'rol' => $rol];
    }

    // La base ya trae admins: dentro de la transacción, $admin queda como único admin.
    private function unicoAdmin(): int
    {
        $admin = $this->usuario(1);
        Conexion::pdo()->prepare('UPDATE usuarios SET rol = 0 WHERE rol = 1 AND id <> ?')->execute([$admin]);

        return $admin;
    }

    private function version(int $id): int
    {
        return $this->contar('SELECT sesion_version FROM usuarios WHERE id = ?', $id);
    }

    public function testAdminCreaAdmin(): void
    {
        $usuario = uniqid('crea', true);

        UsuarioServicio::crear(self::datos($usuario, '1'), $this->sesion($this->usuario(1)));

        $this->assertSame(1, (int) UsuarioModelo::porUsuario($usuario)['rol']);
    }

    // RNF-01
    public function testClaveSeGuardaConArgon2id(): void
    {
        $usuario = uniqid('crea', true);

        UsuarioServicio::crear(self::datos($usuario), $this->sesion($this->usuario(1)));

        $this->assertStringStartsWith('$argon2id$', (string) UsuarioModelo::porUsuario($usuario)['clave']);
    }

    // RF-21
    public function testTesterNoCrea(): void
    {
        $this->expectException(ErrorPermiso::class);

        UsuarioServicio::crear(self::datos(uniqid('crea', true)), $this->sesion($this->usuario()));
    }

    // #101
    public function testCrearUsuarioRepetidoDaErrorDeCampo(): void
    {
        $admin = $this->sesion($this->usuario(1));
        $usuario = uniqid('crea', true);
        UsuarioServicio::crear(self::datos($usuario), $admin);

        $errores = $this->errores(fn () => UsuarioServicio::crear(self::datos($usuario), $admin));

        $this->assertSame(['usuario' => 'Ese usuario ya existe.'], $errores);
    }

    /** @return array<string, array{string, string, int}> */
    public static function versiones(): array
    {
        return [
            'cambia clave' => ['otra-clave-1', '0', 1],
            'cambia rol' => ['', '1', 1],
            'solo nombre' => ['', '0', 0],
        ];
    }

    // RF-03: clave o rol cambiado cierra la sesión abierta.
    #[DataProvider('versiones')]
    public function testEditarSubeVersionDeSesionSoloConClaveORol(string $clave, string $rol, int $esperado): void
    {
        $id = $this->usuario();
        $usuario = (string) UsuarioModelo::porId($id)['usuario'];

        UsuarioServicio::editar($id, self::datos($usuario, $rol, $clave), $this->sesion($this->usuario(1)));

        $this->assertSame($esperado, $this->version($id));
    }

    // #116
    public function testQuitarRolAlUltimoAdminDaError(): void
    {
        $admin = $this->unicoAdmin();
        $actor = ['id' => $this->usuario(), 'rol' => 1];
        $usuario = (string) UsuarioModelo::porId($admin)['usuario'];

        $errores = $this->errores(fn () => UsuarioServicio::editar($admin, self::datos($usuario, '0', ''), $actor));

        $this->assertSame(['general' => 'Debe quedar al menos un admin.'], $errores);
    }

    public function testNoPuedeQuitarseSuPropioRol(): void
    {
        $admin = $this->usuario(1);
        $usuario = (string) UsuarioModelo::porId($admin)['usuario'];

        $errores = $this->errores(fn () => UsuarioServicio::editar($admin, self::datos($usuario, '0', ''), $this->sesion($admin)));

        $this->assertSame(['rol' => 'No puede quitarse su propio rol de admin.'], $errores);
    }

    // #116
    public function testEliminarAlUltimoAdminDaError(): void
    {
        $admin = $this->unicoAdmin();
        $actor = ['id' => $this->usuario(), 'rol' => 1];

        $errores = $this->errores(fn () => UsuarioServicio::eliminar($admin, $actor));

        $this->assertSame(['general' => 'Debe quedar al menos un admin.'], $errores);
    }

    public function testEliminarTester(): void
    {
        $tester = $this->usuario();

        UsuarioServicio::eliminar($tester, $this->sesion($this->usuario(1)));

        $this->assertNull(UsuarioModelo::porId($tester));
    }

    public function testNoPuedeEliminarseASiMismo(): void
    {
        $admin = $this->usuario(1);

        $errores = $this->errores(fn () => UsuarioServicio::eliminar($admin, $this->sesion($admin)));

        $this->assertSame(['general' => 'No puede eliminar su propia cuenta.'], $errores);
    }

    // RF-21
    public function testTesterNoLista(): void
    {
        $tester = $this->sesion($this->usuario());

        $this->expectException(ErrorPermiso::class);

        UsuarioServicio::listar($tester);
    }

    public function testTesterNoVe(): void
    {
        $tester = $this->sesion($this->usuario());

        $this->expectException(ErrorPermiso::class);

        UsuarioServicio::ver($tester['id'], $tester);
    }
}
