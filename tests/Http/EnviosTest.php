<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;
use Tests\Apoyo\PruebaHttp;

// Formularios por HTTP: CSRF, doble envío y errores por ruta.
#[CoversNothing]
#[Large]
final class EnviosTest extends PruebaHttp
{
    /** @return array<string, array{array<string, string>}> */
    public static function tokensMalos(): array
    {
        return [
            'sin token' => [[]],
            'token falso' => [['csrf' => str_repeat('0', 64)]],
        ];
    }

    // RNF-02
    #[DataProvider('tokensMalos')]
    public function testPostSinCsrfValidoDa403YNoGuarda(array $token): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $cliente = $this->entrar($tester);

        $respuesta = $cliente->post('/requerimientos/registrar', $token + ['proyecto_id' => (string) $proyecto, 'codigo' => 'RF-01', 'descripcion' => 'Descripción']);

        $this->assertSame(403, $respuesta['codigo']);
        $this->assertSame(0, $this->contar('SELECT COUNT(*) FROM requerimientos WHERE proyecto_id = ?', $proyecto));
    }

    // #102, BUG-031: el segundo envío va a donde fue el primero, sin guardar otra vez.
    public function testEnvioRepetidoGuardaUnaVez(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $cliente = $this->entrar($tester);
        $datos = [
            'csrf' => $cliente->token('/requerimientos/registrar'), 'envio' => bin2hex(random_bytes(16)),
            'proyecto_id' => (string) $proyecto, 'codigo' => 'RF-01', 'descripcion' => 'Descripción',
        ];
        $cliente->post('/requerimientos/registrar', $datos);

        $respuesta = $cliente->post('/requerimientos/registrar', $datos);

        $this->assertSame('/requerimientos/listar', $respuesta['cabeceras']['location'] ?? null);
        $this->assertSame(1, $this->contar('SELECT COUNT(*) FROM requerimientos WHERE proyecto_id = ?', $proyecto));
    }

    // #103
    public function testErroresDeUnFormularioNoSalenEnOtro(): void
    {
        $tester = $this->usuario();
        $proyecto = $this->proyecto($tester);
        $cliente = $this->entrar($tester);
        $cliente->post('/requerimientos/registrar', [
            'csrf' => $cliente->token('/requerimientos/registrar'),
            'proyecto_id' => (string) $proyecto, 'codigo' => 'X-01', 'descripcion' => 'Descripción',
        ]);

        $respuesta = $cliente->get('/casos/registrar');

        $this->assertSame(200, $respuesta['codigo']);
        $this->assertStringNotContainsString('Use RF-01 o RNF-01.', $respuesta['cuerpo']);
    }
}
