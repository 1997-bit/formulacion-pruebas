<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Config\Conexion;
use App\Services\Historial;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Apoyo\BaseDatos;

// RF-20
#[CoversClass(Historial::class)]
#[Medium]
final class HistorialTest extends BaseDatos
{
    private int $autor;

    private int $proyecto;

    private int $casoId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autor = $this->usuario(0, 'Ana Autora');
        $this->proyecto = $this->proyecto($this->autor);
        $this->casoId = $this->caso($this->proyecto, $this->requerimiento($this->proyecto), $this->autor);
    }

    /** @return list<array<string, mixed>> */
    private function filas(string $tabla, int $id): array
    {
        $sql = Conexion::pdo()->prepare('SELECT campo, antes, despues FROM logs_cambios WHERE tabla = ? AND registro_id = ? ORDER BY id');
        $sql->execute([$tabla, $id]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function testCasoGuardaSoloCamposCambiadosDeLaLista(): void
    {
        $antes = ['modulo' => 'Login', 'objetivo' => 'Igual', 'huella' => 'a'];
        $despues = ['modulo' => 'Pagos', 'objetivo' => 'Igual', 'huella' => 'b'];

        Historial::registrar('casos_prueba', $this->casoId, $antes, $despues, $this->autor);

        $this->assertSame([['campo' => 'modulo', 'antes' => 'Login', 'despues' => 'Pagos']], $this->filas('casos_prueba', $this->casoId));
    }

    public function testSinCambiosNoEscribe(): void
    {
        $datos = ['modulo' => 'Login', 'estado' => 1];

        Historial::registrar('casos_prueba', $this->casoId, $datos, $datos, $this->autor);

        $this->assertSame([], $this->filas('casos_prueba', $this->casoId));
    }

    public function testMismoValorComoNumeroYTextoNoEsCambio(): void
    {
        Historial::registrar('casos_prueba', $this->casoId, ['estado' => 1], ['estado' => '1'], $this->autor);

        $this->assertSame([], $this->filas('casos_prueba', $this->casoId));
    }

    public function testDeNullAVacioEsCambio(): void
    {
        Historial::registrar('casos_prueba', $this->casoId, ['entorno' => null], ['entorno' => ''], $this->autor);

        $this->assertSame([['campo' => 'entorno', 'antes' => null, 'despues' => '']], $this->filas('casos_prueba', $this->casoId));
    }

    // Incidentes y plan: los campos que trae $antes, sin lista fija.
    public function testOtraTablaUsaLosCamposDeAntes(): void
    {
        $incidente = $this->incidente($this->proyecto, $this->casoId, $this->autor);

        Historial::registrar('incidentes', $incidente, ['estado' => 0], ['estado' => 1, 'titulo' => 'Nuevo'], $this->autor);

        $this->assertSame([['campo' => 'estado', 'antes' => '0', 'despues' => '1']], $this->filas('incidentes', $incidente));
    }

    /** @return array<string, array{string, string, string, string, string, string}> */
    public static function legibles(): array
    {
        return [
            'catálogo' => ['estado', '0', '2', 'Estado', 'Pendiente', 'FAULT'],
            'fecha' => ['fecha_fin', '2026-10-06', '2026-12-31', 'Fecha final', '06/10/2026', '31/12/2026'],
            'texto' => ['modulo', 'Login', 'Pagos', 'Módulo', 'Login', 'Pagos'],
        ];
    }

    #[DataProvider('legibles')]
    public function testDeCasoMuestraEtiquetaYValoresLegibles(string $campo, string $antes, string $despues, string $etiqueta, string $textoAntes, string $textoDespues): void
    {
        Historial::registrar('casos_prueba', $this->casoId, [$campo => $antes], [$campo => $despues], $this->autor);

        $fila = Historial::deCaso($this->casoId)[0];

        $this->assertSame([$etiqueta, $textoAntes, $textoDespues], [$fila['etiqueta'], $fila['antes'], $fila['despues']]);
    }

    public function testDeCasoMuestraElNombreDelUsuario(): void
    {
        Historial::registrar('casos_prueba', $this->casoId, ['solicitado_por' => null], ['solicitado_por' => $this->autor], $this->autor);

        $fila = Historial::deCaso($this->casoId)[0];

        $this->assertSame(['Solicitado por', '', 'Ana Autora'], [$fila['etiqueta'], $fila['antes'], $fila['despues']]);
    }

    public function testDeCasoVaDelMasNuevoAlMasViejo(): void
    {
        Historial::registrar('casos_prueba', $this->casoId, ['modulo' => 'A'], ['modulo' => 'B'], $this->autor);
        Historial::registrar('casos_prueba', $this->casoId, ['modulo' => 'B'], ['modulo' => 'C'], $this->autor);

        $historial = Historial::deCaso($this->casoId);

        $this->assertSame(['C', 'B'], array_column($historial, 'despues'));
    }
}
