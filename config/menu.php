<?php

declare(strict_types=1);

// Menu del sidebar. Rutas y permisos segun docs/IR.md (seccion 3 y matriz 7.1).
// 'rol' => 1 (admin, ver config/catalogos.php) oculta el grupo o el elemento al rol tester.
// Ocultar en el menu no protege nada: cada pagina debe validar el rol en el servidor (RF-21).
return [
    [
        'grupo' => 'Plataforma',
        'elementos' => [
            ['texto' => 'Panel', 'icono' => 'layout-dashboard', 'ruta' => '/dashboard'],
            ['texto' => 'Casos de prueba', 'icono' => 'clipboard-list', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/casos/listar.php'],
                ['texto' => 'Registrar', 'ruta' => '/casos/registrar.php'],
            ]],
            ['texto' => 'Requerimientos', 'icono' => 'list-checks', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/requerimientos/listar.php'],
                ['texto' => 'Registrar', 'ruta' => '/requerimientos/registrar.php'],
            ]],
            ['texto' => 'Incidentes', 'icono' => 'bug', 'ruta' => '/formularios/incidentes.php'],
            ['texto' => 'Reporte de cierre', 'icono' => 'chart-column', 'ruta' => '/reportes/cierre.php'],
        ],
    ],
    [
        'grupo' => 'Formularios',
        'elementos' => [
            ['texto' => 'Técnicas', 'icono' => 'clipboard-check', 'hijos' => [
                ['texto' => 'Clases de equivalencia', 'ruta' => '/formularios/clases_equivalencia.php'],
                ['texto' => 'Valor límite', 'ruta' => '/formularios/valor_limite.php'],
                ['texto' => 'Tabla de decisión', 'ruta' => '/formularios/tabla_decision.php'],
                ['texto' => 'Cobertura caja blanca', 'ruta' => '/formularios/cobertura_blanca.php'],
            ]],
            ['texto' => 'Proyecto', 'icono' => 'file-text', 'hijos' => [
                ['texto' => 'Plan de pruebas', 'ruta' => '/formularios/plan_pruebas.php'],
                ['texto' => 'Portafolio de evidencias', 'ruta' => '/formularios/portafolio.php'],
            ]],
            ['texto' => 'Evaluación', 'icono' => 'graduation-cap', 'hijos' => [
                ['texto' => 'Rúbrica', 'ruta' => '/formularios/rubrica.php', 'rol' => 1],
                ['texto' => 'Auto y coevaluación', 'ruta' => '/formularios/autoevaluacion.php'],
            ]],
        ],
    ],
    [
        'grupo' => 'Administración',
        'rol' => 1,
        'elementos' => [
            ['texto' => 'Usuarios', 'icono' => 'users', 'ruta' => '/admin/usuarios.php'],
            ['texto' => 'Proyectos', 'icono' => 'folder-kanban', 'ruta' => '/admin/proyectos.php'],
        ],
    ],
];
