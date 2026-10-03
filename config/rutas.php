<?php

declare(strict_types=1);

// rol: null pública, 0 con sesión, 1 admin.
return [
    'GET /' => ['controlador' => 'Acceso', 'accion' => 'login', 'rol' => null, 'rf' => 'RF-01'],
    'POST /' => ['controlador' => 'Acceso', 'accion' => 'entrar', 'rol' => null, 'rf' => 'RF-01'],
    'GET /registro' => ['controlador' => 'Acceso', 'accion' => 'registro', 'rol' => null, 'rf' => 'RF-02'],
    'POST /registro' => ['controlador' => 'Acceso', 'accion' => 'registrar', 'rol' => null, 'rf' => 'RF-02'],
    'GET /salir' => ['controlador' => 'Acceso', 'accion' => 'salir', 'rol' => 0, 'rf' => 'RF-01'],
    'GET /dashboard' => ['controlador' => 'Panel', 'accion' => 'ver', 'rol' => 0, 'rf' => 'RF-23'],
    'GET /casos/editar' => ['controlador' => 'Casos', 'accion' => 'editar', 'rol' => 0, 'rf' => 'RF-06'],
    'GET /admin/usuarios' => ['controlador' => 'Usuarios', 'accion' => 'listar', 'rol' => 1, 'rf' => 'RF-03'],
    'GET /admin/usuarios/crear' => ['controlador' => 'Usuarios', 'accion' => 'crear', 'rol' => 1, 'rf' => 'RF-02'],
    'POST /admin/usuarios/crear' => ['controlador' => 'Usuarios', 'accion' => 'guardar', 'rol' => 1, 'rf' => 'RF-02'],
    'GET /admin/usuarios/editar' => ['controlador' => 'Usuarios', 'accion' => 'editar', 'rol' => 1, 'rf' => 'RF-03'],
    'POST /admin/usuarios/editar' => ['controlador' => 'Usuarios', 'accion' => 'actualizar', 'rol' => 1, 'rf' => 'RF-03'],
    'POST /admin/usuarios/eliminar' => ['controlador' => 'Usuarios', 'accion' => 'eliminar', 'rol' => 1, 'rf' => 'RF-03'],
];
