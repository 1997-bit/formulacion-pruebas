<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\RubricaModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// #98
#[CoversClass(RubricaModelo::class)]
#[Medium]
final class RubricaModeloTest extends BaseDatos
{
    public function testGuardarSinCambiosNoCambiaElAutor(): void
    {
        $autor = $this->usuario(1, 'Autor');
        $otro = $this->usuario(1, 'Otro');
        $proyecto = $this->proyecto();
        RubricaModelo::guardar($proyecto, $autor, [1 => 5, 2 => 4]);

        RubricaModelo::guardar($proyecto, $otro, [1 => 5, 2 => 4]);

        $this->assertSame(2, $this->contar('SELECT COUNT(*) FROM rubrica_evaluaciones WHERE proyecto_id = ? AND evaluado_por = ?', $proyecto, $autor));
    }

    public function testGuardarCambiaElAutorSoloDelCriterioCambiado(): void
    {
        $autor = $this->usuario(1, 'Autor');
        $otro = $this->usuario(1, 'Otro');
        $proyecto = $this->proyecto();
        RubricaModelo::guardar($proyecto, $autor, [1 => 5, 2 => 4]);

        RubricaModelo::guardar($proyecto, $otro, [1 => 5, 2 => 3]);

        $this->assertSame(1, $this->contar('SELECT COUNT(*) FROM rubrica_evaluaciones WHERE proyecto_id = ? AND evaluado_por = ?', $proyecto, $otro));
    }
}
