<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\IntentoModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RNF-02: 3 fallos libres; el tercero bloquea 1 s.
#[CoversClass(IntentoModelo::class)]
#[Medium]
final class IntentoModeloTest extends BaseDatos
{
    public function testDosFallosNoBloquean(): void
    {
        $clave = uniqid('i', true);
        IntentoModelo::fallar($clave);
        IntentoModelo::fallar($clave);

        $bloqueado = IntentoModelo::bloqueado($clave);

        $this->assertFalse($bloqueado);
    }

    public function testTresFallosBloquean(): void
    {
        $clave = uniqid('i', true);
        IntentoModelo::fallar($clave);
        IntentoModelo::fallar($clave);
        IntentoModelo::fallar($clave);

        $bloqueado = IntentoModelo::bloqueado($clave);

        $this->assertTrue($bloqueado);
    }

    public function testBorrarDesbloquea(): void
    {
        $clave = uniqid('i', true);
        IntentoModelo::fallar($clave);
        IntentoModelo::fallar($clave);
        IntentoModelo::fallar($clave);

        IntentoModelo::borrar($clave);

        $this->assertFalse(IntentoModelo::bloqueado($clave));
    }
}
