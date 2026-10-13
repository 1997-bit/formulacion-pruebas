# Medición de rendimiento

Cómo repetir las mediciones con 20 000 casos (#90).

## 1. Datos

```sh
mysql casos_prueba < database/schema.sql
mysql casos_prueba < database/seed.sql
mysql casos_prueba < database/carga.sql
```

`carga.sql` agrega el proyecto "Carga". Siempre crea las mismas filas:

| Tabla | Filas después de carga.sql |
| --- | --- |
| requerimientos | 2 027 |
| casos_prueba | 20 109 |
| incidentes | 20 054 |
| evidencias | 12 055 |

## 2. Servidor

Sin Xdebug: Xdebug multiplica los tiempos.

```sh
PHP_INI_SCAN_DIR= PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8000 -t public
```

## 3. Tiempo por pantalla con `ab`

Entrar como admin y guardar la cookie de sesión:

```sh
U=http://127.0.0.1:8000
tok=$(curl -s -c c.txt $U/ | grep -o 'name="csrf" value="[^"]*"' | cut -d'"' -f4)
curl -s -o /dev/null -b c.txt -c c.txt --data-urlencode "csrf=$tok" -d 'usuario=admin&clave=admin1234' $U/
S=$(awk '$6=="casos_sesion"{print $7}' c.txt)
```

Una petición a la vez (`-c 1`): la sesión de PHP atiende de a una las peticiones de la misma cookie.

```sh
ab -n 100 -c 1 -C casos_sesion=$S "$U/casos/listar"
ab -n 100 -c 1 -C casos_sesion=$S "$U/casos/listar?pagina=1000"
ab -n 100 -c 1 -C casos_sesion=$S "$U/formularios/incidentes"
ab -n 100 -c 1 -C casos_sesion=$S "$U/requerimientos/listar"
ab -n 100 -c 1 -C casos_sesion=$S "$U/formularios/portafolio"
ab -n 100 -c 1 -C casos_sesion=$S "$U/formularios/incidentes/registrar?codigo=SIS-01000"
```

El dato es `Time per request` (mean). La última responde 302: va directo al caso encontrado.

## 4. Planes con `EXPLAIN`

El SQL de cada lista, como lo arma el modelo para el admin. Bueno: `index` o `range` en la subconsulta, sin `Using filesort` sobre la tabla grande.

```sql
-- Casos (CasoModelo::listar), página 1000
EXPLAIN SELECT c.id FROM casos_prueba c WHERE TRUE ORDER BY c.proyecto_id, c.sigla, c.numero LIMIT 20 OFFSET 19980;
-- Incidentes (IncidenteModelo::listar)
EXPLAIN SELECT i.id FROM incidentes i WHERE TRUE ORDER BY i.proyecto_id, i.estado, i.severidad DESC, i.numero LIMIT 20;
-- Requerimientos (RequerimientoModelo::pagina)
EXPLAIN SELECT r.id FROM requerimientos r WHERE TRUE ORDER BY r.proyecto_id, r.no_funcional, r.numero LIMIT 20;
-- Portafolio (EvidenciaModelo::portafolio)
EXPLAIN SELECT e.id FROM evidencias e ORDER BY e.subido_en DESC, e.id DESC LIMIT 20;
-- Tester: cambia WHERE TRUE por la lista de sus proyectos
EXPLAIN SELECT c.id FROM casos_prueba c WHERE c.proyecto_id IN (1, 3) ORDER BY c.proyecto_id, c.sigla, c.numero LIMIT 20;
-- Numerar un caso o un BUG: "Select tables optimized away"
EXPLAIN SELECT COALESCE(MAX(numero), 0) + 1 FROM casos_prueba WHERE proyecto_id = 3 AND sigla = 'SIS';
EXPLAIN SELECT COALESCE(MAX(numero), 0) + 1 FROM incidentes WHERE proyecto_id = 3;
-- Registrar incidente (CasoModelo::porCodigo)
EXPLAIN SELECT c.id FROM casos_prueba c WHERE c.codigo = 'SIS-01000' ORDER BY c.proyecto_id LIMIT 20;
```

## 5. Golden signals desde el log

Cada petición deja una línea `http` con `duracion_ms`, RNF-11. Ver `docs/logs.md`.

```sh
# p95 por ruta
jq -s 'map(select(.channel=="http")) | group_by(.context.ruta)[] | {ruta: .[0].context.ruta, p95: (map(.context.duracion_ms) | sort | .[(length*0.95|floor)])}' storage/logs/app-*.log
# tasa 5xx
jq -s 'map(select(.channel=="http")) | (map(select(.context.codigo>=500)) | length) / length' storage/logs/app-*.log
# login fallido por ip
jq -r 'select(.message|startswith("authn_login_fail")) | .extra.ip' storage/logs/app-*.log | sort | uniq -c
```

SQL lento: `slow_query_log` de MySQL, sin código.

```ini
slow_query_log = 1
long_query_time = 0.2
```
