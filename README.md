# formulacion-pruebas
Sistema web en PHP para la identificación, clasificación, registro y generación de formularios orientados a la gestión de casos de prueba. Incluye autenticación, roles de administrador y testers, gestión de casos de prueba, técnicas de caja negra y caja blanca, plan de pruebas, registro de incidentes y evidencias.

## Usuarios de prueba

Los crea `database/seed.sql`.

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| admin | `admin` | `admin1234` |
| tester | `gloria` | `gloria1234` |
| tester | `pan` | `pan12345` |

## Producción

En `php.ini` (#123):

```ini
opcache.enable=1
opcache.validate_timestamps=0  ; PHP no revisa la fecha de cada archivo en cada petición
opcache.preload=/ruta/al/proyecto/config/precarga.php  ; no funciona en Windows (XAMPP): omitir
opcache.preload_user=www-data  ; el usuario del servidor web
```

Después de cada despliegue, reiniciar Apache o PHP-FPM para vaciar OPcache. Sin eso, PHP sigue usando el código anterior.

Con Apache, `mod_headers` guarda los CSS y JS un año (`public/.htaccess`). Con `mod_xsendfile` y `XSendFilePath` apuntando a `storage/evidencias`, Apache envía las evidencias y PHP queda libre.
