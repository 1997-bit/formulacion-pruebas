<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Catalogo;

// Junta errores y lanza ErrorValidacion
final class Validador
{
    /** @var array<string, string> */
    private array $errores = [];

    public function requerido(string $campo, mixed $valor): self
    {
        return $this->regla($campo, trim((string) $valor) !== '', 'Es obligatorio.');
    }

    // TEXT de MariaDB: 65 535 bytes. Más da 500, BUG-008
    public function texto(string $campo, ?string $valor): self
    {
        return $this->regla($campo, strlen((string) $valor) <= 65535, 'Máximo 65 535 bytes.');
    }

    // AAAA-MM-DD. Vacío pasa
    public function fecha(string $campo, ?string $valor): self
    {
        $valor = trim((string) $valor);
        $fecha = \DateTime::createFromFormat('Y-m-d', $valor);

        return $this->regla($campo, $valor === '' || ($fecha && $fecha->format('Y-m-d') === $valor), 'Fecha inválida.');
    }

    // Vacío pasa
    public function catalogo(string $campo, string $catalogo, int|string|null $valor): self
    {
        $valor = trim((string) $valor);

        return $this->regla($campo, $valor === '' || Catalogo::existe($catalogo, $valor), 'Valor no válido.');
    }

    // Commit: 7 a 40 hexadecimales. Vacío pasa
    public function commit(string $campo, ?string $valor): self
    {
        $valor = trim((string) $valor);

        return $this->regla($campo, $valor === '' || preg_match('/^[0-9a-f]{7,40}$/i', $valor) === 1, 'De 7 a 40 caracteres de 0 a 9 y de a a f.');
    }

    public function regla(string $campo, bool $cumple, string $mensaje): self
    {
        if (!$cumple) {
            $this->errores[$campo] ??= $mensaje;
        }

        return $this;
    }

    public function comprobar(): void
    {
        if ($this->errores) {
            throw new ErrorValidacion($this->errores);
        }
    }
}
