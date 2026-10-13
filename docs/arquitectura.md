# Arquitectura

MVC por capas. `public/index.php` → `config/rutas.php` → controlador → servicio → modelo → vista.

Pantalla nueva: una ruta, un método en el controlador y una vista.

| Capa | Hace | No hace |
| --- | --- | --- |
| `controllers/` | Lee la petición, llama al servicio, responde. | SQL, reglas. |
| `services/` | Valida, permisos, transacciones, historial. | Leer la petición, imprimir. |
| `models/` | SQL con PDO. | Validar, decidir. |
| `views/` | HTML. | Consultas, lógica. |

| Carpeta | Contenido |
| --- | --- |
| `config/` | Rutas, catálogos, menú, conexión PDO. |
| `core/` | Arranque, ruteador, sesión, CSRF, validación, paginación. |
| `helpers/` | HTML, íconos, catálogos, fechas. |
| `database/` | `schema.sql`, `seed.sql`. |
| `storage/` | Evidencias y logs. Sin acceso web. |
