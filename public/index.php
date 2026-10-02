<?php

declare(strict_types=1);

// Front controller. Todas las peticiones pasan por aqui.
define('RAIZ', dirname(__DIR__));
require RAIZ . '/core/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Sistema de Casos de Prueba: esqueleto OK\n";
echo 'PHP ' . PHP_VERSION . "\n";
