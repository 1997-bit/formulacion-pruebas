<?php

declare(strict_types=1);

namespace App\Core;

// 404. Otro proyecto responde igual que inexistente, BUG-026
final class ErrorNoEncontrado extends \RuntimeException
{
    protected $message = 'No encontrado.';
}
