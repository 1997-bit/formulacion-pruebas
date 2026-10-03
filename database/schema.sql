-- Esquema: tablas del núcleo y de seguimiento (IR, sección 9).
-- Cargar en una base vacía o existente: mysql casos_prueba < database/schema.sql
-- Borra y crea las tablas: se puede correr de nuevo para empezar desde cero.
-- Catálogos (TINYINT): el número es la clave en config/catalogos.php.
-- Borrado: por defecto RESTRICT; CASCADE solo en lo que no vale sin su dueño (miembros, historial).

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS logs_cambios, incidentes, evidencias, casos_prueba,
    requerimientos, proyecto_miembros, proyectos, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL, -- nombre completo en un campo (RNF-05)
    usuario VARCHAR(30) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL, -- password_hash con PASSWORD_ARGON2ID
    rol TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 tester, 1 admin
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proyectos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proyecto_miembros (
    proyecto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (proyecto_id, usuario_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE requerimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- RF-01, RNF-03
    descripcion TEXT NOT NULL,
    no_funcional TINYINT NOT NULL DEFAULT 0, -- 0 funcional, 1 no funcional
    UNIQUE (proyecto_id, codigo),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE casos_prueba (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT UNSIGNED NOT NULL, -- igual al del requerimiento; lo revisa el servicio
    requerimiento_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(10) NOT NULL, -- sigla del tipo + consecutivo: SIS-001
    tipo_prueba TINYINT UNSIGNED NOT NULL, -- 1 UNI … 9 SEG
    subtecnica TINYINT UNSIGNED NOT NULL, -- 1 a 10 caja negra, 11 a 20 caja blanca
    modulo VARCHAR(100) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Archivo o enlace: el tipo sale de cuál está lleno.
CREATE TABLE evidencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caso_id INT UNSIGNED NOT NULL,
    archivo VARCHAR(50) NULL, -- nombre aleatorio en storage/evidencias/
    nombre_original VARCHAR(255) NULL,
    enlace VARCHAR(500) NULL,
    descripcion VARCHAR(255) NOT NULL, -- texto alternativo
    subido_por INT UNSIGNED NOT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (archivo IS NOT NULL OR enlace IS NOT NULL),
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE RESTRICT,
    FOREIGN KEY (subido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE incidentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caso_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    pasos TEXT NOT NULL,
    severidad TINYINT UNSIGNED NOT NULL, -- 1 Baja, 2 Media, 3 Alta, 4 Crítica
    prioridad TINYINT UNSIGNED NOT NULL, -- 1 Baja, 2 Media, 3 Alta
    estado TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0 Abierto, 1 En progreso, 2 Cerrado
    es_stopper TINYINT NOT NULL DEFAULT 0, -- 0 no, 1 sí
    asignado_id INT UNSIGNED NULL,
    creado_por INT UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caso_id) REFERENCES casos_prueba (id) ON DELETE RESTRICT,
    FOREIGN KEY (asignado_id) REFERENCES usuarios (id),
    FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
