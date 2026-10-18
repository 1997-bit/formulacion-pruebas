<?php

declare(strict_types=1);

// Composer: App\ y Monolog
require RAIZ . '/vendor/autoload.php';

App\Core\Env::cargar(RAIZ . '/.env');

// Zona horaria de la base, Conexion.php
date_default_timezone_set(App\Core\Env::get('APP_ZONA', 'America/Panama'));
