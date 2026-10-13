<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// RF-01, RNF-02
#[CoversNothing]
#[Large]
final class AccesoTest extends PruebaHttp
{
    public function testEntrarVaAlPanelYRegeneraLaSesion(): void
    {
        $id = $this->usuario();
        $cliente = $this->cliente();
        $token = $cliente->token('/');
        $antes = $cliente->sesion();

        $respuesta = $cliente->post('/', ['csrf' => $token, 'usuario' => $this->nombre($id), 'clave' => self::CLAVE]);

        $this->assertSame('/dashboard', $respuesta['cabeceras']['location'] ?? null);
        $this->assertNotSame($antes, $cliente->sesion());
    }

    public function testClaveIncorrectaYUsuarioInexistenteDanElMismoMensaje(): void
    {
        $id = $this->usuario();
        $malaClave = $this->cliente();
        $malaClave->post('/', ['csrf' => $malaClave->token('/'), 'usuario' => $this->nombre($id), 'clave' => 'otra-clave']);
        $noExiste = $this->cliente();
        $noExiste->post('/', ['csrf' => $noExiste->token('/'), 'usuario' => $this->nombre($id) . 'x', 'clave' => self::CLAVE]);

        $mensajes = [$this->mensaje($malaClave->get('/')['cuerpo']), $this->mensaje($noExiste->get('/')['cuerpo'])];

        $this->assertSame(['Usuario o contraseña incorrectos.', 'Usuario o contraseña incorrectos.'], $mensajes);
    }

    public function testSalirConGetNoExiste(): void
    {
        $cliente = $this->entrar($this->usuario());

        $this->assertSame(404, $cliente->get('/salir')['codigo']);
        $this->assertSame(200, $cliente->get('/dashboard')['codigo']);
    }

    public function testSalirCierraLaSesion(): void
    {
        $cliente = $this->entrar($this->usuario());
        $cliente->post('/salir', ['csrf' => $cliente->token('/dashboard')]);

        $respuesta = $cliente->get('/dashboard');

        $this->assertSame([302, '/'], [$respuesta['codigo'], $respuesta['cabeceras']['location'] ?? null]);
    }

    // RNF-11. POST sin token: 403 y malicious_csrf con el mismo X-Request-Id.
    public function testPostSinTokenDejaMaliciousCsrf(): void
    {
        $respuesta = $this->cliente()->post('/', ['usuario' => 'a', 'clave' => 'b']);

        $this->assertSame(403, $respuesta['codigo']);
        $this->assertContains('malicious_csrf:', $this->eventos($respuesta));
    }

    // RNF-11. Tester en ruta de admin: authz_fail.
    public function testTesterEnRutaAdminDejaAuthzFail(): void
    {
        $id = $this->usuario();
        $respuesta = $this->entrar($id)->get('/admin/usuarios');

        $this->assertSame(403, $respuesta['codigo']);
        $this->assertContains("authz_fail:{$id},/admin/usuarios", $this->eventos($respuesta));
    }

    private function mensaje(string $html): ?string
    {
        preg_match('/<p class="alerta-titulo">([^<]*)<\/p>/', $html, $m);

        return $m[1] ?? null;
    }
}
