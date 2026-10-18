<?php

declare(strict_types=1);

// Artículos de /ayuda por grupo. slug => título. Solo estos slugs abren, docs/ayuda.md
// 'pantallas': ruta => slug del botón Ayuda de la barra
return [
    'articulos' => [
        'Empezar' => [
            'inicio-tester' => 'Empezar como tester',
            'inicio-admin' => 'Empezar como admin',
        ],
        'Tareas' => [
            'crear-caso' => 'Registrar un caso',
            'anotar-resultado' => 'Anotar el resultado',
            'registrar-incidente' => 'Registrar un incidente',
            'llenar-formulario' => 'Llenar un formulario',
        ],
        'Referencia' => [
            'estados' => 'Estados',
            'roles' => 'Roles y permisos',
            'mapa' => 'Mapa de la app',
        ],
        'Conceptos' => [
            'glosario' => 'Glosario',
        ],
    ],
    'pantallas' => [
        '/dashboard' => 'mapa',
        '/casos/listar' => 'estados',
        '/casos/registrar' => 'crear-caso',
        '/casos/editar' => 'crear-caso',
        '/casos/resultado' => 'anotar-resultado',
        '/formularios/incidentes' => 'estados',
        '/formularios/incidentes/registrar' => 'registrar-incidente',
        '/formularios/incidentes/ver' => 'estados',
        '/formularios/clases_equivalencia' => 'llenar-formulario',
        '/formularios/clases_equivalencia/editar' => 'llenar-formulario',
        '/formularios/valor_limite' => 'llenar-formulario',
        '/formularios/valor_limite/editar' => 'llenar-formulario',
        '/formularios/tabla_decision' => 'llenar-formulario',
        '/formularios/tabla_decision/editar' => 'llenar-formulario',
        '/formularios/cobertura_blanca' => 'llenar-formulario',
        '/formularios/cobertura_blanca/editar' => 'llenar-formulario',
        '/formularios/plan_pruebas' => 'llenar-formulario',
        '/formularios/plan_pruebas/editar' => 'llenar-formulario',
        '/admin/usuarios' => 'roles',
        '/admin/proyectos' => 'inicio-admin',
    ],
];
