# Registrar un bug corregido

Regla: no borrar el caso FAULT ni el incidente. Son el registro de la prueba (#91).

## Pasos, en este orden

1. Cambiar el incidente a Cerrado: `incidentes.estado = 2`.
2. Ejecutar otra vez el caso. Esta segunda ejecución es la prueba de regresión.
3. Cambiar el caso a OK con el nuevo resultado obtenido.
4. Agregar una evidencia con el enlace al PR de la corrección.
5. Agregar filas en `logs_cambios` con el cambio de FAULT a OK. Así el historial del caso muestra la corrección.
6. Escribir el número del PR en las observaciones.

El paso 1 va antes del paso 3: la app no deja marcar OK un caso con un incidente abierto.

En la app, los pasos 1, 3, 4, 5 y 6 se hacen al cerrar el incidente y anotar el resultado. El historial se escribe solo.

## En el seed

Al final de `database/seed.sql`, la tabla temporal `corregidos` lista cada bug con su PR, la fecha y el nuevo resultado obtenido. Las sentencias que siguen aplican los pasos a todos los bugs de la lista. Un commit de seed por fase agrega sus filas.

| Fase | PR | Bugs |
| --- | --- | --- |
| 1, login | #131 | BUG-016, BUG-024, BUG-025 |
| 3, lecturas | #132 | BUG-003, BUG-023, BUG-026, BUG-038, BUG-049, BUG-050 |
| 2, escrituras | #133 | BUG-001, BUG-007, BUG-018, BUG-019, BUG-031, BUG-033 |
| 4, integridad | #134 | BUG-020, BUG-035, BUG-048 |
| 5, servidor | #135 | BUG-017, BUG-053, BUG-054 |
