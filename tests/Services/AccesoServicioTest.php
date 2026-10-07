<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Models\UsuarioModelo;
use App\Services\AccesoServicio;
use App\Services\UsuarioServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-01, RF-02, RNF-02
#[CoversClass(AccesoServicio::class)]
#[Medium]
final class AccesoServicioTest extends BaseDatos
{
    private const CLAVE = 'clave-segura';

    private const ERROR = ['general' => 'Usuario o contraseña incorrectos.'];

    private string $nombre;

    private int $id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nombre = uniqid('acc', true);
        $this->id = UsuarioModelo::crear('Acceso', $this->nombre, password_hash(self::CLAVE, PASSWORD_ARGON2ID, UsuarioServicio::ARGON), 0);
    }

    private function clave(): string
    {
        $sql = Conexion::pdo()->prepare('SELECT clave FROM usuarios WHERE id = ?');
        $sql->execute([$this->id]);

        return (string) $sql->fetchColumn();
    }

    public function testEntraConClaveCorrecta(): void
    {
        $usuario = AccesoServicio::entrar($this->nombre, self::CLAVE);

        $this->assertSame([$this->id, 0], [$usuario['id'], $usuario['rol']]);
    }

    public function testQuitaEspaciosAlrededorDelUsuario(): void
    {
        $usuario = AccesoServicio::entrar("  {$this->nombre}  ", self::CLAVE);

        $this->assertSame($this->id, $usuario['id']);
    }

    /** @return array<string, array{string, string}> */
    public static function fallos(): array
    {
        return [
            'clave incorrecta' => ['', 'otra-clave'],
            'usuario no existe' => ['x', self::CLAVE],
        ];
    }

    // No dice si el usuario existe.
    #[DataProvider('fallos')]
    public function testFalloDaElMismoMensaje(string $sufijo, string $clave): void
    {
        $errores = $this->errores(fn () => AccesoServicio::entrar($this->nombre . $sufijo, $clave));

        $this->assertSame(self::ERROR, $errores);
    }

    // IntentoModelo::LIBRES = 3: el tercer fallo bloquea.
    public function testDosFallosNoBloquean(): void
    {
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));

        $usuario = AccesoServicio::entrar($this->nombre, self::CLAVE);

        $this->assertSame($this->id, $usuario['id']);
    }

    public function testTercerFalloBloqueaAunConClaveCorrecta(): void
    {
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));

        $errores = $this->errores(fn () => AccesoServicio::entrar($this->nombre, self::CLAVE));

        $this->assertSame(['general' => 'Demasiados intentos. Espere un momento.'], $errores);
    }

    public function testEntrarBorraLosFallos(): void
    {
        $this->errores(fn () => AccesoServicio::entrar($this->nombre, 'mala'));

        AccesoServicio::entrar($this->nombre, self::CLAVE);

        $this->assertSame(0, $this->contar('SELECT COUNT(*) FROM intentos_acceso WHERE clave = ?', 'u:' . $this->nombre));
    }

    // RNF-01
    public function testHashViejoSePasaAArgon2id(): void
    {
        UsuarioModelo::cambiarClave($this->id, password_hash(self::CLAVE, PASSWORD_BCRYPT));

        AccesoServicio::entrar($this->nombre, self::CLAVE);

        $this->assertStringStartsWith('$argon2id$v=19$m=19456,t=2,p=1$', $this->clave());
    }

    // BUG-003, BUG-023
    public function testVigenteEsNullSiElUsuarioYaNoExiste(): void
    {
        $sesion = ['id' => $this->id, 'sesion_version' => 0];
        UsuarioModelo::eliminar($this->id);

        $vigente = AccesoServicio::vigente($sesion);

        $this->assertNull($vigente);
    }

    public function testVigenteEsNullSiCambioLaVersionDeSesion(): void
    {
        $sesion = AccesoServicio::entrar($this->nombre, self::CLAVE);
        UsuarioModelo::actualizar($this->id, 'Acceso', $this->nombre, null, 1);

        $vigente = AccesoServicio::vigente($sesion);

        $this->assertNull($vigente);
    }

    public function testVigenteTraeLosProyectos(): void
    {
        $sesion = AccesoServicio::entrar($this->nombre, self::CLAVE);
        $proyecto = $this->proyecto($this->id);

        $vigente = AccesoServicio::vigente($sesion);

        $this->assertSame([$proyecto], $vigente['proyectos'] ?? null);
    }

    // RF-02: el registro público nunca crea admin.
    public function testRegistrarCreaTester(): void
    {
        $usuario = uniqid('reg', true);

        AccesoServicio::registrar('Nuevo', $usuario, self::CLAVE, '192.0.2.1');

        $this->assertSame(0, (int) UsuarioModelo::porUsuario($usuario)['rol']);
    }

    // #101
    public function testRegistrarUsuarioRepetidoDaErrorDeCampo(): void
    {
        $errores = $this->errores(fn () => AccesoServicio::registrar('Otro', $this->nombre, self::CLAVE, '192.0.2.2'));

        $this->assertSame(['usuario' => 'Ese usuario ya existe.'], $errores);
    }
}
