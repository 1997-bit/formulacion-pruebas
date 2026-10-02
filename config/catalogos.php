<?php

declare(strict_types=1);

// Valores fijos del sistema: estados, severidades, tipos de prueba, roles.
// Es la única fuente: listas, insignias y validación salen de aquí (helpers/Catalogo.php).
// Tipos y números de cada valor: docs/tipos-datos.md.
//
// Cada valor es  número => ['texto' => …, 'variante' => …]
// - número: lo que se guarda en la base (TINYINT UNSIGNED). No se cambia ni se reutiliza;
//   un valor nuevo toma el siguiente número.
// - texto: lo que ve la persona. Se cambia libre.
// - variante: color de la insignia (exito, peligro, aviso, info, destacado, secundaria, borde).
//   Ver la tabla de insignias en public/paleta.php.
// El orden aquí es el orden en las listas.
return [
    'estado_caso' => [
        'nombre' => 'Estado del caso',
        'valores' => [
            0 => ['texto' => 'Pendiente', 'variante' => 'aviso'],
            1 => ['texto' => 'OK', 'variante' => 'exito'],
            2 => ['texto' => 'FAULT', 'variante' => 'peligro'],
        ],
    ],
    // La sigla va al inicio del código del caso (SIS-001).
    // Cambiar una sigla solo afecta a los casos nuevos: un código ya generado no cambia.
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
    // 1 a 3 son caja negra; 4 a 8, caja blanca. Las métricas del formulario 5 usan 4 a 8.
    'subtecnica' => [
        'nombre' => 'Sub-técnica',
        'valores' => [
            1 => ['texto' => 'Clases de equivalencia', 'variante' => 'borde'],
            2 => ['texto' => 'Valor límite', 'variante' => 'borde'],
            3 => ['texto' => 'Tabla de decisión', 'variante' => 'borde'],
            4 => ['texto' => 'Sentencia', 'variante' => 'borde'],
            5 => ['texto' => 'Decisión', 'variante' => 'borde'],
            6 => ['texto' => 'Condición', 'variante' => 'borde'],
            7 => ['texto' => 'Caminos', 'variante' => 'borde'],
            8 => ['texto' => 'Bucles', 'variante' => 'borde'],
        ],
    ],
    // De menor a mayor: ORDER BY severidad DESC deja lo más grave arriba.
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
            2 => ['texto' => 'Trabajo en equipo', 'variante' => 'borde'],
            3 => ['texto' => 'Cumplimiento de plazos', 'variante' => 'borde'],
        ],
    ],
    'rol' => [
        'nombre' => 'Rol',
        'valores' => [
            0 => ['texto' => 'general', 'variante' => 'borde'],
            1 => ['texto' => 'admin', 'variante' => 'secundaria'],
        ],
    ],
];
