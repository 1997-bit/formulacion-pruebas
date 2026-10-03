-- Datos de prueba, después de schema.sql. Usuarios y claves en el README.

INSERT INTO usuarios (id, nombre, usuario, clave, rol) VALUES
    (1, 'Administrador', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$QmNMQXRHMkduenhXdmY5Lw$UYbZvbEjII90V3ZvyIJ2G2MBDb73EkeMiLvtTQQZpWU', 1),
    (2, 'Gloria', 'gloria', '$argon2id$v=19$m=65536,t=4,p=1$UWtlRVhPTU0uL1dtVFBMeg$yDi0W7fj0U7wOxKDuHt4XrPuHtDLecTjastnlkufpOY', 0),
    (3, 'Pan', 'pan', '$argon2id$v=19$m=65536,t=4,p=1$RFk1SnJOQ0czNXJtUGp4dg$29iQIAs7lw03/d1mb+NQLIAXbsuo+mglME9r94ynecc', 0);

-- El primero es este sistema; sus requerimientos salen del IR.
INSERT INTO proyectos (id, nombre, descripcion) VALUES
    (1, 'Formulación de pruebas', 'Este sistema: casos de prueba, formularios y evidencias.'),
    (2, 'Biblioteca escolar', 'Préstamo y devolución de libros para estudiantes.');

-- Gloria en los dos; Pan solo en el primero.
INSERT INTO proyecto_miembros (proyecto_id, usuario_id) VALUES
    (1, 2), (1, 3), (2, 2);

INSERT INTO requerimientos (id, proyecto_id, codigo, descripcion, no_funcional) VALUES
    (1, 1, 'RF-01', 'Iniciar sesión con usuario y contraseña. Un inicio fallido no dice si el usuario existe.', 0),
    (2, 1, 'RNF-03', 'Evidencias: solo png, jpg, txt o log, o un enlace. Máximo 5 MB. El archivo se renombra y se guarda fuera de la raíz pública.', 1),
    (3, 2, 'RF-01', 'Prestar un libro. No se presta si el estudiante tiene 3 préstamos activos o una multa sin pagar.', 0);

INSERT INTO casos_prueba (id, proyecto_id, requerimiento_id, codigo, tipo_prueba, subtecnica, modulo, plataforma,
    entorno, objetivo, precondiciones, entrada, pasos, resultado_esperado, fecha_inicio, fecha_fin,
    estado, resultado_obtenido, observaciones, creado_por, creado_en, anotado_por, anotado_en) VALUES
    (1, 1, 1, 'SIS-001', 3, 1, 'Acceso', 1,
        'Windows 11, Chrome 129, PHP 8.3, MariaDB 11.4',
        'Verificar que un inicio fallido muestra el mismo mensaje si la contraseña es incorrecta o si el usuario no existe.',
        'Existe el usuario gloria. No existe el usuario noexiste.',
        'Clase 1: usuario gloria, contraseña Clave-mala1\nClase 2: usuario noexiste, contraseña Clave-mala1',
        '1. Abrir la página de inicio de sesión.\n2. Entrar con los datos de la clase 1 y anotar el mensaje.\n3. Entrar con los datos de la clase 2 y anotar el mensaje.\n4. Comparar los dos mensajes.',
        'En los dos casos sale "Usuario o contraseña incorrectos." y no se inicia sesión.',
        '2026-10-01', '2026-10-02',
        1, 'Los dos intentos mostraron "Usuario o contraseña incorrectos." y la página siguió en el login.',
        'El usuario escrito se conserva en el campo; la contraseña no.',
        2, '2026-10-01 09:15:00', 2, '2026-10-02 10:40:00'),
    (2, 1, 2, 'SEG-001', 9, 2, 'Evidencias', 1,
        'Ubuntu 24.04, Firefox 131, PHP 8.3, MySQL 8.0',
        'Verificar que solo se aceptan evidencias de hasta 5 MB con extensión permitida.',
        'Sesión iniciada como pan. Existe el caso SIS-001. Archivos preparados: captura-5mb.png (5 242 880 bytes), captura-5mb-mas1.png (5 242 881 bytes) y nota.php.',
        'captura-5mb.png, captura-5mb-mas1.png, nota.php',
        '1. Abrir el caso SIS-001 y anotar estado OK.\n2. Adjuntar captura-5mb.png y guardar.\n3. Repetir con captura-5mb-mas1.png.\n4. Repetir con nota.php.\n5. Revisar storage/evidencias/ y la carpeta public/.',
        'Se acepta el archivo de 5 MB exactos con un nombre nuevo en storage/evidencias/. El de 5 MB y 1 byte y el .php se rechazan con un mensaje junto al campo. Nada se guarda en public/.',
        '2026-10-02', '2026-10-06',
        0, NULL, NULL,
        3, '2026-10-02 14:05:00', NULL, NULL),
    (3, 2, 3, 'INT-001', 2, 3, 'Préstamos', 1,
        'Windows 10, Edge 129, servidor de pruebas de la biblioteca',
        'Verificar las reglas del préstamo con una tabla de decisión: préstamos activos y multa pendiente.',
        'Existen los estudiantes 2024-0101 (0 préstamos, sin multa), 2024-0102 (3 préstamos, sin multa) y 2024-0103 (1 préstamo, multa de RD$50). Hay ejemplares disponibles del libro ISBN 978-84-376-0494-7.',
        'Regla 1: 2024-0101\nRegla 2: 2024-0102\nRegla 3: 2024-0103\nLibro: ISBN 978-84-376-0494-7',
        '1. Entrar como bibliotecario.\n2. Abrir Préstamos > Nuevo.\n3. Buscar al estudiante de la regla 1, elegir el libro y confirmar.\n4. Repetir con las reglas 2 y 3.',
        'Regla 1: se registra el préstamo y baja la existencia. Regla 2: no presta y dice "Tiene 3 préstamos activos". Regla 3: no presta y dice "Tiene una multa sin pagar".',
        '2026-10-03', '2026-10-07',
        0, NULL, NULL,
        2, '2026-10-03 08:30:00', NULL, NULL);

-- RF-24: un caso OK lleva al menos una evidencia.
INSERT INTO evidencias (caso_id, tipo, enlace, descripcion, subido_por, subido_en) VALUES
    (1, 4, 'https://github.com/1997-bit/formulacion-pruebas/issues/38', 'Registro de la prueba con las capturas de los dos mensajes.', 2, '2026-10-02 10:40:00');
