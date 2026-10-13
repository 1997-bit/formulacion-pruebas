<?php

declare(strict_types=1);

// Composer: clases App\ y Monolog. Ver README.
require RAIZ . '/vendor/autoload.php';

App\Core\Env::cargar(RAIZ . '/.env');

// Misma zona horaria en la base, Conexion.php.
date_default_timezone_set(App\Core\Env::get('APP_ZONA', 'America/Panama'));
