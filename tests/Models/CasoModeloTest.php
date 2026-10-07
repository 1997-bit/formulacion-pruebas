<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Config\Conexion;
use App\Models\CasoModelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

#[CoversClass(CasoModelo::class)]
#[Medium]
final class CasoModeloTest extends BaseDatos
{
    // Regla 7
    public function testSiguienteNumeroSinCasosEs1(): void
    {
        $proyecto = $this->proyecto();

        $numero = CasoModelo::siguienteNumero($proyecto, 'SIS');

        $this->assertSame(1, $numero);
    }

    public function testSiguienteNumeroConHuecosEsElMaximoMas1(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $requerimiento = $this->requerimiento($proyecto);
        $this->caso($proyecto, $requerimiento, $usuario, 'SIS-001');
        $this->caso($proyecto, $requerimiento, $usuario, 'SIS-003');

        $numero = CasoModelo::siguienteNumero($proyecto, 'SIS');

        $this->assertSame(4, $numero);
    }

    // #111
    public function testPorCodigoRepetidoEntreProyectosDevuelveVarios(): void
    {
        $usuario = $this->usuario();
        $p1 = $this->proyecto();
        $p2 = $this->proyecto();
        $c1 = $this->caso($p1, $this->requerimiento($p1), $usuario);
        $c2 = $this->caso($p2, $this->requerimiento($p2), $usuario);

        $casos = CasoModelo::porCodigo([$p1, $p2], 'SIS-001');

        $this->assertSame([$c1, $c2], array_column($casos, 'id'));
    }

    public function testPorCodigoFiltraPorProyectosPermitidos(): void
    {
        $usuario = $this->usuario();
        $p1 = $this->proyecto();
        $p2 = $this->proyecto();
        $c1 = $this->caso($p1, $this->requerimiento($p1), $usuario);
        $this->caso($p2, $this->requerimiento($p2), $usuario);

        $casos = CasoModelo::porCodigo([$p1], 'SIS-001');

        $this->assertSame([$c1], array_column($casos, 'id'));
    }

    // RF-07
    public function testEliminarSinEvidenciasNiIncidentes(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);

        $eliminado = CasoModelo::eliminar($caso);

        $this->assertTrue($eliminado);
        $this->assertNull(CasoModelo::porId($caso));
    }

    public function testEliminarConEvidenciaDevuelveFalse(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        Conexion::pdo()->prepare('INSERT INTO evidencias (caso_id, tipo, enlace, descripcion, subido_por) VALUES (?, 4, ?, ?, ?)')
            ->execute([$caso, 'https://ejemplo.com', 'Enlace', $usuario]);

        $eliminado = CasoModelo::eliminar($caso);

        $this->assertFalse($eliminado);
    }

    public function testEliminarConIncidenteDevuelveFalse(): void
    {
        $usuario = $this->usuario();
        $proyecto = $this->proyecto();
        $caso = $this->caso($proyecto, $this->requerimiento($proyecto), $usuario);
        $this->incidente($proyecto, $caso, $usuario);

        $eliminado = CasoModelo::eliminar($caso);

        $this->assertFalse($eliminado);
    }
}
