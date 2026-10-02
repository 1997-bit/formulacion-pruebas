<?php

declare(strict_types=1);

// Arranque de PHPStan y PHPUnit: lo que en la app hace public/index.php.
define('RAIZ', dirname(__DIR__));
require RAIZ . '/vendor/autoload.php';
