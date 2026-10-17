# Arquitectura

MVC por capas. `public/index.php` → `config/rutas.php` → controlador → servicio → modelo → vista.

Pantalla nueva: una ruta, un método en el controlador y una vista.

Documento imprimible: el controlador pasa `'documento' => Impresion::documento(N, $proyecto, $codigo)`. Con eso la página tiene encabezado, pie y firmas al imprimir.

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
| `helpers/` | HTML, íconos, catálogos, fechas, impresión. |
| `database/` | `schema.sql`, `seed.sql`. |
| `storage/` | Evidencias y logs. Sin acceso web. |
