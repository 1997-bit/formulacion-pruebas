<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Catalogo;

// Junta los errores por campo y lanza ErrorValidacion si hay alguno.
// Los textos se revisan sin espacios al inicio ni al final.
final class Validador
{
    /** @var array<string, string> */
    private array $errores = [];

    public function requerido(string $campo, mixed $valor): self
    {
        return $this->regla($campo, trim((string) $valor) !== '', 'Es obligatorio.');
    }

    // Formato de <input type="date">: 2026-05-15. Vacío pasa; para exigirlo, usar requerido().
    public function fecha(string $campo, ?string $valor): self
    {
        $valor = trim((string) $valor);
        $fecha = \DateTime::createFromFormat('Y-m-d', $valor);

        return $this->regla($campo, $valor === '' || ($fecha && $fecha->format('Y-m-d') === $valor), 'Fecha inválida.');
    }

    // Vacío pasa; para exigirlo, usar requerido().
    public function catalogo(string $campo, string $catalogo, int|string|null $valor): self
    {
        $valor = trim((string) $valor);

        return $this->regla($campo, $valor === '' || Catalogo::existe($catalogo, $valor), 'Valor no válido.');
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
