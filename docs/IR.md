# IR

**Proyecto:** Sistema de Casos de Prueba

**Versión:** 1.0

**Fecha:** 01/10/2026

**Referencia:** Guía de Laboratorio, Ingeniería de Software Aplicada III

---

## 1. Introducción

Sistema web en PHP y MySQL para registrar casos de prueba, darles seguimiento y consultarlos. El sistema no ejecuta pruebas: una persona prueba y anota en el caso el resultado, el estado y la evidencia. El sistema genera los 10 formularios de testing de la guía.

## 2. Objetivos

- Autenticación con roles `admin` y `general`, y creación de cuentas.
- Registrar casos, su resultado y su evidencia, con fecha, hora y usuario.
- Generar los 10 formularios de testing.
- Consultar por proyecto, requerimiento y estado.
- Recrear el sistema desde cero con la carpeta de Drive.

---

## 3. Alcance

Toda petición entra por `public/index.php`. Cada pantalla es una ruta de `config/rutas.php`, sin `.php`.

| Grupo | Rutas |
| --- | --- |
| Acceso | `/` inicio de sesión. `/registro` crea cuentas `general`. `/dashboard` panel con el avance. |
| Administración | `/admin/usuarios`, `/admin/proyectos`. |
| Requerimientos | `/requerimientos/registrar`, `/requerimientos/listar`. |
| Casos | `/casos/registrar`, `/casos/listar` con filtros, `/casos/editar`, `/casos/eliminar`. En `/casos/editar` se anota el resultado y la evidencia, y se imprime el formulario 1. |
| Formularios | `/formularios/` + `clases_equivalencia` 2, `valor_limite` 3, `tabla_decision` 4, `cobertura_blanca` 5, `plan_pruebas` 6, `rubrica` 7, `autoevaluacion` 8, `portafolio` 9, `incidentes` 10. |
| Reportes | `/reportes/cierre`. |

No incluye: producción, API REST, servicios externos, app móvil, pagos, ejecutar pruebas desde el sistema, UAT fuera del equipo y el profesor.

---

## 4. Roles

| Rol | Hace |
| --- | --- |
| `admin` | Gestiona cuentas, roles, proyectos y formularios. Llena la Rúbrica 7. |
| `general` | Registra casos, anota resultados, registra incidentes y llena la Auto y Coevaluación 8. |

Roles organizacionales de referencia: desarrollador, QA/Tester, QA Lead, ingeniería de requerimientos, ingeniería de sistemas, product owner, usuario y profesor Arturo Murillo, que evalúa la exposición y el trabajo escrito.

---

## 5. Requerimientos funcionales

| ID | Requerimiento | Detalle | Prioridad |
| --- | --- | --- | --- |
| RF-01 | Iniciar sesión | Usuario y contraseña con `password_verify`. | Alta |
| RF-02 | Crear cuenta | Registro público con rol `general`. Solo un `admin` crea cuentas `admin`. | Alta |
| RF-03 | Gestionar roles | Solo el `admin` cambia el rol, al editar un usuario. | Alta |
| RF-04 | Registrar caso | Código, proyecto, requerimiento funcional o no funcional, tipo, módulo, técnica y sub-técnica, objetivo, precondiciones, entrada, pasos, resultado esperado, fecha de inicio y fecha final. La final no es anterior a la de inicio. Tipos: unitaria, integración, sistema, aceptación, mantenimiento, regresión, smoke, performance o seguridad. | Alta |
| RF-05 | Listar casos | Orden por código. `admin` ve todos. `general` ve los de sus proyectos. | Alta |
| RF-06 | Editar caso | `admin` edita cualquiera. `general` edita solo los que creó. | Media |
| RF-07 | Eliminar caso | Solo `admin`, con confirmación. Un caso con evidencias o incidentes no se elimina. | Media |
| RF-08 | Formulario 1 | Registro de Caso de Prueba: vista imprimible del caso. | Alta |
| RF-09 | Formulario 2 | Matriz de Clases de Equivalencia. | Alta |
| RF-10 | Formulario 3 | Análisis de Valor Límite. | Alta |
| RF-11 | Formulario 4 | Tabla de Decisión. | Alta |
| RF-12 | Formulario 5 | Cobertura de Caja Blanca. | Alta |
| RF-13 | Formulario 6 | Plan de Pruebas del Proyecto. | Alta |
| RF-14 | Formulario 7 | Rúbrica de Evaluación. Solo `admin`. | Media |
| RF-15 | Formulario 8 | Autoevaluación y Coevaluación. Ambos roles. | Media |
| RF-16 | Formulario 9 | Portafolio de Evidencias: lista de las evidencias del proyecto. | Media |
| RF-17 | Formulario 10 | Registro de Incidentes. | Alta |
| RF-18 | Recrear ambiente | `schema.sql`, `seed.sql`, `.env.example` y `docs/recrear_ambiente.md`. | Alta |
| RF-19 | Incidentes | Los defectos de un caso se registran en el formulario 10. | Alta |
| RF-20 | Historial | Cada cambio de un caso guarda campo, valor anterior, valor nuevo, usuario y fecha. | Media |
| RF-21 | Permisos | El servidor valida el rol en cada operación según la sección 7.1. | Alta |
| RF-22 | Consultar resultados | La lista de casos filtra por proyecto, requerimiento y estado. | Alta |
| RF-23 | Reportes | El panel muestra el avance. El reporte de cierre va aparte. | Alta |
| RF-24 | Anotar resultado | Estado Pendiente, OK o FAULT; un caso nuevo está Pendiente. Con OK o FAULT lleva resultado obtenido, observaciones y al menos una evidencia: archivo o enlace, con descripción como texto alternativo. Se guardan fecha, hora y usuario. El caso puede volver a Pendiente. | Alta |

---

## 6. Requerimientos no funcionales

| ID | Tema | Regla |
| --- | --- | --- |
| RNF-01 | Seguridad | Contraseñas con `PASSWORD_ARGON2ID`, nunca en texto plano. SQL con sentencias preparadas de PDO. Credenciales solo en `.env`; se entrega `.env.example`. |
| RNF-02 | Acceso | El servidor valida rol y sesión en cada página. La sesión se regenera al entrar. Un inicio fallido no dice si el usuario existe. La sesión no borra un formulario a medias. |
| RNF-03 | Evidencias | Solo png, jpg, pdf, txt o log. Máximo 5 MB. El archivo se renombra y se guarda fuera de la raíz pública. |
| RNF-04 | Fiabilidad | Borrar un caso pide confirmación. Los pasos y datos de un caso permiten repetir la prueba. |
| RNF-05 | Datos | InnoDB, `utf8mb4` y UTF-8. Nombre completo en un solo campo. Sin campo de género obligatorio. |
| RNF-06 | Fechas | ISO en la base. `dd/mm/aaaa` en pantalla. |
| RNF-07 | Accesibilidad | WCAG 2.2 AA. HTML semántico y `lang="es"`. Todo con teclado y foco visible. Contraste 4.5:1. El color no es la única señal. Errores junto al campo con `aria-describedby`. Texto alternativo. Zoom de 200 %. Mensajes cortos. Campos obligatorios marcados. |
| RNF-08 | Compatibilidad | PHP 8.2 o superior, MySQL 8 o superior. Chrome, Firefox y Edge. |
| RNF-09 | Desempeño | Listados de 20 filas por página. |
| RNF-10 | Mantenibilidad | Cada función corresponde a un RF. Hay manual por pantalla y guía para recrear el ambiente. |

---

## 7. Reglas de negocio

- Un caso pertenece a un proyecto. Su código es la sigla del tipo y un consecutivo, único en el proyecto: `SIS-001`.
- Formularios 2 a 5 pertenecen a un requerimiento. Formularios 6 y 7, a un proyecto. Formulario 8, a una persona: cada una llena su autoevaluación y la coevaluación de su compañero.
- Estados, tipos y roles se validan contra `config/catalogos.php`.

### 7.1 Matriz rol por operación

| Operación | admin | general |
| --- | --- | --- |
| Usuarios y roles | Sí | No |
| Proyectos y miembros | Sí | No |
| Registrar caso | Sí | Sus proyectos |
| Editar caso | Sí | Solo propios |
| Eliminar caso | Sí | No |
| Ver casos | Todos | Sus proyectos |
| Anotar resultado | Sí | Sus proyectos |
| Registrar incidente | Sí | Sus proyectos |
| Rúbrica 7 | Sí | No |
| Auto y Coevaluación 8 | Sí | Sí |
| Reportes | Sí | Sus proyectos |

---

## 8. Restricciones

- PHP y MySQL, sin frameworks ni librerías en tiempo de ejecución. Composer solo para PHPUnit, PHPStan y php-cs-fixer.
- Entrega: carpeta de Drive con código y base, y documento escrito en Word con el formato del ejemplo de clase.

---

## 9. Datos

| Grupo | Tablas |
| --- | --- |
| Núcleo | `usuarios`, `proyectos`, `proyecto_miembros`, `requerimientos`, `casos_prueba` |
| Seguimiento | `evidencias`, `incidentes`, `logs_cambios` |
| Formularios | `clases_equivalencia`, `valor_limite`, `decision_reglas`, `decision_celdas`, `cobertura_blanca`, `plan_pruebas`, `rubrica_evaluaciones`, `autoevaluaciones` |
| Vista | `v_trazabilidad` |

---
