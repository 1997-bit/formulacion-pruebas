<?php

declare(strict_types=1);

namespace App\Core;

// index.php responde 403.
final class ErrorPermiso extends \RuntimeException
{
    protected $message = 'Sin permiso.';
}
