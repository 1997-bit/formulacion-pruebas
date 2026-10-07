<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Config\Conexion;
use App\Models\ProyectoModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

#[CoversClass(ProyectoModelo::class)]
#[Medium]
final class ProyectoModeloTest extends BaseDatos
{
    // BUG-007: el que sigue no pierde su autoevaluación.
    public function testGuardarQuitaSoloALosQueSalen(): void
    {
        $sale = $this->usuario();
        $sigue = $this->usuario();
        $entra = $this->usuario();
        $proyecto = $this->proyecto($sale, $sigue);
        Conexion::pdo()->prepare('INSERT INTO autoevaluaciones (proyecto_id, evaluador_id, evaluado_id, aspecto, puntos) VALUES (?, ?, ?, 1, 5)')
            ->execute([$proyecto, $sigue, $sigue]);

        ProyectoModelo::guardar($proyecto, uniqid('p', true), null, [$sigue, $entra]);

        $miembros = ProyectoModelo::miembros($proyecto);
        sort($miembros);
        $this->assertSame([$sigue, $entra], $miembros);
        $this->assertSame(1, $this->contar('SELECT COUNT(*) FROM autoevaluaciones WHERE proyecto_id = ?', $proyecto));
    }
}
