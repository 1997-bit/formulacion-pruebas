<?php

declare(strict_types=1);

// Menú del sidebar. 'rol' => 1 lo oculta al tester; ocultar no protege (RF-21). 'atajo': secuencia de teclas (atajos.js).
// g s (Ajustes) está en views/partials/sidebar.php: va en el menú del usuario.
return [
    [
        'grupo' => 'Plataforma',
        'elementos' => [
            ['texto' => 'Panel', 'icono' => 'layout-dashboard', 'ruta' => '/dashboard', 'atajo' => 'g p'],
            ['texto' => 'Casos de prueba', 'icono' => 'clipboard-list', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/casos/listar', 'atajo' => 'g c'],
                ['texto' => 'Registrar', 'ruta' => '/casos/registrar'],
            ]],
            ['texto' => 'Requerimientos', 'icono' => 'list-checks', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/requerimientos/listar', 'atajo' => 'g r'],
                ['texto' => 'Registrar', 'ruta' => '/requerimientos/registrar'],
            ]],
            ['texto' => 'Incidentes', 'icono' => 'bug', 'hijos' => [
                ['texto' => 'Listar', 'ruta' => '/formularios/incidentes', 'atajo' => 'g i'],
                ['texto' => 'Registrar', 'ruta' => '/formularios/incidentes/registrar'],
            ]],
            ['texto' => 'Reporte de cierre', 'icono' => 'chart-column', 'ruta' => '/reportes/cierre', 'atajo' => 'g e'],
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
            ['texto' => 'Usuarios', 'icono' => 'users', 'ruta' => '/admin/usuarios', 'atajo' => 'g u'],
            ['texto' => 'Proyectos', 'icono' => 'folder-kanban', 'ruta' => '/admin/proyectos', 'atajo' => 'g o'],
        ],
    ],
];
