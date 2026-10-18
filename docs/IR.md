# IR

**Proyecto:** Safeguard

**Versión:** 1.0

**Fecha:** 01/10/2026

**Referencia:** Guía de Laboratorio, Ingeniería de Software Aplicada III

---

## 1. Introducción

Web en PHP y MySQL para registrar casos de prueba y darles seguimiento. No ejecuta pruebas: una persona prueba y anota resultado, estado y evidencia. Genera los 10 formularios de la guía.

## 2. Objetivos

- Roles `admin` y `tester`, y creación de cuentas.
- Casos con resultado y evidencia, con fecha, hora y usuario.
- Los 10 formularios de testing.
- Consulta por proyecto, requerimiento y estado.
- Recrear el sistema con la carpeta de Drive.

---

## 3. Alcance

Toda petición entra por `public/index.php`. Rutas en `config/rutas.php`.

| Grupo | Rutas |
| --- | --- |
| Acceso | `/` login. Sin registro público: solo `admin` crea cuentas. `/dashboard` avance. |
| Administración | `/admin/usuarios`, `/admin/proyectos`. |
| Requerimientos | `/requerimientos/registrar`, `/requerimientos/listar`, `/requerimientos/editar`, `/requerimientos/eliminar`. |
| Casos | `/casos/registrar`, `/casos/listar`, `/casos/resultado`, `/casos/editar`, `/casos/eliminar`, `/evidencias/ver`. `/casos/resultado` anota e imprime el formulario 1. |
| Formularios | `/formularios/` + `clases_equivalencia` 2, `valor_limite` 3, `tabla_decision` 4, `cobertura_blanca` 5, `plan_pruebas` 6, `rubrica` 7, `autoevaluacion` 8, `portafolio` 9, `incidentes` 10. |
| Reportes | `/reportes/cierre`. |

Fuera: producción, API REST, servicios externos, móvil, pagos, ejecutar pruebas, UAT externo.

---

## 4. Roles

| Rol | Hace |
| --- | --- |
| `admin` | Cuentas, roles, proyectos, formularios. Rúbrica 7. |
| `tester` | Casos, resultados, incidentes. Auto y Coevaluación 8. |

Referencia: desarrollador, QA, QA Lead, requerimientos, sistemas, product owner, usuario y el profesor Arturo Murillo.

---

## 5. Requerimientos funcionales

| ID | Requerimiento | Detalle | Prioridad |
| --- | --- | --- | --- |
| RF-01 | Iniciar sesión | `password_verify`. | Alta |
| RF-02 | Crear cuenta | Solo `admin` crea cuentas, en Usuarios. Sin registro público. | Alta |
| RF-03 | Gestionar roles | Solo `admin` cambia el rol. | Alta |
| RF-04 | Registrar caso | Proyecto, requerimiento, tipo, módulo, técnica, objetivo, precondiciones, entrada, pasos, esperado, fechas. Final ≥ inicio. | Alta |
| RF-05 | Listar casos | Por código. `tester` ve sus proyectos. | Alta |
| RF-06 | Editar caso | `tester` solo los suyos. | Media |
| RF-07 | Eliminar caso | Solo `admin`, con confirmación. No con evidencias o incidentes. | Media |
| RF-08 | Formulario 1 | Registro de Caso de Prueba, imprimible. | Alta |
| RF-09 | Formulario 2 | Clases de Equivalencia. | Alta |
| RF-10 | Formulario 3 | Valor Límite. | Alta |
| RF-11 | Formulario 4 | Tabla de Decisión. | Alta |
| RF-12 | Formulario 5 | Cobertura de Caja Blanca. | Alta |
| RF-13 | Formulario 6 | Plan de Pruebas. | Alta |
| RF-14 | Formulario 7 | Rúbrica. Solo `admin`. | Media |
| RF-15 | Formulario 8 | Auto y Coevaluación. Ambos roles. | Media |
| RF-16 | Formulario 9 | Portafolio por persona y semana. | Media |
| RF-17 | Formulario 10 | Registro de Incidentes. | Alta |
| RF-18 | Recrear ambiente | `schema.sql`, `seed.sql`, `.env.example`, `docs/recrear_ambiente.md`. | Alta |
| RF-19 | Incidentes | Defectos de un caso en el formulario 10. Commit afectado obligatorio, commit corregido al cerrar. | Alta |
| RF-20 | Historial | Campo, antes, después, usuario y fecha. No se cambia ni se borra, ASVS 16.4.2. | Media |
| RF-21 | Permisos | Rol validado en el servidor, según 7.1. | Alta |
| RF-22 | Consultar | Filtro por proyecto, requerimiento y estado. | Alta |
| RF-23 | Reportes | Avance en el panel. Cierre aparte. | Alta |
| RF-24 | Anotar resultado | Pendiente, OK o FAULT. OK o FAULT pide resultado, observaciones, impacto, commit probado y evidencia. Guarda fecha, hora y usuario. | Alta |

---

## 6. Requerimientos no funcionales

| ID | Tema | Regla |
| --- | --- | --- |
| RNF-01 | Seguridad | Argon2id. PDO preparado. Credenciales en `.env`. |
| RNF-02 | Acceso | Rol y sesión en cada página. Sesión nueva al entrar. Login fallido no dice si el usuario existe. La sesión vence tras 30 min sin uso o 8 h en total, ASVS 7.3.1 y 7.3.2: riesgo bajo y una jornada de trabajo. |
| RNF-03 | Evidencias | png, jpg, txt, log o enlace. 5 MB. Renombrado, fuera de la raíz pública. |
| RNF-04 | Fiabilidad | Borrar pide confirmación. Un caso se puede repetir. |
| RNF-05 | Datos | InnoDB, `utf8mb4`. Nombre en un campo. Sin género obligatorio. |
| RNF-06 | Fechas | ISO en base, `dd/mm/aaaa` en pantalla. |
| RNF-07 | Accesibilidad | WCAG 2.2 AA, `lang="es"`, teclado, contraste 4.5:1, errores con `aria-describedby`. |
| RNF-08 | Compatibilidad | PHP 8.2+, MySQL 8 o MariaDB 10.6+. Chrome, Firefox, Edge. |
| RNF-09 | Desempeño | 20 filas por página. |
| RNF-10 | Mantenibilidad | Cada función corresponde a un RF. |
| RNF-11 | Logs | Monolog en `storage/logs`. Ver `docs/logs.md`. |

---

## 7. Reglas de negocio

- Código de caso: sigla del tipo y consecutivo por proyecto, `SIS-001`.
- Formularios 2 a 5 por requerimiento. 6 y 7 por proyecto. 8 y 9 por persona.
- Estados, tipos y roles según `config/catalogos.php`.

### 7.1 Matriz rol por operación

| Operación | admin | tester |
| --- | --- | --- |
| Usuarios y roles | Sí | No |
| Proyectos y miembros | Sí | No |
| Editar requerimiento | Sí | Sus proyectos |
| Eliminar requerimiento | Sí | No |
| Registrar caso | Sí | Sus proyectos |
| Editar caso | Sí | Propios |
| Eliminar caso | Sí | No |
| Ver casos | Todos | Sus proyectos |
| Anotar resultado | Sí | Sus proyectos |
| Registrar incidente | Sí | Sus proyectos |
| Rúbrica 7 | Sí | No |
| Auto y Coevaluación 8 | Sí | Sí |
| Reportes | Sí | Sus proyectos |
| Logs | Sí | No |

---

## 8. Restricciones

- PHP y MySQL sin frameworks. Solo Monolog en ejecución. PHPUnit, PHPStan y php-cs-fixer en desarrollo.
- Entrega: carpeta de Drive con código y base.

---

## 9. Datos

| Grupo | Tablas |
| --- | --- |
| Núcleo | `usuarios`, `intentos_acceso`, `proyectos`, `proyecto_miembros`, `requerimientos`, `casos_prueba` |
| Seguimiento | `evidencias`, `incidentes`, `logs_cambios` |
| Formularios | `clases_equivalencia`, `valor_limite`, `decision_filas`, `decision_celdas`, `cobertura_blanca`, `plan_pruebas`, `rubrica_evaluaciones`, `autoevaluaciones` |
| Vista | `v_trazabilidad` |
