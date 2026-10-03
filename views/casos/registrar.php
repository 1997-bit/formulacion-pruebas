<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * @var list<array<string, mixed>> $requerimientos
 * @var array<string, string> $errores
 * @var array<string, string> $datos
 */

$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
]);
$elegido = fn (string $campo, int|string $valor): string => (string) $valor === ($datos[$campo] ?? '') ? ' selected' : '';
$marcado = fn (string $campo, int|string $valor): string => (string) $valor === ($datos[$campo] ?? '') ? ' checked' : '';
// Radios: el error va en el fieldset.
$aria = fn (string $campo): string => isset($errores[$campo]) ? ' aria-describedby="f-' . $campo . '-error"' : '';
$error = fn (string $campo): string => isset($errores[$campo])
    ? '<p class="campo-error" id="f-' . $campo . '-error">' . Icono::svg('circle-x') . Html::e($errores[$campo]) . '</p>'
    : '';

$opcionesReq = '';
$porProyecto = [];
foreach ($requerimientos as $r) {
    $porProyecto[$r['proyecto']][] = $r;
}
foreach ($porProyecto as $proyecto => $reqs) {
    $opcionesReq .= '<optgroup label="' . Html::e($proyecto) . '">';
    foreach ($reqs as $r) {
        $opcionesReq .= '<option value="' . $r['id'] . '"' . $elegido('requerimiento_id', $r['id']) . '>' . Html::e($r['codigo'] . ' · ' . $r['descripcion']) . '</option>';
    }
    $opcionesReq .= '</optgroup>';
}
$opcionesSub = '';
foreach (['Caja negra' => [1, 10], 'Caja blanca' => [11, 20]] as $tecnica => [$desde, $hasta]) {
    $opcionesSub .= '<optgroup label="' . $tecnica . '">';
    foreach (Catalogo::valores('subtecnica') as $clave => $sub) {
        if ($clave >= $desde && $clave <= $hasta) {
            $opcionesSub .= '<option value="' . $clave . '"' . $elegido('subtecnica', $clave) . '>' . Html::e($sub['texto']) . '</option>';
        }
    }
    $opcionesSub .= '</optgroup>';
}
?>
<header class="encabezado">
  <div>
    <h1>Registro de caso de prueba</h1>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/casos/registrar" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <div class="campos">
      <?= $campo('requerimiento_id', 'Proyecto y requerimiento', ['tipo' => 'select', 'opciones' => $opcionesReq]) ?>
      <?= $campo('tipo_prueba', 'Tipo de prueba', ['tipo' => 'select', 'opciones' => Catalogo::opciones('tipo_prueba', $datos['tipo_prueba'] ?? null)]) ?>
      <div class="campo">
        <label for="f-id">ID del caso</label>
        <input class="control codigo" id="f-id" disabled placeholder="CP-001" aria-describedby="f-id-ayuda">
        <p class="campo-ayuda" id="f-id-ayuda">Se genera al guardar con la sigla del tipo: SIS-001.</p>
      </div>
      <?= $campo('modulo', 'Módulo / funcionalidad', ['atributos' => 'maxlength="100"', 'ayuda' => 'Componente del sistema a probar.']) ?>
    </div>

    <fieldset class="grupo"<?= $aria('tecnica') ?>>
      <legend>Técnica utilizada <span class="requerido" aria-hidden="true">*</span></legend>
      <div class="grupo grupo-fila">
        <label class="opcion"><input type="radio" name="tecnica" value="1" required<?= $marcado('tecnica', 1) ?>> Caja negra</label>
        <label class="opcion"><input type="radio" name="tecnica" value="2"<?= $marcado('tecnica', 2) ?>> Caja blanca</label>
      </div>
      <?= $error('tecnica') ?>
    </fieldset>

    <?= $campo('subtecnica', 'Sub-técnica', ['tipo' => 'select', 'opciones' => $opcionesSub]) ?>

    <div class="campos">
      <?= $campo('fecha_inicio', 'Fecha de inicio', ['tipo' => 'date']) ?>
      <?= $campo('fecha_fin', 'Fecha final', ['tipo' => 'date', 'atributos' => 'data-desde="f-fecha_inicio"', 'ayuda' => 'Igual o después de la fecha de inicio.']) ?>
    </div>
    <?= $campo('plataforma', 'Plataforma', ['tipo' => 'select', 'opciones' => Catalogo::opciones('plataforma', $datos['plataforma'] ?? null)]) ?>
    <?= $campo('entorno', 'Entorno', [
        'requerido' => false,
        'atributos' => 'maxlength="255"',
        'ayuda' => 'Detalle de la plataforma: sistema operativo, navegador y versión. Ej.: Windows 11, Chrome 129, v1.2.',
    ]) ?>

    <?= $campo('objetivo', 'Objetivo', ['tipo' => 'textarea', 'ayuda' => 'Qué se pretende verificar.']) ?>
    <div class="campos">
      <?= $campo('precondiciones', 'Precondiciones', ['tipo' => 'textarea', 'requerido' => false, 'ayuda' => 'Estado inicial requerido.']) ?>
      <?= $campo('entrada', 'Datos de entrada', ['tipo' => 'textarea', 'ayuda' => 'Valores de entrada.']) ?>
      <?= $campo('pasos', 'Pasos de ejecución', ['tipo' => 'textarea', 'ayuda' => 'Secuencia detallada, un paso por línea.']) ?>
      <?= $campo('resultado_esperado', 'Resultado esperado', ['tipo' => 'textarea', 'ayuda' => 'Comportamiento esperado del sistema.']) ?>
    </div>

    <hr class="separador">
    <p class="titulo-seccion" style="margin:0">Resultado</p>
    <?= $campo('resultado_obtenido', 'Resultado obtenido', ['tipo' => 'textarea', 'requerido' => false, 'ayuda' => 'Comportamiento real observado.']) ?>
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
      <?= $campo('observaciones', 'Observaciones', ['tipo' => 'textarea', 'requerido' => false, 'atributos' => 'placeholder="Notas adicionales"']) ?>
      <?= $campo('evidencia', 'Evidencia', [
          'tipo' => 'file',
          'requerido' => false,
          'atributos' => 'accept=".png,.jpg,.jpeg,.pdf,.txt,.log"',
          'ayuda' => 'Captura o log: PNG, JPG, PDF, TXT o LOG, máximo 5 MB. Suba un archivo, un enlace o ambos.',
      ]) ?>
      <?= $campo('evidencia_enlace', 'O un enlace', ['tipo' => 'url', 'requerido' => false, 'atributos' => 'placeholder="https://…"']) ?>
      <?= $campo('evidencia_descripcion', 'Qué muestra la evidencia', [
          'atributos' => 'maxlength="255" placeholder="Pantalla de login con el mensaje de error"',
          'ayuda' => 'Describa en pocas palabras qué se ve en el archivo o el enlace.',
      ]) ?>
    </div>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/casos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar caso</button>
    </div>
  </form>
</div>
