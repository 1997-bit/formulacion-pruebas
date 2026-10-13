# Logs

Inventario de logs, ASVS 16.1.1. RNF-11.

## 1. Log de la app

| Tema | Valor |
| --- | --- |
| Dónde | `storage/logs/app-AAAA-MM-DD.log`. Sin acceso web. |
| Formato | Una línea JSON por evento. Monolog `JsonFormatter`. |
| Días | `LOG_DIAS`, 14 por defecto. Monolog borra los viejos. |
| Nivel | `LOG_NIVEL`: `debug` en local, `info` en producción. |
| Quién lee | `admin` del servidor. La app no lo muestra. |
| Código | `core/Log.php`. |

Cada línea lleva en `extra`: `uid` de la petición, `url`, `ip`, `http_method`, `referrer`, `memory_peak_usage`, `user_id` y `rf`.

| Canal | Qué | Nivel |
| --- | --- | --- |
| `http` | Una línea por petición: `metodo`, `ruta`, `codigo`, `duracion_ms`. | info |
| `app` | `sys_crash` con stack trace. Warnings de PHP. | critical, warning |
| `seguridad` | Eventos de la tabla 2. | info a critical |

El `uid` también va en la cabecera `X-Request-Id` y en la página 500. Así el código que da el usuario lleva a su línea `sys_crash`. ASVS 16.5.1.

## 2. Eventos de seguridad

Nombres de OWASP Logging Vocabulary. Formato `evento:param`.

| Evento | Nivel | Dónde |
| --- | --- | --- |
| `authn_login_success:usuario` | info | `AccesoServicio::entrar` |
| `authn_login_fail:usuario` | warning | `AccesoServicio::entrar` |
| `authn_login_lock:llave` | warning | `AccesoServicio::frenar` |
| `session_logout:id` | info | `AccesoControlador::salir` |
| `session_expired:id,motivo` | info | `index.php`, RNF-02 |
| `authz_fail:id,ruta` | critical | `index.php`, 403 |
| `malicious_csrf:id` | warning | `index.php` |
| `input_validation_fail:(campos),id` | warning | `Respuesta::errores` |
| `upload_complete:id,archivo,tipo` | info | `Subida::guardar` |
| `upload_validation:archivo,motivo` | critical | `Subida::error` |
| `user_created:id,nuevo` | warning | `AccesoServicio::registrar`, `UsuarioServicio::crear` |
| `user_updated:id,otro` | warning | `UsuarioServicio::editar` |
| `user_deleted:id,otro` | warning | `UsuarioServicio::eliminar` |
| `privilege_permissions_changed:id,otro,de,a` | warning | `UsuarioServicio::editar` |

Nunca: clave, cookie de sesión, `csrf` ni valores de campos. ASVS 16.2.5.

Casos, incidentes y formularios: sin evento. Ya están en `logs_cambios`, RF-20.

## 3. Log de pruebas

ISO/IEC/IEEE 29119-3, Test Execution Log. En la base, por caso.

| Campo | Dónde |
| --- | --- |
| Fecha y hora | `casos_prueba.anotado_en`, RF-24 |
| Descripción | `resultado_obtenido` y `observaciones` |
| Impacto | `impacto`. Obligatorio con OK o FAULT. |

Cada ejecución queda en `logs_cambios`, RF-20.

## 4. Fuera

Envío a SIEM, métricas y trazas. ASVS 16.4.3.
