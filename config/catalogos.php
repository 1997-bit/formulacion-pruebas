<?php

declare(strict_types=1);

// Única fuente de valores fijos. número => texto y variante (color de la insignia).
// El número va en la base: no se cambia ni se reutiliza. El orden aquí es el de las listas.
return [
    'estado_caso' => [
        'nombre' => 'Estado del caso',
        'valores' => [
            0 => ['texto' => 'Pendiente', 'variante' => 'aviso'],
            1 => ['texto' => 'OK', 'variante' => 'exito'],
            2 => ['texto' => 'FAULT', 'variante' => 'peligro'],
        ],
    ],
    // La sigla abre el código (SIS-001). Cambiarla no toca códigos ya generados.
    'tipo_prueba' => [
        'nombre' => 'Tipo de prueba',
        'valores' => [
            1 => ['texto' => 'Unitaria', 'sigla' => 'UNI', 'variante' => 'borde'],
            2 => ['texto' => 'Integración', 'sigla' => 'INT', 'variante' => 'borde'],
            3 => ['texto' => 'Sistema', 'sigla' => 'SIS', 'variante' => 'borde'],
            4 => ['texto' => 'Aceptación', 'sigla' => 'ACE', 'variante' => 'borde'],
            5 => ['texto' => 'Mantenimiento', 'sigla' => 'MAN', 'variante' => 'borde'],
            6 => ['texto' => 'Regresión', 'sigla' => 'REG', 'variante' => 'borde'],
            7 => ['texto' => 'Smoke', 'sigla' => 'SMK', 'variante' => 'borde'],
            8 => ['texto' => 'Performance', 'sigla' => 'PER', 'variante' => 'borde'],
            9 => ['texto' => 'Seguridad', 'sigla' => 'SEG', 'variante' => 'borde'],
        ],
    ],
    // 1-10 caja negra, 11-20 caja blanca. Métricas del F5: 11, 12, 13, 16 y 18.
    'subtecnica' => [
        'nombre' => 'Sub-técnica',
        'valores' => [
            1 => ['texto' => 'Partición de equivalencia', 'variante' => 'borde'],
            2 => ['texto' => 'Análisis de valores límite', 'variante' => 'borde'],
            3 => ['texto' => 'Tabla de decisiones', 'variante' => 'borde'],
            4 => ['texto' => 'Transición de estados', 'variante' => 'borde'],
            5 => ['texto' => 'Grafos causa-efecto', 'variante' => 'borde'],
            6 => ['texto' => 'Combinatoria por pares', 'variante' => 'borde'],
            7 => ['texto' => 'Casos de uso', 'variante' => 'borde'],
            8 => ['texto' => 'Escenarios', 'variante' => 'borde'],
            9 => ['texto' => 'Basada en modelos', 'variante' => 'borde'],
            10 => ['texto' => 'Aleatoria', 'variante' => 'borde'],
            11 => ['texto' => 'Cobertura de sentencias', 'variante' => 'borde'],
            12 => ['texto' => 'Cobertura de decisiones', 'variante' => 'borde'],
            13 => ['texto' => 'Cobertura de condiciones', 'variante' => 'borde'],
            14 => ['texto' => 'Cobertura de decisión/condición', 'variante' => 'borde'],
            15 => ['texto' => 'Cobertura de condiciones múltiples', 'variante' => 'borde'],
            16 => ['texto' => 'Cobertura de caminos', 'variante' => 'borde'],
            17 => ['texto' => 'Cobertura de caminos básicos', 'variante' => 'borde'],
            18 => ['texto' => 'Cobertura de bucles', 'variante' => 'borde'],
            19 => ['texto' => 'Cobertura de funciones/métodos', 'variante' => 'borde'],
            20 => ['texto' => 'Cobertura de clases', 'variante' => 'borde'],
        ],
    ],
    'plataforma' => [
        'nombre' => 'Plataforma',
        'valores' => [
            1 => ['texto' => 'Web', 'variante' => 'borde'],
            2 => ['texto' => 'Escritorio', 'variante' => 'borde'],
            3 => ['texto' => 'Android', 'variante' => 'borde'],
            4 => ['texto' => 'iOS', 'variante' => 'borde'],
            5 => ['texto' => 'API', 'variante' => 'borde'],
        ],
    ],
    // Captura png/jpg, log txt/log, documento pdf (RNF-03).
    'tipo_evidencia' => [
        'nombre' => 'Tipo de evidencia',
        'valores' => [
            1 => ['texto' => 'Captura', 'variante' => 'borde'],
            2 => ['texto' => 'Log', 'variante' => 'borde'],
            3 => ['texto' => 'Documento', 'variante' => 'borde'],
            4 => ['texto' => 'Enlace', 'variante' => 'borde'],
        ],
    ],
    // De menor a mayor: DESC deja lo grave arriba.
    'severidad' => [
        'nombre' => 'Severidad',
        'valores' => [
            1 => ['texto' => 'Baja', 'variante' => 'secundaria'],
            2 => ['texto' => 'Media', 'variante' => 'aviso'],
            3 => ['texto' => 'Alta', 'variante' => 'destacado'],
            4 => ['texto' => 'Crítica', 'variante' => 'peligro'],
        ],
    ],
    'prioridad' => [
        'nombre' => 'Prioridad',
        'valores' => [
            1 => ['texto' => 'Baja', 'variante' => 'secundaria'],
            2 => ['texto' => 'Media', 'variante' => 'aviso'],
            3 => ['texto' => 'Alta', 'variante' => 'destacado'],
        ],
    ],
    'estado_incidente' => [
        'nombre' => 'Estado del incidente',
        'valores' => [
            0 => ['texto' => 'Abierto', 'variante' => 'aviso'],
            1 => ['texto' => 'En progreso', 'variante' => 'info'],
            2 => ['texto' => 'Cerrado', 'variante' => 'exito'],
        ],
    ],
    'criterio_rubrica' => [
        'nombre' => 'Criterio de la rúbrica',
        'valores' => [
            1 => ['texto' => 'Diseño de casos', 'variante' => 'borde'],
            2 => ['texto' => 'Aplicación de técnicas', 'variante' => 'borde'],
            3 => ['texto' => 'Cobertura', 'variante' => 'borde'],
            4 => ['texto' => 'Uso de herramientas', 'variante' => 'borde'],
            5 => ['texto' => 'Documentación', 'variante' => 'borde'],
            6 => ['texto' => 'Presentación', 'variante' => 'borde'],
        ],
    ],
    'aspecto_evaluacion' => [
        'nombre' => 'Aspecto de la auto y coevaluación',
        'valores' => [
            1 => ['texto' => 'Comprensión de conceptos', 'variante' => 'borde'],
            2 => ['texto' => 'Aplicación de técnicas', 'variante' => 'borde'],
            3 => ['texto' => 'Trabajo en equipo', 'variante' => 'borde'],
            4 => ['texto' => 'Uso de herramientas', 'variante' => 'borde'],
            5 => ['texto' => 'Calidad de documentación', 'variante' => 'borde'],
            6 => ['texto' => 'Cumplimiento de plazos', 'variante' => 'borde'],
        ],
    ],
    'estrategia' => [
        'nombre' => 'Estrategia del plan',
        'valores' => [
            1 => ['texto' => 'Caja negra', 'variante' => 'borde'],
            2 => ['texto' => 'Caja blanca', 'variante' => 'borde'],
            3 => ['texto' => 'Mixta', 'variante' => 'borde'],
        ],
    ],
    'estado_plan' => [
        'nombre' => 'Estado del plan',
        'valores' => [
            0 => ['texto' => 'Borrador', 'variante' => 'aviso'],
            1 => ['texto' => 'Aprobado', 'variante' => 'info'],
            2 => ['texto' => 'Cerrado', 'variante' => 'secundaria'],
        ],
    ],
    'tipo_portafolio' => [
        'nombre' => 'Tipo de evidencia del portafolio',
        'valores' => [
            1 => ['texto' => 'Documento', 'variante' => 'borde'],
            2 => ['texto' => 'Taller', 'variante' => 'borde'],
            3 => ['texto' => 'Laboratorio', 'variante' => 'borde'],
            4 => ['texto' => 'Proyecto', 'variante' => 'borde'],
            5 => ['texto' => 'Presentación', 'variante' => 'borde'],
        ],
    ],
    'rol' => [
        'nombre' => 'Rol',
        'valores' => [
            0 => ['texto' => 'tester', 'variante' => 'borde'],
            1 => ['texto' => 'admin', 'variante' => 'secundaria'],
        ],
    ],
];
