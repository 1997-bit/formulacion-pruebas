# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/2.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Panel: aprobación como cifra principal, casos y resultados de hoy, y "Todo al día" en una línea.
- Ajustes: densidad compacta.
- Esqueleto de carga si una página tarda más de 150 ms.
- Precarga de páginas al pasar el mouse y Atrás instantáneo.
- Cabeceras de seguridad: CSP, Referrer-Policy, Permissions-Policy y COOP.
- Manifest: la app se puede instalar.
- Diálogos con `closedby` y botones `command`. `<select>` con los colores de la app donde hay soporte.

### Changed

- Superficies por capas en claro y oscuro. Oscuro neutro, sin tinte azul.
- Tarjetas con sombra suave. Íconos de cifras con el color del estado.
- Números de tablas alineados a la derecha.

### Fixed

- Cerrar sesión desde la paleta de comandos.
- Botón secundario hundido en oscuro.

## [1.6.0] - 2026-10-13

### Added

- Soporte de alto contraste, más contraste y menos transparencia del sistema.

### Changed

- Colores en oklch. Insignias con fondo suave estilo Jira.
- Pendiente en gris. El amarillo queda para atención.
- Bordes de campo con contraste 3:1.

## [1.5.0] - 2026-10-13

### Added

- Imagen Docker y `compose.yaml` con la app y MySQL.

### Changed

- README y docs más cortos.

## [1.4.0] - 2026-10-13

### Added

- Logs con Monolog: accesos, errores y eventos de seguridad OWASP.
- Vista de logs para el admin.
- Campo impacto del caso, ISO/IEC/IEEE 29119-3.
- Vistas Tablero y Cronograma de casos.
- Panel con por atender, asignados, atrasados y barra apilada.
- Incidentes: filtros, Asignarme y tablero.
- Paleta de comandos con Ctrl+K.
- Ajustes: pestañas Apariencia y Accesibilidad.

### Fixed

- Vista Lista por defecto y Gantt legible.

## [1.3.0] - 2026-10-12

### Added

- Atajos de teclado: ayuda con `?`, ir a con `G`, líder Space y modos estándar, avanzado y solo Ctrl.
- Página de ajustes.

### Fixed

- J y K mueven el foco.
- La pista de atajos se anuncia al lector de pantalla.

## [1.2.0] - 2026-10-07

### Added

- Favicon.

## [1.1.0] - 2026-10-05

### Added

- Solicitado por y Aprobado por en el panel del caso.

### Changed

- Estados visibles en los datos del caso.
- Reflow a 320px.

## [1.0.1] - 2026-10-04

### Changed

- Rendimiento de login, lecturas, escrituras y servidor.

### Fixed

- 21 bugs del seed. Lista en [bugs corregidos](docs/bugs_corregidos.md).

### Security

- Ningún sitio carga la app en un iframe. BUG-017.
- `.htaccess` en la raíz protege `.env`, `database/` y `storage/`. BUG-054.

## [1.0.0] - 2026-10-04

### Added

- Plan de pruebas, cobertura caja blanca y rúbrica. Con esto están los 10 formularios.

## [0.8.0] - 2026-10-04

### Added

- Clases de equivalencia, valor límite, tabla de decisión y autoevaluación.
- Portafolio de evidencias.

## [0.7.0] - 2026-10-03

### Added

- Editar caso con historial.
- Eliminar caso, solo admin y con confirmación.
- Filtros en las listas.
- Registro de incidentes.
- Registro de caso imprimible.

## [0.6.0] - 2026-10-03

### Added

- Registrar y listar casos.
- Anotar resultado y ver evidencias.
- Requerimientos con paginación.

## [0.5.0] - 2026-10-03

### Added

- Usuarios con roles admin y tester.
- Proyectos y miembros.

## [0.4.0] - 2026-10-03

### Added

- Login.
- Paginación y partials comunes.
- Tablas de formularios y trazabilidad.

## [0.3.0] - 2026-10-02

### Added

- Conexión a la base.
- Respuestas y páginas de error.

## [0.2.0] - 2026-10-02

### Added

- Router, sesión, CSRF y validador.

## [0.1.0] - 2026-10-02

### Added

- Estructura del proyecto.
- Tablas base.

[1.6.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.8.0...v1.0.0
[0.8.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/1997-bit/formulacion-pruebas/releases/tag/v0.1.0
