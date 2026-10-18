# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/2.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.0] - 2026-10-17

### Added

- Eliminar caso, requerimiento, proyecto o usuario: un diálogo pide escribir el código o el nombre para confirmar. Pegar está permitido.
- Caso con evidencias o incidentes: el botón Eliminar queda deshabilitado y el tooltip dice por qué.
- Imagen Docker con etiquetas OCI: título, descripción y repo.

### Changed

- Oscuro zinc: gris con un tinte frío leve.
- Desde tablet: contenido en un panel con curvas sobre el fondo del menú lateral. Móvil: el menú lateral flota con curvas.
- Radios propios. Ajustes con opciones en fila e íconos en Tema.
- Curvas iguales en tarjetas, alertas y menús flotantes. Insignias en píldora.
- Docker: nombre de proyecto fijo en compose y `up --wait` en el README, que termina cuando la app responde.

### Fixed

- Apache en Docker ya no muestra el aviso AH00558 al arrancar.

### Security

- Docker deja al usuario de la app solo con SELECT, INSERT, UPDATE y DELETE. ASVS 13.2.2.
- La sesión vence tras 30 min sin uso o 8 h en total. ASVS 7.3.1 y 7.3.2.
- El historial no se puede cambiar ni borrar: el usuario de la app solo hace SELECT e INSERT en `logs_cambios`. Al eliminar un caso o requerimiento, su historial queda con una fila `eliminado`. ASVS 16.4.2.

## [1.7.0] - 2026-10-17

### Added

- Formularios con errores: resumen con un enlace por campo y "Error:" en el título de la pestaña.
- Registrar caso sin requerimientos: aviso con el paso que falta y botón para registrar uno.
- Requerimiento guardado: aviso con "Registrar caso", que abre el formulario con ese requerimiento elegido.
- Formulario del caso en tres secciones: Qué se prueba, Cómo y cuándo, Diseño de la prueba.
- Campos de contraseña: ojo para mostrar u ocultar.
- Cantidades en singular o plural: "1 stopper abierto", "2 casos", sin "(s)".
- Requerimientos: editar con motivo obligatorio, casos afectados e historial. Eliminar solo admin y solo sin casos ni formularios de técnicas.
- Commit probado en el resultado del caso. Commit afectado y commit corregido en el incidente, con los commits ya usados como sugerencia.
- Listas de casos, incidentes, requerimientos y logs: encabezado de tabla fijo al bajar.
- Gráficos del cierre: tooltip con el dato al pasar el mouse.
- Historial y evidencias: tiempo relativo ("hace 2 h", "ayer") con la fecha exacta al pasar el mouse.
- Panel: cuadrícula con un cuadro por caso y fecha estimada de fin según el ritmo de 7 días.
- Reporte de cierre: resultados por día con promedio y pico, avance contra el plan e incidentes por día con stoppers.
- Requerimientos: matriz de cobertura por tipo de prueba, con huecos y FAULT marcados.
- Caso e incidente: barra de pasos. Caso: historial como línea de tiempo también en el detalle.
- Casos: filtro Desde y Hasta con atajos Esta semana, Próximos 7 días y Este mes.
- Cronograma: tramo rayado si el resultado llegó tarde o el caso sigue atrasado.
- Impresión en hoja carta: encabezado con formulario y proyecto, pie con fecha, usuario y número de página, datos primero, secciones y tablas cortas sin cortes, firmas al final. Al guardar como PDF, el título del archivo es el del documento.
- Impresión: marca BORRADOR o NO-GO en cada hoja, hoja 1 sin encabezado, márgenes para doble cara, cronograma, matriz y tablas de decisión de más de 8 reglas en horizontal, URL de los enlaces externos, secciones plegadas abiertas, mejor corte de texto, y encabezado con título y proyecto también en las listas.
- Panel: aprobación como cifra principal, casos y resultados de hoy, y "Todo al día" en una línea.
- Ajustes: densidad compacta.
- Esqueleto de carga si una página tarda más de 150 ms.
- Precarga de páginas al pasar el mouse y Atrás instantáneo.
- Cabeceras de seguridad: CSP, Referrer-Policy, Permissions-Policy y COOP.
- Manifest: la app se puede instalar.
- Diálogos con `closedby` y botones `command`. `<select>` con los colores de la app donde hay soporte.
- Tokens de espacio en escala de 4 px, de alto de controles y de movimiento de Material 3: duraciones de 100, 150 y 250 ms, y curvas para entrar y salir.
- Tablet de 600 a 1023 px: sidebar como rail de íconos. El botón abre el menú completo encima.
- Móvil: los diálogos salen como hoja desde abajo, con Cancelar al final.
- Táctil: botones, campos, paginación y menú de 44 px. Al tocar, una capa del 10 % marca el toque.
- Áreas seguras de iPhone: el notch y la barra de inicio no tapan el contenido.

### Changed

- Ajustes → Accesibilidad: "Reducir movimiento" es un interruptor sí/no, sin la opción Sistema. La preferencia del sistema operativo se respeta siempre.
- Menús de proyecto y de usuario con Popover API y anchor positioning: Esc y clic afuera nativos.
- Cifras con container queries: una fila en ancho, 2 × 2 en medio, una por fila en angosto.
- Cambio de página con un fundido de 100 ms del contenido. Sin él con "Reducir movimiento".
- Pestañas como control segmentado en píldora.
- Superficies por capas en claro y oscuro. Oscuro neutro, sin tinte azul.
- Tarjetas con sombra suave. Íconos de cifras con el color del estado.
- Números de tablas alineados a la derecha.
- Espaciado: página con 24 px de margen desde tablet, título de sección junto a su contenido, el mismo gap entre cifras y tarjetas, y filtros con el padding de las tarjetas.
- Opciones Pendiente, OK y FAULT de 40 px de alto.
- Tablet: tablas con scroll de lado, la primera columna fija y una sombra si hay más columnas. Tarjetas solo en móvil.
- El hover solo pinta con mouse. En táctil los globos de ayuda salen solo con teclado.

### Fixed

- Panel: "Todo al día" solo cuando no queda nada pendiente. Incidentes abiertos sin asignar en Por atender.
- Reporte de cierre: la cifra que impide el Go sale en rojo.
- Caso: insignia de atraso, pasos con lo que falta y, con un incidente abierto, el botón principal lleva a él.
- Pruebas: misma zona horaria que la app.
- Reporte de cierre: gráficos en 2 columnas sin hueco; el título oculto de la tabla de datos ya no se ve encima.
- Cifras en 6 columnas exactas: con zoom ya no queda la principal sola.
- Matriz de cobertura: llena el ancho con la descripción completa; sin subrayado en las celdas.
- Panel: las tarjetas llenan el ancho; en pantallas anchas ya no quedan pegadas a la izquierda.
- Diálogo de atajos (?): cierra con clic afuera, como los demás.
- Panel del tester: "Mis casos por vencer" ya no repite los atrasados; en atrasados, los suyos dicen "Tuyo".
- Tablero: cada columna con alto máximo y scroll propio.
- Filtros de casos: atajos de fecha y botones en la misma fila.
- Listas de formularios: "—" en las columnas de números sin dato, en vez de una insignia.
- Requerimientos: solo "No funcional" lleva insignia.
- Registrar caso: campos en filas completas, sin huecos.
- Cerrar sesión desde la paleta de comandos.
- Panel: en las tarjetas de proyecto, los botones van al fondo. Códigos y fechas en la misma línea que su texto.
- Panel en móvil: las tareas muestran el texto completo y el dato debajo.
- Números sin huecos en el texto: "22 %" y fechas.
- Botón de borrar del caso con el mismo alto que los demás botones.
- iOS: el texto ya no crece al girar a horizontal.
- El fondo de los diálogos respeta "Reducir transparencia".
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

[Unreleased]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.8.0...HEAD
[1.8.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.7.0...v1.8.0
[1.7.0]: https://github.com/1997-bit/formulacion-pruebas/compare/v1.6.0...v1.7.0
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
