<?php

declare(strict_types=1);

namespace App\Core;

// index.php responde 404. Un registro de otro proyecto responde igual que uno que no existe (BUG-026).
final class ErrorNoEncontrado extends \RuntimeException
{
    protected $message = 'No encontrado.';
}
