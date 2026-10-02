<?php

declare(strict_types=1);

// Rutas del sistema (docs/IR.md, sección 3).
// 'MÉTODO /ruta' => [Controlador::class, 'accion', rol, 'RF']
// rol: null = público, 0 = cualquier sesión, 1 = solo admin (config/catalogos.php).
return [
    // Acceso: /, /registro, /salir, /dashboard (RF-01, RF-02, RF-23)

    // Administración: /admin/usuarios, /admin/proyectos (RF-03)

    // Requerimientos: /requerimientos/registrar, /requerimientos/listar

    // Casos: /casos/registrar, /casos/listar, /casos/editar, /casos/eliminar, /evidencias/ver (RF-04 a RF-08, RF-24)

    // Formularios: /formularios/* (RF-09 a RF-17)

    // Reportes: /reportes/cierre (RF-23)
];
