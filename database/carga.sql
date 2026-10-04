-- Datos de carga para medir (#90). No es parte del seed: se carga después de schema.sql y seed.sql.
-- mysql casos_prueba < database/carga.sql
-- Crea el proyecto "Carga" con 2 000 requerimientos, 20 000 casos, 20 000 incidentes y 12 000 evidencias.
-- Siempre las mismas filas: no usa azar. Corre en MySQL 8 y MariaDB 10.6 (sin tablas seq_ ni CTE recursivas).

INSERT INTO proyectos (nombre, descripcion) VALUES ('Carga', 'Datos generados para medir el rendimiento.');
SET @p = LAST_INSERT_ID();
INSERT INTO proyecto_miembros (proyecto_id, usuario_id) VALUES (@p, 2);

-- Números del 1 al 100 000.
CREATE TEMPORARY TABLE numeros (n INT UNSIGNED PRIMARY KEY);
INSERT INTO numeros
SELECT a.d + b.d * 10 + c.d * 100 + e.d * 1000 + f.d * 10000 + 1
FROM (SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
      UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
CROSS JOIN (SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
      UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
CROSS JOIN (SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
      UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
CROSS JOIN (SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
      UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) e
CROSS JOIN (SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
      UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) f;

-- 2 000 requerimientos: RF-0001 a RF-2000. Uno de cada 5 es no funcional.
INSERT INTO requerimientos (proyecto_id, codigo, descripcion, no_funcional)
SELECT @p, CONCAT('RF-', LPAD(n, 4, '0')), CONCAT('Requerimiento de carga ', n), n % 5 = 0
FROM numeros WHERE n <= 2000;

-- 20 000 casos, 10 por requerimiento. Estado: 40 % OK, 20 % FAULT, 40 % Pendiente.
INSERT INTO casos_prueba (proyecto_id, requerimiento_id, codigo, tipo_prueba, subtecnica, modulo, plataforma, objetivo,
                          entrada, pasos, resultado_esperado, fecha_inicio, fecha_fin, estado, resultado_obtenido,
                          observaciones, creado_por, anotado_por, anotado_en)
SELECT @p, r.id, CONCAT('SIS-', LPAD(t.n, 5, '0')), 3, 1, CONCAT('Módulo ', t.n % 12), 1,
       CONCAT('Verificar el comportamiento de carga número ', t.n, '.'), 'Entrada de carga',
       '1. Abrir la pantalla.\n2. Ejecutar el paso.\n3. Revisar el resultado.', 'El sistema responde como se espera.',
       '2026-09-01', '2026-09-30', t.e, IF(t.e = 0, NULL, 'Resultado de carga'), IF(t.e = 0, NULL, 'Observación de carga'),
       2, IF(t.e = 0, NULL, 2), IF(t.e = 0, NULL, '2026-09-15 10:00:00')
FROM (SELECT n, CASE WHEN n % 10 < 4 THEN 1 WHEN n % 10 < 6 THEN 2 ELSE 0 END AS e FROM numeros WHERE n <= 20000) t
JOIN requerimientos r ON r.proyecto_id = @p AND r.codigo = CONCAT('RF-', LPAD((t.n - 1) DIV 10 + 1, 4, '0'));

-- Una evidencia por caso OK o FAULT.
INSERT INTO evidencias (caso_id, tipo, enlace, descripcion, subido_por, subido_en)
SELECT id, 4, CONCAT('https://example.com/evidencia/', numero), 'Evidencia de carga', 2, '2026-09-15 10:00:00'
FROM casos_prueba WHERE proyecto_id = @p AND estado <> 0;

-- 20 000 incidentes, uno por caso. Estado abierto, en progreso o cerrado; 1 de cada 20 es stopper.
INSERT INTO incidentes (proyecto_id, caso_id, codigo, titulo, modulo, descripcion, pasos, resultado_esperado,
                        resultado_obtenido, severidad, prioridad, estado, es_stopper, asignado_id, creado_por)
SELECT @p, c.id, CONCAT('BUG-', LPAD(c.numero, 5, '0')), CONCAT('Defecto de carga ', c.numero), c.modulo,
       'Descripción de carga', 'Pasos de carga', 'Esperado', 'Obtenido',
       c.numero % 4 + 1, c.numero % 3 + 1, c.numero % 3, c.numero % 20 = 0, IF(c.numero % 2 = 0, 2, NULL), 2
FROM casos_prueba c WHERE c.proyecto_id = @p;

DROP TEMPORARY TABLE numeros;
ANALYZE TABLE requerimientos, casos_prueba, incidentes, evidencias;
