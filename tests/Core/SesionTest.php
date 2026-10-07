<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Sesion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RNF-02
#[CoversClass(Sesion::class)]
#[Small]
final class SesionTest extends TestCase
{
    protected function setUp(): void
    {
        // Sin cookies ni cabeceras: la sesión funciona en CLI.
        ini_set('session.use_cookies', '0');
        ini_set('session.cache_limiter', '');
        session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        unset($_SERVER['REQUEST_URI']);
    }

    public function testSinSesionNoHayUsuario(): void
    {
        $usuario = Sesion::usuario();

        $this->assertNull($usuario);
    }

    public function testEntrarGuardaUsuario(): void
    {
        Sesion::entrar(['id' => 1]);

        $this->assertSame(['id' => 1], Sesion::usuario());
    }

    public function testEntrarRegeneraId(): void
    {
        $antes = session_id();

        Sesion::entrar(['id' => 1]);

        $this->assertNotSame($antes, session_id());
    }

    public function testSalirBorraUsuario(): void
    {
        Sesion::entrar(['id' => 1]);

        Sesion::salir();

        $this->assertNull(Sesion::usuario());
    }

    public function testRefrescarCambiaDatosSinPerderCsrf(): void
    {
        $_SESSION['csrf'] = 'abc';
        $antes = session_id();

        Sesion::refrescar(['id' => 1, 'nombre' => 'Nuevo']);

        $this->assertSame(['id' => 1, 'nombre' => 'Nuevo'], Sesion::usuario());
        $this->assertSame('abc', $_SESSION['csrf']);
        $this->assertSame($antes, session_id());
    }

    public function testFlashSeLeeUnaVez(): void
    {
        Sesion::flash('Guardado.');

        $this->assertSame(['Guardado.', null], [Sesion::tomar('flash'), Sesion::tomar('flash')]);
    }

    public function testTomarSinValorDevuelveDefecto(): void
    {
        $valor = Sesion::tomar('flash', 'defecto');

        $this->assertSame('defecto', $valor);
    }

    // #103
    public function testErroresSoloSalenEnSuRuta(): void
    {
        $_SESSION['errores'] = ['/casos/registrar' => ['modulo' => 'Es obligatorio.']];
        $_SERVER['REQUEST_URI'] = '/proyectos/registrar';

        $errores = Sesion::tomar('errores', []);

        $this->assertSame([], $errores);
    }

    public function testErroresSalenEnSuRuta(): void
    {
        $_SESSION['errores'] = ['/casos/registrar' => ['modulo' => 'Es obligatorio.']];
        $_SERVER['REQUEST_URI'] = '/casos/registrar';

        $errores = Sesion::tomar('errores', []);

        $this->assertSame(['modulo' => 'Es obligatorio.'], $errores);
    }
}
