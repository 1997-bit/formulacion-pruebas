# IR

Sistema de Casos de Prueba. Ingeniería de Software Aplicada III.

El sistema no ejecuta pruebas: una persona prueba y anota resultado, estado y evidencia en el caso.

## Alcance

| Grupo | Rutas |
| --- | --- |
| Acceso | `/`, `/registro`, `/dashboard`. |
| Administración | `/admin/usuarios`, `/admin/proyectos`, `/admin/logs`. |
| Requerimientos | `/requerimientos/registrar`, `/requerimientos/listar`. |
| Casos | `/casos/registrar`, `/casos/listar`, `/casos/resultado`, `/casos/editar`, `/casos/eliminar`, `/evidencias/ver`. |
| Formularios | `/formularios/` + `clases_equivalencia` 2, `valor_limite` 3, `tabla_decision` 4, `cobertura_blanca` 5, `plan_pruebas` 6, `rubrica` 7, `autoevaluacion` 8, `portafolio` 9, `incidentes` 10. |
| Reportes | `/reportes/cierre`. |

Fuera: producción, API REST, servicios externos, móvil, pagos, ejecutar pruebas.

## Roles

| Rol | Hace |
| --- | --- |
| `admin` | Cuentas, roles, proyectos, Rúbrica 7. |
| `tester` | Casos, resultados, incidentes, Auto y Coevaluación 8. |

Profesor: Arturo Murillo.

## Requerimientos funcionales

| ID | Requerimiento | Regla |
| --- | --- | --- |
| RF-01 | Iniciar sesión | `password_verify`. |
| RF-02 | Crear cuenta | Registro público como `tester`. Solo `admin` crea `admin`. |
| RF-03 | Roles | Solo `admin` cambia el rol. |
| RF-04 | Registrar caso | Proyecto, requerimiento, tipo, módulo, técnica, objetivo, precondiciones, entrada, pasos, esperado, fechas. Fecha final ≥ inicio. |
| RF-05 | Listar casos | Por código. `tester` ve sus proyectos. |
| RF-06 | Editar caso | `tester` solo los suyos. |
| RF-07 | Eliminar caso | Solo `admin`, con confirmación. No con evidencias o incidentes. |
| RF-08 a RF-17 | Formularios 1 a 10 | 1 es la vista imprimible del caso. 7 solo `admin`. 8 ambos roles. 9 por persona y semana. |
| RF-18 | Recrear ambiente | `schema.sql`, `seed.sql`, `.env.example`, `docs/recrear_ambiente.md`. |
| RF-19 | Incidentes | Defectos de un caso en el formulario 10. |
| RF-20 | Historial | Campo, antes, después, usuario y fecha por cambio. |
| RF-21 | Permisos | El servidor valida el rol según la matriz. |
| RF-22 | Consultar | Filtro por proyecto, requerimiento y estado. |
| RF-23 | Reportes | Avance en el panel. Reporte de cierre aparte. |
| RF-24 | Anotar resultado | Pendiente, OK o FAULT. OK o FAULT pide resultado, observaciones, impacto y una evidencia como mínimo. Guarda fecha, hora y usuario. |

## Requerimientos no funcionales

| ID | Tema | Regla |
| --- | --- | --- |
| RNF-01 | Seguridad | Argon2id. PDO preparado. Credenciales en `.env`. |
| RNF-02 | Acceso | Rol y sesión en cada página. Sesión nueva al entrar. Login fallido no dice si el usuario existe. |
| RNF-03 | Evidencias | png, jpg, txt, log o enlace. 5 MB. Renombrado, fuera de la raíz pública. |
| RNF-04 | Fiabilidad | Borrar pide confirmación. Un caso se puede repetir. |
| RNF-05 | Datos | InnoDB, `utf8mb4`. Nombre en un campo. Sin género obligatorio. |
| RNF-06 | Fechas | ISO en base, `dd/mm/aaaa` en pantalla. |
| RNF-07 | Accesibilidad | WCAG 2.2 AA. |
| RNF-08 | Compatibilidad | PHP 8.2+, MySQL 8 o MariaDB 10.6+. Chrome, Firefox, Edge. |
| RNF-09 | Desempeño | 20 filas por página. |
| RNF-10 | Mantenibilidad | Cada función corresponde a un RF. |
| RNF-11 | Logs | Ver `docs/logs.md`. |

## Reglas de negocio

- Código de caso: sigla del tipo y consecutivo por proyecto, `SIS-001`.
- Formularios 2 a 5 por requerimiento. 6 y 7 por proyecto. 8 y 9 por persona.
- Estados, tipos y roles según `config/catalogos.php`.

| Operación | admin | tester |
| --- | --- | --- |
| Usuarios, proyectos, Rúbrica 7, Logs | Sí | No |
| Eliminar caso | Sí | No |
| Editar caso | Sí | Propios |
| Registrar, ver, anotar, incidentes, reportes | Sí | Sus proyectos |
| Auto y Coevaluación 8 | Sí | Sí |

## Restricciones

PHP y MySQL sin frameworks. Solo Monolog en ejecución. PHPUnit, PHPStan y php-cs-fixer en desarrollo.

## Datos

| Grupo | Tablas |
| --- | --- |
| Núcleo | `usuarios`, `intentos_acceso`, `proyectos`, `proyecto_miembros`, `requerimientos`, `casos_prueba` |
| Seguimiento | `evidencias`, `incidentes`, `logs_cambios` |
| Formularios | `clases_equivalencia`, `valor_limite`, `decision_filas`, `decision_celdas`, `cobertura_blanca`, `plan_pruebas`, `rubrica_evaluaciones`, `autoevaluaciones` |
| Vista | `v_trazabilidad` |
