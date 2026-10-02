<?php
declare(strict_types=1);

// Panel segun rol (docs/IR.md, seccion 3).
define('RAIZ', dirname(__DIR__));
require RAIZ . '/core/bootstrap.php';

use App\Core\Vista;

// Temporal hasta que exista el inicio de sesion (RF-01): usuario fijo para ver el layout.
// Cambie 'rol' a 0 (general) para ver el menu sin la seccion de Administracion.
$usuario = ['nombre' => 'Usuario Demo', 'usuario' => 'demo', 'rol' => 1];

Vista::pagina('dashboard', [
    'titulo' => 'Panel',
    'usuario' => $usuario,
    'migas' => [
        ['texto' => 'Plataforma'],
        ['texto' => 'Panel'],
    ],
]);
