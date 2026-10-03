<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $requerimientos
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$v = fn (string $campo): string => Html::e($datos[$campo] ?? '');
$elegido = fn (string $campo, int|string $valor): string => (string) $valor === ($datos[$campo] ?? '') ? ' selected' : '';
$marcado = fn (string $campo, int|string $valor): string => (string) $valor === ($datos[$campo] ?? '') ? ' checked' : '';
// aria-invalid y aria-describedby: enlaza la ayuda y, si hay, el error (RNF-07).
$aria = function (string $campo, string $ayuda = '') use ($errores): string {
    $ids = array_filter([isset($errores[$campo]) ? "e-{$campo}" : '', $ayuda]);

    return (isset($errores[$campo]) ? ' aria-invalid="true"' : '')
        . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$error = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="campo-error" id="e-' . $campo . '">' . Icono::svg('circle-x') . Html::e($errores[$campo]) . '</p>'
    : '';
$porProyecto = [];
foreach ($requerimientos as $r) {
    $porProyecto[$r['proyecto']][] = $r;
}
?>
<header class="encabezado">
  <div>
    <h1>Registro de caso de prueba</h1>
  </div>
</header>
<div class="pila">
  <?php if ($errores): ?>
    <div class="alerta alerta-error" role="alert"><?= Icono::svg('circle-alert') ?>
      <p class="alerta-titulo">Revise los campos marcados.</p>
    </div>
  <?php endif; ?>
  <form class="tarjeta pila" method="post" action="/casos/registrar" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <div class="campos">
      <div class="campo">
        <label for="f-req">Proyecto y requerimiento <span class="requerido" aria-hidden="true">*</span></label>
        <div class="select">
          <select class="control" id="f-req" name="requerimiento_id" required<?= $aria('requerimiento_id') ?>>
            <option value="">Elegir…</option>
            <?php foreach ($porProyecto as $proyecto => $reqs): ?>
              <optgroup label="<?= Html::e($proyecto) ?>">
                <?php foreach ($reqs as $r): ?>
                  <option value="<?= $r['id'] ?>"<?= $elegido('requerimiento_id', $r['id']) ?>><?= Html::e($r['codigo'] . ' · ' . $r['descripcion']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <?= $error('requerimiento_id') ?>
      </div>
      <div class="campo">
        <label for="f-tipo">Tipo de prueba <span class="requerido" aria-hidden="true">*</span></label>
        <div class="select">
          <select class="control" id="f-tipo" name="tipo_prueba" required<?= $aria('tipo_prueba') ?>>
            <option value="">Elegir…</option>
            <?= Catalogo::opciones('tipo_prueba', $datos['tipo_prueba'] ?? null) ?>
          </select>
        </div>
        <?= $error('tipo_prueba') ?>
      </div>
      <div class="campo">
        <label for="f-id">ID del caso</label>
        <input class="control codigo" id="f-id" disabled placeholder="CP-001" aria-describedby="f-id-ayuda">
        <p class="campo-ayuda" id="f-id-ayuda">Se genera al guardar con la sigla del tipo: SIS-001.</p>
      </div>
      <div class="campo">
        <label for="f-modulo">Módulo / funcionalidad <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="f-modulo" name="modulo" required maxlength="100" value="<?= $v('modulo') ?>"<?= $aria('modulo', 'f-modulo-ayuda') ?>>
        <p class="campo-ayuda" id="f-modulo-ayuda">Componente del sistema a probar.</p>
        <?= $error('modulo') ?>
      </div>
    </div>

    <fieldset class="grupo"<?= $aria('tecnica') ?>>
      <legend>Técnica utilizada <span class="requerido" aria-hidden="true">*</span></legend>
      <div class="grupo grupo-fila">
        <label class="opcion"><input type="radio" name="tecnica" value="1" required<?= $marcado('tecnica', 1) ?>> Caja negra</label>
        <label class="opcion"><input type="radio" name="tecnica" value="2"<?= $marcado('tecnica', 2) ?>> Caja blanca</label>
      </div>
      <?= $error('tecnica') ?>
    </fieldset>

    <div class="campo">
      <label for="f-subtecnica">Sub-técnica <span class="requerido" aria-hidden="true">*</span></label>
      <div class="select">
        <select class="control" id="f-subtecnica" name="subtecnica" required<?= $aria('subtecnica') ?>>
          <option value="">Elegir…</option>
          <?php foreach (['Caja negra' => [1, 10], 'Caja blanca' => [11, 20]] as $tecnica => [$desde, $hasta]): ?>
            <optgroup label="<?= $tecnica ?>">
              <?php foreach (Catalogo::valores('subtecnica') as $clave => $sub): ?>
                <?php if ($clave >= $desde && $clave <= $hasta): ?>
                  <option value="<?= $clave ?>"<?= $elegido('subtecnica', $clave) ?>><?= Html::e($sub['texto']) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <?= $error('subtecnica') ?>
    </div>

    <div class="campos">
      <div class="campo">
        <label for="f-inicio">Fecha de inicio <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="f-inicio" name="fecha_inicio" type="date" required value="<?= $v('fecha_inicio') ?>"<?= $aria('fecha_inicio') ?>>
        <?= $error('fecha_inicio') ?>
      </div>
      <div class="campo">
        <label for="f-fin">Fecha final <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="f-fin" name="fecha_fin" type="date" required data-desde="f-inicio" value="<?= $v('fecha_fin') ?>"<?= $aria('fecha_fin', 'f-fin-ayuda') ?>>
        <p class="campo-ayuda" id="f-fin-ayuda">Igual o después de la fecha de inicio.</p>
        <?= $error('fecha_fin') ?>
      </div>
    </div>
    <div class="campo">
      <label for="f-entorno">Entorno</label>
      <input class="control" id="f-entorno" name="entorno" maxlength="255" value="<?= $v('entorno') ?>"<?= $aria('entorno', 'f-entorno-ayuda') ?>>
      <p class="campo-ayuda" id="f-entorno-ayuda">Sistema operativo, navegador y versión probada. Ej.: Windows 11, Chrome 129, v1.2.</p>
      <?= $error('entorno') ?>
    </div>

    <div class="campo">
      <label for="f-objetivo">Objetivo <span class="requerido" aria-hidden="true">*</span></label>
      <textarea class="control" id="f-objetivo" name="objetivo" required<?= $aria('objetivo', 'f-objetivo-ayuda') ?>><?= $v('objetivo') ?></textarea>
      <p class="campo-ayuda" id="f-objetivo-ayuda">Qué se pretende verificar.</p>
      <?= $error('objetivo') ?>
    </div>
    <div class="campos">
      <div class="campo">
        <label for="f-pre">Precondiciones</label>
        <textarea class="control" id="f-pre" name="precondiciones" aria-describedby="f-pre-ayuda"><?= $v('precondiciones') ?></textarea>
        <p class="campo-ayuda" id="f-pre-ayuda">Estado inicial requerido.</p>
      </div>
      <div class="campo">
        <label for="f-entrada">Datos de entrada <span class="requerido" aria-hidden="true">*</span></label>
        <textarea class="control" id="f-entrada" name="entrada" required<?= $aria('entrada', 'f-entrada-ayuda') ?>><?= $v('entrada') ?></textarea>
        <p class="campo-ayuda" id="f-entrada-ayuda">Valores de entrada.</p>
        <?= $error('entrada') ?>
      </div>
      <div class="campo">
        <label for="f-pasos">Pasos de ejecución <span class="requerido" aria-hidden="true">*</span></label>
        <textarea class="control" id="f-pasos" name="pasos" required<?= $aria('pasos', 'f-pasos-ayuda') ?>><?= $v('pasos') ?></textarea>
        <p class="campo-ayuda" id="f-pasos-ayuda">Secuencia detallada, un paso por línea.</p>
        <?= $error('pasos') ?>
      </div>
      <div class="campo">
        <label for="f-esperado">Resultado esperado <span class="requerido" aria-hidden="true">*</span></label>
        <textarea class="control" id="f-esperado" name="resultado_esperado" required<?= $aria('resultado_esperado', 'f-esperado-ayuda') ?>><?= $v('resultado_esperado') ?></textarea>
        <p class="campo-ayuda" id="f-esperado-ayuda">Comportamiento esperado del sistema.</p>
        <?= $error('resultado_esperado') ?>
      </div>
    </div>

    <hr class="separador">
    <p class="titulo-seccion" style="margin:0">Resultado</p>
    <div class="campo">
      <label for="f-obtenido">Resultado obtenido</label>
      <textarea class="control" id="f-obtenido" name="resultado_obtenido" aria-describedby="f-obtenido-ayuda"><?= $v('resultado_obtenido') ?></textarea>
      <p class="campo-ayuda" id="f-obtenido-ayuda">Comportamiento real observado.</p>
    </div>
    <fieldset class="resultado"<?= $aria('estado') ?>>
      <legend>Estado <span class="requerido" aria-hidden="true">*</span></legend>
      <label class="resultado-opcion resultado-ok">
        <input type="radio" name="estado" value="1" required<?= $marcado('estado', 1) ?>>
        <?= Icono::svg('circle-check') ?> Éxito
      </label>
      <label class="resultado-opcion resultado-fault">
        <input type="radio" name="estado" value="2"<?= $marcado('estado', 2) ?>>
        <?= Icono::svg('circle-x') ?> Fallo
      </label>
      <?= $error('estado') ?>
    </fieldset>
    <div class="campos">
      <div class="campo">
        <label for="f-obs">Observaciones</label>
        <textarea class="control" id="f-obs" name="observaciones" placeholder="Notas adicionales"><?= $v('observaciones') ?></textarea>
      </div>
      <div class="campo">
        <label for="f-evidencia">Evidencia</label>
        <input class="control" id="f-evidencia" name="evidencia" type="file"
               accept=".png,.jpg,.jpeg,.pdf,.txt,.log"<?= $aria('evidencia', 'f-evidencia-ayuda') ?>>
        <p class="campo-ayuda" id="f-evidencia-ayuda">Captura o log: PNG, JPG, PDF, TXT o LOG, máximo 5 MB. Suba un archivo, un enlace o ambos.</p>
        <?= $error('evidencia') ?>
      </div>
      <div class="campo">
        <label for="f-enlace">O un enlace</label>
        <input class="control" id="f-enlace" name="evidencia_enlace" type="url" placeholder="https://…" value="<?= $v('evidencia_enlace') ?>"<?= $aria('evidencia_enlace') ?>>
        <?= $error('evidencia_enlace') ?>
      </div>
      <div class="campo">
        <label for="f-evidencia-desc">Qué muestra la evidencia <span class="requerido" aria-hidden="true">*</span></label>
        <input class="control" id="f-evidencia-desc" name="evidencia_descripcion" required maxlength="255" value="<?= $v('evidencia_descripcion') ?>"
               placeholder="Pantalla de login con el mensaje de error"<?= $aria('evidencia_descripcion', 'f-evidencia-desc-ayuda') ?>>
        <p class="campo-ayuda" id="f-evidencia-desc-ayuda">Describa en pocas palabras qué se ve en el archivo o el enlace.</p>
        <?= $error('evidencia_descripcion') ?>
      </div>
    </div>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/casos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar caso</button>
    </div>
  </form>
</div>
