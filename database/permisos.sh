# Permisos mínimos del usuario de la app. ASVS 13.2.2, CIS MySQL 5.1
# Docker lo carga después de schema.sql y seed.sql. Docker da ALL: aquí queda solo DML
# logs_cambios sin UPDATE ni DELETE, ASVS 16.4.2
# Tabla nueva en schema.sql: agregarla en tablas
# Sin Docker: MYSQL_DATABASE=casos_prueba MYSQL_USER=app bash database/permisos.sh | mysql

tablas='usuarios intentos_acceso proyectos proyecto_miembros requerimientos casos_prueba evidencias incidentes
clases_equivalencia valor_limite decision_filas decision_celdas cobertura_blanca plan_pruebas rubrica_evaluaciones autoevaluaciones'

permisos() {
    local base="\`$MYSQL_DATABASE\`" usuario="'$MYSQL_USER'@'%'" t
    for t in $tablas; do
        echo "GRANT SELECT, INSERT, UPDATE, DELETE ON $base.$t TO $usuario;"
    done
    echo "GRANT SELECT, INSERT ON $base.logs_cambios TO $usuario;"
    echo "GRANT SELECT ON $base.v_trazabilidad TO $usuario;"
}

# Docker usa source: docker_process_sql entra como root
# Docker da ALL sobre casos\_prueba: el REVOKE usa el mismo nombre
if declare -F docker_process_sql >/dev/null; then
    { echo "REVOKE ALL ON \`${MYSQL_DATABASE//_/\\_}\`.* FROM '$MYSQL_USER'@'%';"; permisos; } | docker_process_sql
else
    permisos
fi
