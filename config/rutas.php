<?php

declare(strict_types=1);

// 'MÉTODO /camino' => [controladollr, acción, rol, RF]
// rol: null pública, 0 cualquier sesión, 1 solo admin (config/catalogos.php).
return [
    'GET /' => ['controlador' => 'Acceso', 'accion' => 'login', 'rol' => null, 'rf' => 'RF-01'],
    'GET /dashboard' => ['controlador' => 'Panel', 'accion' => 'ver', 'rol' => 0, 'rf' => 'RF-23'],
    'GET /casos/editar' => ['controlador' => 'Casos', 'accion' => 'editar', 'rol' => 0, 'rf' => 'RF-06'],
];
