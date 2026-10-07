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

    public function testSalirCierraLaSesion(): void
    {
        $cliente = $this->entrar($this->usuario());
        $cliente->get('/salir');

        $respuesta = $cliente->get('/dashboard');

        $this->assertSame([302, '/'], [$respuesta['codigo'], $respuesta['cabeceras']['location'] ?? null]);
    }

    private function mensaje(string $html): ?string
    {
        preg_match('/<p class="alerta-titulo">([^<]*)<\/p>/', $html, $m);

        return $m[1] ?? null;
    }
}
