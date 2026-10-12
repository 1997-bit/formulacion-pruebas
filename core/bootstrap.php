<?php

declare(strict_types=1);

// Autoloader propio y .env. App\Core\Env → core/Env.php.
spl_autoload_register(function (string $clase): void {
    if (!str_starts_with($clase, 'App\\')) {
        return;
    }
    $partes = explode('\\', substr($clase, 4));
    $partes[0] = strtolower($partes[0]);
    $archivo = RAIZ . '/' . implode('/', $partes) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

App\Core\Env::cargar(RAIZ . '/.env');

// Misma zona horaria en la base, Conexion.php.
date_default_timezone_set(App\Core\Env::get('APP_ZONA', 'America/Panama'));
