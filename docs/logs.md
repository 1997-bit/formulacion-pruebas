# Logs

RNF-11. Inventario, ASVS 16.1.1. Código en `core/Log.php`.

## App

`storage/logs/app-AAAA-MM-DD.log`, una línea JSON por evento. `LOG_DIAS` 14. `LOG_NIVEL` `debug` en local, `info` en producción.

`extra` en cada línea: `uid`, `url`, `ip`, `http_method`, `referrer`, `memory_peak_usage`, `user_id`, `rf`. El `uid` va también en `X-Request-Id` y en la página 500.

| Canal | Qué | Nivel |
| --- | --- | --- |
| `http` | Una línea por petición: `metodo`, `ruta`, `codigo`, `duracion_ms`. | info |
| `app` | `sys_crash` con stack trace, warnings de PHP. | critical, warning |
| `seguridad` | Eventos de abajo. | info a critical |

## Eventos de seguridad

OWASP Logging Vocabulary, `evento:param`.

| Evento | Nivel |
| --- | --- |
| `authn_login_success:usuario` | info |
| `authn_login_fail:usuario` | warning |
| `authn_login_lock:llave` | warning |
| `session_logout:id` | info |
| `session_expired:id,motivo` | info |
| `authz_fail:id,ruta` | critical |
| `malicious_csrf:id` | warning |
| `input_validation_fail:(campos),id` | warning |
| `upload_complete:id,archivo,tipo` | info |
| `upload_validation:archivo,motivo` | critical |
| `user_created:id,nuevo` | warning |
| `user_updated:id,otro` | warning |
| `user_deleted:id,otro` | warning |
| `privilege_permissions_changed:id,otro,de,a` | warning |
| `sensitive_read:id,logs` | info |

Nunca clave, cookie, `csrf` ni valores de campos. Casos, incidentes y formularios van en `logs_cambios`, RF-20.

## Pruebas

Test Execution Log, ISO 29119-3: `anotado_en`, `resultado_obtenido`, `observaciones` e `impacto` en `casos_prueba`. Historial en `logs_cambios`.

## Leer

Solo admin, solo lectura, en `/admin/logs`. Pestañas: Lista, Seguridad, Rendimiento.

Error 500: captura de la página con su código, abrir `/admin/logs?uid=<código>`, captura o `.log` de esa vista, y registrar el incidente con las dos evidencias.

Fuera de alcance: envío a otro sistema y alertas, ASVS 16.4.3.
