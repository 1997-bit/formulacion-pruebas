-- Esquema completo (IR, sección 9). Funciona en MySQL 8 y en MariaDB 10.6 o superior.
-- Cargar en una base vacía o existente: mysql casos_prueba < database/schema.sql
-- Borra y crea las tablas: se puede correr de nuevo para empezar desde cero.
-- Catálogos (TINYINT): el número es la clave en config/catalogos.php.
-- Borrado: por defecto RESTRICT; CASCADE solo en lo que no vale sin su dueño.
-- Collation explícita: la de cada motor por defecto no existe en el otro.
-- Formularios: la llave primaria es dueño + orden. InnoDB guarda las filas juntas y ordenadas,
-- y se guardan con DELETE + un INSERT de varias filas en una transacción.

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_trazabilidad;
DROP TABLE IF EXISTS portafolio, autoevaluaciones, rubrica_evaluaciones, plan_pruebas,
    cobertura_blanca, decision_celdas, decision_filas, valor_limite, clases_equivalencia,
    logs_cambios, incidentes, evidencias, casos_prueba,
    requerimientos, proyecto_miembros, proyectos, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Núcleo ----------

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL, -- nombre completo en un campo (RNF-05)
    usuario VARCHAR(30) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL, -- password_hash con PASSWORD_ARGON2ID
    rol TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 tester, 1 admin
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
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requerimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- RF-01, RNF-03
    descripcion TEXT NOT NULL,
    no_funcional TINYINT NOT NULL DEFAULT 0, -- 0 funcional, 1 no funcional
    UNIQUE (proyecto_id, codigo),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 1.
CREATE TABLE casos_prueba (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL, -- igual al del requerimiento; lo revisa el servicio
    requerimiento_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- sigla del tipo + consecutivo: SIS-001
    tipo_prueba TINYINT UNSIGNED NOT NULL, -- 1 UNI … 9 SEG
    subtecnica TINYINT UNSIGNED NOT NULL, -- 1 a 10 caja negra, 11 a 20 caja blanca
    modulo VARCHAR(100) NOT NULL,
    plataforma TINYINT UNSIGNED NOT NULL, -- 1 Web … 5 API
    entorno VARCHAR(255) NULL, -- detalle de la plataforma: SO, navegador, versión
    objetivo TEXT NOT NULL,
    precondiciones TEXT NULL,
    entrada TEXT NOT NULL,
    pasos TEXT NOT NULL,
    resultado_esperado TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 Pendiente, 1 OK, 2 FAULT
    resultado_obtenido TEXT NULL,
    observaciones TEXT NULL,
    creado_por INT UNSIGNED NOT NULL, -- RF-06: tester edita solo los suyos
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anotado_por INT UNSIGNED NULL, -- RF-24: quién y cuándo anotó el resultado
    anotado_en DATETIME NULL,
    UNIQUE (proyecto_id, codigo),
    INDEX (proyecto_id, estado), -- filtros de /casos/listar
    CHECK (fecha_fin >= fecha_inicio),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id),
    FOREIGN KEY (creado_por) REFERENCES usuarios (id),
    FOREIGN KEY (anotado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Seguimiento ----------

-- Formulario 10.
CREATE TABLE incidentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL, -- igual al del caso; da el consecutivo del código
    caso_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- BUG-001
    titulo VARCHAR(150) NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    pasos TEXT NOT NULL,
    resultado_esperado TEXT NOT NULL,
    resultado_obtenido TEXT NOT NULL,
    severidad TINYINT UNSIGNED NOT NULL, -- 1 Baja, 2 Media, 3 Alta, 4 Crítica
    prioridad TINYINT UNSIGNED NOT NULL, -- 1 Baja, 2 Media, 3 Alta
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 Abierto, 1 En progreso, 2 Cerrado
    es_stopper TINYINT NOT NULL DEFAULT 0, -- 0 no, 1 sí
    asignado_id INT UNSIGNED NULL,
    creado_por INT UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (proyecto_id, codigo),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE RESTRICT,
    FOREIGN KEY (asignado_id) REFERENCES usuarios (id),
    FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una fila por evidencia marcada en el formulario 1 o el 10.
CREATE TABLE evidencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caso_id INT UNSIGNED NOT NULL,
    incidente_id INT UNSIGNED NULL, -- lleno si se subió con un incidente del caso
    tipo TINYINT UNSIGNED NOT NULL, -- 1 Captura, 2 Log, 3 Documento, 4 Enlace
    archivo VARCHAR(50) NULL, -- nombre aleatorio en storage/evidencias/
    nombre_original VARCHAR(255) NULL,
    enlace VARCHAR(500) NULL,
    descripcion VARCHAR(255) NOT NULL, -- texto alternativo
    subido_por INT UNSIGNED NOT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK ((tipo = 4) = (enlace IS NOT NULL) AND (tipo = 4) = (archivo IS NULL)),
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE RESTRICT,
    FOREIGN KEY (incidente_id) REFERENCES incidentes (id) ON DELETE RESTRICT,
    FOREIGN KEY (subido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF-20. Valores de catálogo se guardan como número y se muestran con Catalogo::texto().
CREATE TABLE logs_cambios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caso_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    campo VARCHAR(50) NOT NULL, -- nombre de la columna de casos_prueba
    antes TEXT NULL,
    despues TEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Formularios 2 a 5: de un requerimiento ----------

-- Formulario 2.
CREATE TABLE clases_equivalencia (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL, -- posición en pantalla, desde 1
    campo VARCHAR(100) NOT NULL,
    clase_valida TEXT NOT NULL,
    clases_invalidas TEXT NOT NULL,
    valores_representativos VARCHAR(255) NOT NULL,
    resultado_esperado TEXT NOT NULL,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 3. Mínimo y máximo son texto: pueden ser números, fechas o largos.
CREATE TABLE valor_limite (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    campo VARCHAR(100) NOT NULL,
    rango_valido VARCHAR(100) NOT NULL,
    minimo VARCHAR(50) NOT NULL,
    maximo VARCHAR(50) NOT NULL,
    valores_limite VARCHAR(255) NOT NULL,
    resultado_esperado TEXT NOT NULL,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 4. Cada fila es una condición o una acción; cada columna, una regla.
CREATE TABLE decision_filas (
    requerimiento_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    es_accion TINYINT NOT NULL DEFAULT 0, -- 0 condición, 1 acción
    texto VARCHAR(255) NOT NULL,
    PRIMARY KEY (requerimiento_id, orden),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo se guardan las celdas marcadas: una condición sin fila aquí es "—"; una acción, vacía.
CREATE TABLE decision_celdas (
    requerimiento_id INT UNSIGNED NOT NULL,
    fila_orden TINYINT UNSIGNED NOT NULL,
    regla TINYINT UNSIGNED NOT NULL, -- número de columna, desde 1
    valor TINYINT NOT NULL, -- condición: 1 V, 0 F. Acción: 1 X
    PRIMARY KEY (requerimiento_id, fila_orden, regla),
    FOREIGN KEY (requerimiento_id, fila_orden) REFERENCES decision_filas (requerimiento_id, orden) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 5. El porcentaje lo calcula la base: no puede diferir del que se ve.
CREATE TABLE cobertura_blanca (
    requerimiento_id INT UNSIGNED NOT NULL,
    metrica TINYINT UNSIGNED NOT NULL, -- sub-técnica de caja blanca: 11, 12, 13, 16, 18
    total INT UNSIGNED NOT NULL,
    cubiertos INT UNSIGNED NOT NULL,
    porcentaje DECIMAL(5, 2) AS (IF(total = 0, 0, cubiertos * 100 / total)) STORED,
    herramienta VARCHAR(100) NOT NULL,
    PRIMARY KEY (requerimiento_id, metrica),
    CHECK (metrica BETWEEN 11 AND 20),
    CHECK (cubiertos <= total),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Formularios 6 a 9: de un proyecto ----------

-- Formulario 6. Uno por proyecto.
CREATE TABLE plan_pruebas (
    proyecto_id INT UNSIGNED NOT NULL PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    alcance TEXT NOT NULL,
    objetivos TEXT NOT NULL,
    estrategia TINYINT UNSIGNED NOT NULL, -- 1 Caja negra, 2 Caja blanca, 3 Mixta
    recursos TEXT NULL,
    cronograma TEXT NULL,
    criterios_aceptacion TEXT NOT NULL,
    riesgos TEXT NULL,
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 Borrador, 1 Aprobado, 2 Cerrado
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 7. Solo admin. El total sobre 30 es SUM(puntos): no se guarda.
CREATE TABLE rubrica_evaluaciones (
    proyecto_id INT UNSIGNED NOT NULL,
    criterio TINYINT UNSIGNED NOT NULL, -- 1 Diseño de casos … 6 Presentación
    puntos TINYINT UNSIGNED NOT NULL,
    evaluado_por INT UNSIGNED NOT NULL,
    evaluado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (proyecto_id, criterio),
    CHECK (puntos BETWEEN 1 AND 5),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (evaluado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 8. Autoevaluación cuando evaluador = evaluado. Promedios con AVG(puntos).
-- Las llaves a proyecto_miembros obligan a que ambos sean del proyecto.
CREATE TABLE autoevaluaciones (
    proyecto_id INT UNSIGNED NOT NULL,
    evaluador_id INT UNSIGNED NOT NULL,
    evaluado_id INT UNSIGNED NOT NULL,
    aspecto TINYINT UNSIGNED NOT NULL, -- 1 Comprensión de conceptos … 6 Cumplimiento de plazos
    puntos TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(500) NULL,
    PRIMARY KEY (proyecto_id, evaluador_id, evaluado_id, aspecto),
    CHECK (puntos BETWEEN 1 AND 5),
    FOREIGN KEY (proyecto_id, evaluador_id) REFERENCES proyecto_miembros (proyecto_id, usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (proyecto_id, evaluado_id) REFERENCES proyecto_miembros (proyecto_id, usuario_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formulario 9. Lo que entrega cada persona por semana de la unidad.
CREATE TABLE portafolio (
    proyecto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    orden TINYINT UNSIGNED NOT NULL,
    semana TINYINT UNSIGNED NOT NULL,
    evidencia VARCHAR(150) NOT NULL,
    tipo TINYINT UNSIGNED NOT NULL, -- 1 Documento … 5 Presentación
    fecha DATE NOT NULL,
    observaciones TEXT NULL,
    PRIMARY KEY (proyecto_id, usuario_id, orden),
    FOREIGN KEY (proyecto_id, usuario_id) REFERENCES proyecto_miembros (proyecto_id, usuario_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Vista ----------

-- Un requerimiento sin casos sale con caso NULL: así se ven los huecos.
-- Las evidencias se cuentan con subconsulta y no con GROUP BY: igual en ambos motores.
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
