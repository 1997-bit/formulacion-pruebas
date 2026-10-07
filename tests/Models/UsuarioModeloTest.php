<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\UsuarioModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

#[CoversClass(UsuarioModelo::class)]
#[Medium]
final class UsuarioModeloTest extends BaseDatos
{
    // #109, #110
    public function testDeSesionTraeSusProyectos(): void
    {
        $usuario = $this->usuario();
        $p1 = $this->proyecto($usuario);
        $p2 = $this->proyecto($usuario);
        $this->proyecto();

        $proyectos = UsuarioModelo::deSesion($usuario)['proyectos'] ?? [];

        sort($proyectos); // GROUP_CONCAT sin ORDER BY
        $this->assertSame([$p1, $p2], $proyectos);
    }

    public function testDeSesionSinProyectosDevuelveListaVacia(): void
    {
        $usuario = $this->usuario();

        $proyectos = UsuarioModelo::deSesion($usuario)['proyectos'] ?? null;

        $this->assertSame([], $proyectos);
    }

    public function testDeSesionUsuarioInexistenteDevuelveNull(): void
    {
        $usuario = UsuarioModelo::deSesion(0);

        $this->assertNull($usuario);
    }

    // #116: la base ya trae usuarios; se compara contra la cuenta de antes.
    public function testContarAdminsCuentaSoloAdmins(): void
    {
        $antes = UsuarioModelo::contarAdmins();
        $this->usuario(1);
        $this->usuario(0);

        $despues = UsuarioModelo::contarAdmins();

        $this->assertSame($antes + 1, $despues);
    }
}
