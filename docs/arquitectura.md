# Arquitectura del sistema

## 1. Patrón

MVC por capas y toda petición entra por `public/index.php`.

`public/index.php` → `config/rutas.php` → controlador → servicio → modelo → MySQL → vista.

Una pantalla nueva es una línea en `config/rutas.php`, un método en el controlador y una vista.

## 2. Capas

| Capa | Carpeta | Hace | No hace |
| --- | --- | --- | --- |
| Controlador | `controllers/` | Lee `$_GET`, `$_POST` y `$_FILES`. Llama al servicio. Responde. | SQL. Reglas. |
| Servicio | `services/` | Valida, revisa permisos sobre datos, abre transacciones y guarda historial. | Leer la petición o la sesión. Imprimir. |
| Modelo | `models/` | Ejecuta SQL con PDO. Devuelve arreglos. | Validar. Decidir. |
| Vista | `views/` | HTML. | Consultas. Lógica. |

## 3. Carpetas

| Carpeta o archivo | Contenido |
| --- | --- |
| `public/` | `index.php` y `assets/`. |
| `public/paleta.php` | Catálogo de pantallas. No se entrega. |
| `config/rutas.php` | Ruta, controlador, acción, rol y RF. |
| `config/catalogos.php` | Estados, tipos, severidades, prioridades y roles. |
| `config/menu.php` | Menú del sidebar. |
| `config/Conexion.php` | Conexión PDO única. |
| `core/` | Arranque, ruteador, sesión, CSRF, respuestas, validación y paginación. |
| `helpers/` |  HTML, íconos, catálogos y fechas. |
| `database/` | `schema.sql` y `seed.sql`. |
| `storage/` | Evidencias y logs. Sin acceso web. |


