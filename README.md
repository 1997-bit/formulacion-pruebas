# Safeguard

App web para registrar casos de prueba, darles seguimiento y generar los 10 formularios de testing. Roles admin y tester.

## Instalación con Docker

```sh
cp .env.example .env   # poner DB_USER y DB_PASS
docker compose up -d --build --wait
```

`--wait` termina cuando la app responde. Abrir http://localhost:8080. La primera vez, MySQL carga `schema.sql`, `seed.sql` y `permisos.sh`. El usuario de la app queda solo con SELECT, INSERT, UPDATE y DELETE. Base, evidencias y logs quedan en volúmenes.

Volver a crear la base: `docker compose down -v`.

## Instalación sin Docker

PHP 8.2+ con `pdo_mysql` y `mbstring`, MySQL 8 o MariaDB 10.6+, Composer.

```sh
composer install
cp .env.example .env   # poner DB_USER y DB_PASS
mysql -e "CREATE DATABASE safeguard CHARACTER SET utf8mb4"
mysql safeguard < database/schema.sql
mysql safeguard < database/seed.sql
MYSQL_DATABASE=safeguard MYSQL_USER=app bash database/permisos.sh | mysql   # usuario 'app'@'%'
php -S 127.0.0.1:8000 -t public
```

## Usuarios de prueba

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| admin | `admin` | `admin1234` |
| tester | `gloria` | `gloria1234` |
| tester | `pan` | `pan12345` |

Las fechas del seed se corren a la semana en que se carga. Las 5 capturas y logs del seed no están en el repo: sin ellos, esas evidencias dan 404.

## Desarrollo

```sh
composer test   # PHPUnit
composer stan   # PHPStan
composer lint   # php-cs-fixer, sin cambiar archivos
composer fix    # php-cs-fixer
```

## Versiones

[SemVer 2.0.0](https://semver.org/spec/v2.0.0.html) y [Keep a Changelog 2.0.0](CHANGELOG.md). En cada merge de `staging` a `main` con `feat` o `fix`:

1. Mover lo de `[Unreleased]` a la versión nueva, con la fecha. `feat` sube MINOR, `fix` sube PATCH. Un cambio que rompe algo lleva `**Breaking:**` y sube MAJOR.
2. Tag anotado en el merge: `git tag -a v1.5.0 -m v1.5.0 <commit> && git push origin v1.5.0`.

## Producción sin Docker

La imagen ya trae esto.

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
| [Changelog](CHANGELOG.md) | Versiones |
