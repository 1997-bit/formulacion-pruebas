# Bugs corregidos

No borrar el caso FAULT ni el incidente: son el registro de la prueba (#91).

1. Cerrar el incidente. Va primero: la app no deja OK con un incidente abierto.
2. Ejecutar otra vez el caso: prueba de regresión.
3. Anotar OK con el nuevo resultado, evidencia con el PR y el PR en observaciones. El historial se escribe solo.

En `database/seed.sql`, la tabla temporal `corregidos` lista cada bug con PR, fecha y resultado. Un commit de seed por fase.

| Fase | PR | Bugs |
| --- | --- | --- |
| 1, login | #131 | BUG-016, BUG-024, BUG-025 |
| 3, lecturas | #132 | BUG-003, BUG-023, BUG-026, BUG-038, BUG-049, BUG-050 |
| 2, escrituras | #133 | BUG-001, BUG-007, BUG-018, BUG-019, BUG-031, BUG-033 |
| 4, integridad | #134 | BUG-020, BUG-035, BUG-048 |
| 5, servidor | #135 | BUG-017, BUG-053, BUG-054 |
