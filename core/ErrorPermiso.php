<?php

declare(strict_types=1);

namespace App\Core;

// 403
final class ErrorPermiso extends \RuntimeException
{
    protected $message = 'Sin permiso.';
}
