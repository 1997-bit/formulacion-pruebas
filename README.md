# formulacion-pruebas

App web para registrar casos de prueba, darles seguimiento y generar los 10 formularios de testing. Roles admin y tester.

## Requisitos

- PHP 8.2+ con `pdo_mysql` y `mbstring`
- MySQL 8 o MariaDB 10.6+
- Composer

## Instalación

```sh
git clone <url> && cd formulacion-pruebas
composer install
cp .env.example .env   # poner DB_USER y DB_PASS
mysql -e "CREATE DATABASE casos_prueba CHARACTER SET utf8mb4"
mysql casos_prueba < database/schema.sql
mysql casos_prueba < database/seed.sql
php -S 127.0.0.1:8000 -t public
```

Abrir http://127.0.0.1:8000.

## Usuarios de prueba

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| admin | `admin` | `admin1234` |
| tester | `gloria` | `gloria1234` |
| tester | `pan` | `pan12345` |

## Desarrollo

```sh
composer test   # PHPUnit
composer stan   # PHPStan
composer lint   # php-cs-fixer, sin cambiar archivos
composer fix    # php-cs-fixer
```

## Producción

```sh
composer install --no-dev
```

- `public/` es la raíz web. `storage/logs` necesita permiso de escritura.
- `LOG_NIVEL=info` en `.env`.
- `php.ini`:

  ```ini
  opcache.enable=1
  opcache.validate_timestamps=0
  opcache.preload=/ruta/al/proyecto/config/precarga.php  ; no en Windows
  opcache.preload_user=www-data
  ```

- Reiniciar Apache o PHP-FPM después de cada despliegue.
- Apache: `mod_headers` guarda CSS y JS un año. `mod_xsendfile` con `XSendFilePath` en `storage/evidencias` envía las evidencias.

## Documentación

| Doc | Contenido |
| --- | --- |
| [IR](docs/IR.md) | Requerimientos |
| [Arquitectura](docs/arquitectura.md) | Capas y carpetas |
| [Logs](docs/logs.md) | Eventos y lectura |
| [Medición](docs/medicion.md) | Rendimiento |
| [Bugs corregidos](docs/bugs_corregidos.md) | Cierre de bugs |
| [Enunciado](docs/enunciado.md) | Tarea original |
