# Medición

Rendimiento con 20 000 casos (#90).

```sh
mysql safeguard < database/schema.sql
mysql safeguard < database/seed.sql
mysql safeguard < database/carga.sql   # proyecto "Carga", siempre las mismas filas
PHP_INI_SCAN_DIR= PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8000 -t public   # sin Xdebug
```

## Tiempo con `ab`

```sh
U=http://127.0.0.1:8000
tok=$(curl -s -c c.txt $U/ | grep -o 'name="csrf" value="[^"]*"' | cut -d'"' -f4)
curl -s -o /dev/null -b c.txt -c c.txt --data-urlencode "csrf=$tok" -d 'usuario=admin&clave=admin1234' $U/
S=$(awk '$6=="casos_sesion"{print $7}' c.txt)
ab -n 100 -c 1 -C casos_sesion=$S "$U/casos/listar?pagina=1000"
```

`-c 1`: la sesión atiende de a una petición. Dato: `Time per request` (mean).

Rutas: `/casos/listar`, `/casos/listar?pagina=1000`, `/formularios/incidentes`, `/requerimientos/listar`, `/formularios/portafolio`, `/formularios/incidentes/registrar?codigo=SIS-01000` (302).

## `EXPLAIN`

Bueno: `index` o `range`, sin `Using filesort` en la tabla grande.

```sql
EXPLAIN SELECT c.id FROM casos_prueba c WHERE TRUE ORDER BY c.proyecto_id, c.sigla, c.numero LIMIT 20 OFFSET 19980;
EXPLAIN SELECT i.id FROM incidentes i WHERE TRUE ORDER BY i.proyecto_id, i.estado, i.severidad DESC, i.numero LIMIT 20;
EXPLAIN SELECT COALESCE(MAX(numero), 0) + 1 FROM casos_prueba WHERE proyecto_id = 3 AND sigla = 'SIS';  -- optimized away
```

## Desde el log

```sh
# p95 por ruta
jq -s 'map(select(.channel=="http")) | group_by(.context.ruta)[] | {ruta: .[0].context.ruta, p95: (map(.context.duracion_ms) | sort | .[(length*0.95|floor)])}' storage/logs/app-*.log
# tasa 5xx
jq -s 'map(select(.channel=="http")) | (map(select(.context.codigo>=500)) | length) / length' storage/logs/app-*.log
# login fallido por ip
jq -r 'select(.message|startswith("authn_login_fail")) | .extra.ip' storage/logs/app-*.log | sort | uniq -c
```

SQL lento: `slow_query_log = 1`, `long_query_time = 0.2` en MySQL.
