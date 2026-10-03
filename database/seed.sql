-- Datos de prueba, después de schema.sql.
-- Clave: panconqueso

INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES
    ('Admin Prueba', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$U3BNbGtEUnBWWXEwcHRpTw$IdbSqTuNCZR83hMq1wfnHC/XeVMzlUAZZs44qQdIhrY', 1),
    ('Pan Con Queso', 'pan', '$argon2id$v=19$m=65536,t=4,p=1$U3BNbGtEUnBWWXEwcHRpTw$IdbSqTuNCZR83hMq1wfnHC/XeVMzlUAZZs44qQdIhrY', 0);
