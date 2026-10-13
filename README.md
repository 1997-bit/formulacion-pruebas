# formulacion-pruebas
App web en PHP para registrar casos de prueba y generar los 10 formularios de testing. Roles admin y tester.

## Usuarios de prueba

Los crea `database/seed.sql`.

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| admin | `admin` | `admin1234` |
| tester | `gloria` | `gloria1234` |
| tester | `pan` | `pan12345` |

## Producción

```sh
composer install --no-dev
```

`storage/logs` necesita permiso de escritura.

`php.ini`:

```ini
opcache.enable=1
opcache.validate_timestamps=0
opcache.preload=/ruta/al/proyecto/config/precarga.php  ; no en Windows
opcache.preload_user=www-data
```

Reiniciar Apache o PHP-FPM después de cada despliegue.

Apache: `mod_headers` guarda CSS y JS un año. `mod_xsendfile` con `XSendFilePath` en `storage/evidencias` envía las evidencias.
