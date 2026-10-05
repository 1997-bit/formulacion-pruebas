-- #139: Solicitado por y Aprobado por. Solo para una base creada antes de #139; schema.sql ya los trae.
-- mysql casos_prueba < database/migracion_139.sql

ALTER TABLE casos_prueba
    ADD solicitado_por INT UNSIGNED NULL AFTER anotado_en,
    ADD aprobado_por INT UNSIGNED NULL AFTER solicitado_por,
    ADD FOREIGN KEY (solicitado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    ADD FOREIGN KEY (aprobado_por) REFERENCES usuarios (id) ON DELETE SET NULL;
