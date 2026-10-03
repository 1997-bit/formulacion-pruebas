<?php

declare(strict_types=1);

// Menú del sidebar. 'rol' => 1 lo oculta al tester; ocultar no protege (RF-21).
return [
    [
        'grupo' => 'Plataforma',
        'elementos' => [
            ['texto' => 'Panel', 'icono' => 'layout-dashboard', 'ruta' => '/dashboard'],
            ['texto' => 'Casos de prueba', 'icono' => 'clipboard-list', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/casos/listar'],
                ['texto' => 'Registrar', 'ruta' => '/casos/registrar'],
            ]],
            ['texto' => 'Requerimientos', 'icono' => 'list-checks', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/requerimientos/listar'],
                ['texto' => 'Registrar', 'ruta' => '/requerimientos/registrar'],
            ]],
            ['texto' => 'Incidentes', 'icono' => 'bug', 'ruta' => '/formularios/incidentes'],
            ['texto' => 'Reporte de cierre', 'icono' => 'chart-column', 'ruta' => '/reportes/cierre'],
        ],
    ],
    [
        'grupo' => 'Formularios',
        'elementos' => [
            ['texto' => 'Técnicas', 'icono' => 'clipboard-check', 'hijos' => [
                ['texto' => 'Clases de equivalencia', 'ruta' => '/formularios/clases_equivalencia'],
                ['texto' => 'Valor límite', 'ruta' => '/formularios/valor_limite'],
                ['texto' => 'Tabla de decisión', 'ruta' => '/formularios/tabla_decision'],
                ['texto' => 'Cobertura caja blanca', 'ruta' => '/formularios/cobertura_blanca'],
            ]],
            ['texto' => 'Proyecto', 'icono' => 'file-text', 'hijos' => [
                ['texto' => 'Plan de pruebas', 'ruta' => '/formularios/plan_pruebas'],
                ['texto' => 'Portafolio de evidencias', 'ruta' => '/formularios/portafolio'],
            ]],
            ['texto' => 'Evaluación', 'icono' => 'graduation-cap', 'hijos' => [
                ['texto' => 'Rúbrica', 'ruta' => '/formularios/rubrica', 'rol' => 1],
                ['texto' => 'Auto y coevaluación', 'ruta' => '/formularios/autoevaluacion'],
            ]],
        ],
    ],
    [
        'grupo' => 'Administración',
        'rol' => 1,
        'elementos' => [
            ['texto' => 'Usuarios', 'icono' => 'users', 'ruta' => '/admin/usuarios'],
            ['texto' => 'Proyectos', 'icono' => 'folder-kanban', 'ruta' => '/admin/proyectos'],
        ],
    ],
];
