<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\ErrorNoEncontrado;
use App\Core\ErrorPermiso;
use App\Services\Permisos;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RF-21, matriz 7.1
#[CoversClass(Permisos::class)]
#[Small]
final class PermisosTest extends TestCase
{
    private const ADMIN = ['id' => 1, 'rol' => 1, 'proyectos' => []];

    private const TESTER = ['id' => 2, 'rol' => 0, 'proyectos' => [10, 11]];

    private const CASO = ['proyecto_id' => 10, 'creado_por' => 2];

    public function testAdminPasaExigirAdmin(): void
    {
        Permisos::exigirAdmin(self::ADMIN);

        $this->addToAssertionCount(1);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function noAdmins(): array
    {
        return [
            'tester' => [self::TESTER],
            'sin rol' => [['id' => 3]],
        ];
    }

    /** @param array<string, mixed> $usuario */
    #[DataProvider('noAdmins')]
    public function testNoAdminNoPasaExigirAdmin(array $usuario): void
    {
        $this->expectException(ErrorPermiso::class);

        Permisos::exigirAdmin($usuario);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function miembros(): array
    {
        return [
            'admin sin ser miembro' => [self::ADMIN],
            'tester miembro' => [self::TESTER],
        ];
    }

    /** @param array<string, mixed> $usuario */
    #[DataProvider('miembros')]
    public function testMiembroOAdminPasaExigirMiembro(array $usuario): void
    {
        Permisos::exigirMiembro($usuario, 10);

        $this->addToAssertionCount(1);
    }

    // BUG-026: 404, no 403.
    public function testTesterAjenoNoEncuentraElProyecto(): void
    {
        $this->expectException(ErrorNoEncontrado::class);

        Permisos::exigirMiembro(self::TESTER, 99);
    }

    // #109
    public function testProyectosDelAdminSonTodos(): void
    {
        $proyectos = Permisos::proyectos(self::ADMIN);

        $this->assertNull($proyectos);
    }

    public function testProyectosDelTesterSonLosSuyos(): void
    {
        $proyectos = Permisos::proyectos(self::TESTER);

        $this->assertSame([10, 11], $proyectos);
    }

    /** @return array<string, array{array<string, mixed>, bool}> */
    public static function editores(): array
    {
        return [
            'admin' => [self::ADMIN, true],
            'tester creador' => [self::TESTER, true],
            'tester no creador' => [['id' => 5, 'rol' => 0, 'proyectos' => [10]], false],
        ];
    }

    // RF-06
    /** @param array<string, mixed> $usuario */
    #[DataProvider('editores')]
    public function testPuedeEditarCasoAdminOCreador(array $usuario, bool $esperado): void
    {
        $puede = Permisos::puedeEditarCaso($usuario, self::CASO);

        $this->assertSame($esperado, $puede);
    }

    // BUG-026: un ajeno recibe 404 antes que 403.
    public function testExigirEditarCasoAjenoDa404(): void
    {
        $this->expectException(ErrorNoEncontrado::class);

        Permisos::exigirEditarCaso(['id' => 5, 'rol' => 0, 'proyectos' => []], self::CASO);
    }

    public function testExigirEditarCasoMiembroNoCreadorDa403(): void
    {
        $this->expectException(ErrorPermiso::class);

        Permisos::exigirEditarCaso(['id' => 5, 'rol' => 0, 'proyectos' => [10]], self::CASO);
    }
}
