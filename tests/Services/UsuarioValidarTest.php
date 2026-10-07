<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorValidacion;
use App\Services\UsuarioServicio;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RF-02
#[CoversClass(UsuarioServicio::class)]
#[Small]
final class UsuarioValidarTest extends TestCase
{
    private const DATOS = ['nombre' => 'Ana', 'usuario' => 'ana', 'clave' => '12345678', 'rol' => '0'];

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function errores(array $datos, ?int $id): array
    {
        try {
            UsuarioServicio::validar($datos + self::DATOS, $id);
        } catch (ErrorValidacion $e) {
            return $e->errores;
        }

        return [];
    }

    /** @return array<string, array{array<string, string>, ?int, array<string, string>}> */
    public static function casos(): array
    {
        return [
            'nombre 100' => [['nombre' => str_repeat('a', 100)], null, []],
            'nombre 101' => [['nombre' => str_repeat('a', 101)], null, ['nombre' => 'Máximo 100 caracteres.']],
            'usuario 30' => [['usuario' => str_repeat('a', 30)], null, []],
            'usuario 31' => [['usuario' => str_repeat('a', 31)], null, ['usuario' => 'Máximo 30 caracteres.']],
            'clave 8' => [['clave' => '12345678'], null, []],
            'clave 7' => [['clave' => '1234567'], null, ['clave' => 'Mínimo 8 caracteres.']],
            'clave vacía al crear' => [['clave' => ''], null, ['clave' => 'Mínimo 8 caracteres.']],
            'clave vacía al editar' => [['clave' => ''], 5, []],
            'rol fuera de catálogo' => [['rol' => '2'], null, ['rol' => 'Valor no válido.']],
        ];
    }

    /**
     * @param array<string, string> $cambios
     * @param array<string, string> $esperado
     */
    #[DataProvider('casos')]
    public function testValidar(array $cambios, ?int $id, array $esperado): void
    {
        $errores = $this->errores($cambios, $id);

        $this->assertSame($esperado, $errores);
    }
}
