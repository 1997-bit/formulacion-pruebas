-- Esquema (IR §9). MySQL 8 o MariaDB 10.6+. Borra y crea todo: mysql casos_prueba < database/schema.sql
-- TINYINT de catálogo: clave en config/catalogos.php. Collation explícita: compatible con ambos motores.
-- Formularios: llave dueño + orden; se guardan con DELETE + un INSERT en transacción.

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_trazabilidad;
DROP TABLE IF EXISTS portafolio, autoevaluaciones, rubrica_evaluaciones, plan_pruebas,
    cobertura_blanca, decision_celdas, decision_filas, valor_limite, clases_equivalencia,
    logs_cambios, incidentes, evidencias, casos_prueba,
    requerimientos, proyecto_miembros, proyectos, usuarios, intentos_acceso;
SET FOREIGN_KEY_CHECKS = 1;

-- Núcleo

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL, -- RNF-05
    usuario VARCHAR(30) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL, -- Argon2id
    rol TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sesion_version INT UNSIGNED NOT NULL DEFAULT 0, -- sube al cambiar clave o rol: cierra las sesiones abiertas
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proyectos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proyecto_miembros (
    proyecto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (proyecto_id, usuario_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id) -- RESTRICT: borrar a un miembro no borra sus coevaluaciones (BUG-020)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requerimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    descripcion TEXT NOT NULL,
    no_funcional TINYINT NOT NULL DEFAULT 0,
    numero INT UNSIGNED AS (CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) STORED, -- RF-01 -> 1
    UNIQUE (proyecto_id, codigo),
    UNIQUE (id, proyecto_id), -- destino de la llave compuesta de casos_prueba
    INDEX (proyecto_id, no_funcional, numero), -- orden de la lista
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F1
CREATE TABLE casos_prueba (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL,
    requerimiento_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- SIS-001
    tipo_prueba TINYINT UNSIGNED NOT NULL,
    subtecnica TINYINT UNSIGNED NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    plataforma TINYINT UNSIGNED NOT NULL,
    entorno VARCHAR(255) NULL,
    objetivo TEXT NOT NULL,
    precondiciones TEXT NULL,
    entrada TEXT NOT NULL,
    pasos TEXT NOT NULL,
    resultado_esperado TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0,
    resultado_obtenido TEXT NULL,
    observaciones TEXT NULL,
    creado_por INT UNSIGNED NOT NULL, -- RF-06
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anotado_por INT UNSIGNED NULL, -- RF-24
    anotado_en DATETIME NULL,
    -- Para numerar y ordenar: SIS-1000 va después de SIS-999. codigo no cambia aunque cambie el tipo (RF-06).
    sigla VARCHAR(3) AS (SUBSTRING_INDEX(codigo, '-', 1)) STORED,
    numero INT UNSIGNED AS (CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) STORED,
    UNIQUE (proyecto_id, codigo),
    UNIQUE (proyecto_id, sigla, numero),
    UNIQUE (id, proyecto_id), -- destino de la llave compuesta de incidentes
    INDEX (proyecto_id, estado),
    CHECK (fecha_fin >= fecha_inicio),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    FOREIGN KEY (requerimiento_id, proyecto_id) REFERENCES requerimientos (id, proyecto_id), -- mismo proyecto que el requerimiento
    FOREIGN KEY (creado_por) REFERENCES usuarios (id),
    FOREIGN KEY (anotado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seguimiento

-- F10
CREATE TABLE incidentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL,
    caso_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    pasos TEXT NOT NULL,
    resultado_esperado TEXT NOT NULL,
    resultado_obtenido TEXT NOT NULL,
    severidad TINYINT UNSIGNED NOT NULL,
    prioridad TINYINT UNSIGNED NOT NULL,
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0,
    es_stopper TINYINT NOT NULL DEFAULT 0,
    asignado_id INT UNSIGNED NULL,
    creado_por INT UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    numero INT UNSIGNED AS (CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) STORED, -- BUG-001 -> 1
    UNIQUE (proyecto_id, codigo),
    UNIQUE (proyecto_id, numero),
    INDEX (proyecto_id, estado, severidad DESC, numero), -- orden de la lista
    INDEX (proyecto_id, es_stopper, estado),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    FOREIGN KEY (caso_id, proyecto_id) REFERENCES casos_prueba (id, proyecto_id) ON DELETE RESTRICT, -- mismo proyecto que el caso
    FOREIGN KEY (asignado_id) REFERENCES usuarios (id),
    FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evidencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caso_id INT UNSIGNED NOT NULL,
    tipo TINYINT UNSIGNED NOT NULL,
    archivo VARCHAR(50) NULL, -- en storage/evidencias/
    nombre_original VARCHAR(255) NULL,
    enlace VARCHAR(500) NULL,
    descripcion VARCHAR(255) NOT NULL,
    subido_por INT UNSIGNED NOT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (subido_en), -- orden del portafolio
    CHECK ((tipo = 4) = (enlace IS NOT NULL) AND (tipo = 4) = (archivo IS NULL)),
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE RESTRICT,
    FOREIGN KEY (subido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF-20
CREATE TABLE logs_cambios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(30) NOT NULL, -- casos_prueba, incidentes o plan_pruebas
    registro_id INT UNSIGNED NOT NULL, -- id del registro; en plan_pruebas, el proyecto
    usuario_id INT UNSIGNED NOT NULL,
    campo VARCHAR(50) NOT NULL,
    antes TEXT NULL,
    despues TEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (tabla, registro_id, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RNF-02. clave: 'u:<usuario>' en el login, 'ip:<ip>' en el registro.
CREATE TABLE intentos_acceso (
    clave VARCHAR(64) PRIMARY KEY,
    fallos INT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME(3) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F2 a F5: de un requerimiento

-- F2
CREATE TABLE clases_equivalencia (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    campo VARCHAR(100) NOT NULL,
    clase_valida TEXT NOT NULL,
    clases_invalidas TEXT NOT NULL,
    valores_representativos VARCHAR(255) NOT NULL,
    resultado_esperado TEXT NOT NULL,
    guardado_por INT UNSIGNED NOT NULL,
    guardado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE,
    FOREIGN KEY (guardado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F3. Mínimo y máximo en texto: números, fechas o largos.
CREATE TABLE valor_limite (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    campo VARCHAR(100) NOT NULL,
    rango_valido VARCHAR(100) NOT NULL,
    minimo VARCHAR(50) NOT NULL,
    maximo VARCHAR(50) NOT NULL,
    valores_limite VARCHAR(255) NOT NULL,
    resultado_esperado TEXT NOT NULL,
    guardado_por INT UNSIGNED NOT NULL,
    guardado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE,
    FOREIGN KEY (guardado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F4: filas son condiciones o acciones; columnas, reglas.
CREATE TABLE decision_filas (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    es_accion TINYINT NOT NULL DEFAULT 0,
    texto VARCHAR(255) NOT NULL,
    guardado_por INT UNSIGNED NOT NULL,
    guardado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE,
    FOREIGN KEY (guardado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo celdas marcadas: sin fila es "—".
CREATE TABLE decision_celdas (
    requerimiento_id INT UNSIGNED NOT NULL,
    fila_orden TINYINT UNSIGNED NOT NULL,
    regla TINYINT UNSIGNED NOT NULL,
    valor TINYINT NOT NULL, -- condición 1 V, 0 F; acción 1 X
    PRIMARY KEY (requerimiento_id, fila_orden, regla),
    FOREIGN KEY (requerimiento_id, fila_orden) REFERENCES decision_filas (requerimiento_id, orden) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F5. Una fila por métrica medida. El porcentaje lo calcula el servidor al guardar.
CREATE TABLE cobertura_blanca (
    requerimiento_id INT UNSIGNED NOT NULL,
    metrica TINYINT UNSIGNED NOT NULL, -- sub-técnica de caja blanca
    total INT UNSIGNED NOT NULL,
    cubiertos INT UNSIGNED NOT NULL,
    porcentaje TINYINT UNSIGNED NOT NULL, -- entero, como en pantalla
    herramienta VARCHAR(100) NOT NULL,
    guardado_por INT UNSIGNED NOT NULL,
    guardado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (requerimiento_id, metrica),
    CHECK (metrica BETWEEN 11 AND 20),
    CHECK (total > 0),
    CHECK (cubiertos <= total),
    CHECK (porcentaje <= 100),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE,
    FOREIGN KEY (guardado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F6 a F9: de un proyecto

-- F6
CREATE TABLE plan_pruebas (
    proyecto_id INT UNSIGNED NOT NULL PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    alcance TEXT NOT NULL,
    objetivos TEXT NOT NULL,
    estrategia TINYINT UNSIGNED NOT NULL,
    recursos TEXT NULL,
    cronograma TEXT NULL,
    criterios_aceptacion TEXT NOT NULL,
    riesgos TEXT NULL,
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F7. Total = SUM(puntos).
CREATE TABLE rubrica_evaluaciones (
    proyecto_id INT UNSIGNED NOT NULL,
    criterio TINYINT UNSIGNED NOT NULL,
    puntos TINYINT UNSIGNED NOT NULL,
    evaluado_por INT UNSIGNED NOT NULL,
    evaluado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (proyecto_id, criterio),
    CHECK (puntos BETWEEN 1 AND 5),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (evaluado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F8. Auto si evaluador = evaluado. Ambos deben ser miembros.
CREATE TABLE autoevaluaciones (
    proyecto_id INT UNSIGNED NOT NULL,
    evaluador_id INT UNSIGNED NOT NULL,
    evaluado_id INT UNSIGNED NOT NULL,
    aspecto TINYINT UNSIGNED NOT NULL,
    puntos TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(500) NULL,
    PRIMARY KEY (proyecto_id, evaluador_id, evaluado_id, aspecto),
    CHECK (puntos BETWEEN 1 AND 5),
    FOREIGN KEY (proyecto_id, evaluador_id) REFERENCES proyecto_miembros (proyecto_id, usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (proyecto_id, evaluado_id) REFERENCES proyecto_miembros (proyecto_id, usuario_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Vista

-- Requerimiento sin casos: caso NULL. Subconsulta en vez de GROUP BY: igual en ambos motores.
CREATE VIEW v_trazabilidad AS
SELECT r.proyecto_id,
       r.id AS requerimiento_id,
       r.codigo AS requerimiento,
       c.id AS caso_id,
       c.codigo AS caso,
       c.tipo_prueba,
       c.estado,
       (SELECT COUNT(*) FROM evidencias e WHERE e.caso_id = c.id) AS evidencias
FROM requerimientos r
LEFT JOIN casos_prueba c ON c.requerimiento_id = r.id;
