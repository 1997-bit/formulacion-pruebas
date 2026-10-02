<?php

declare(strict_types=1);

// Arranque sin Composer: autoloader propio y carga del .env.
// App\Core\Env -> core/Env.php, App\Models\Caso -> models/Caso.php, etc.
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

// Una sola zona para todo el sistema. MySQL usa la misma al conectar (config/Conexion.php).
$zona = App\Core\Env::get('APP_ZONA', 'America/Panama');
if (!in_array($zona, \DateTimeZone::listIdentifiers(), true)) {
    throw new \RuntimeException("APP_ZONA no es una zona horaria válida: {$zona}");
}
date_default_timezone_set($zona);
unset($zona);
