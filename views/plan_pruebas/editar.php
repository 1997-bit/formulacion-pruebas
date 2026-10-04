<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 6 (RF-13) para llenar o editar, como en la paleta.
 *
 * @var array<string, mixed> $proyecto
 * @var bool $nuevo
 * @var list<array<string, mixed>> $miembros
 * @var array<string, string> $datos
 * @var array<string, string> $errores
 */

if ($nuevo && !$errores) {
    $datos += ['version' => '1.0', 'fecha' => date('Y-m-d'), 'estado' => '0'];
}
$campo = fn (string $nombre, string $etiqueta, array $extra = []): string => Vista::capturar('partials/campo', $extra + [
    'nombre' => $nombre,
    'etiqueta' => $etiqueta,
    'valor' => $datos[$nombre] ?? '',
    'error' => $errores[$nombre] ?? '',
    'requerido' => true,
]);
$texto = fn (string $nombre, string $etiqueta, string $ayuda, bool $requerido = true): string => $campo($nombre, $etiqueta, [
    'tipo' => 'textarea', 'ayuda' => $ayuda, 'requerido' => $requerido, 'atributos' => 'maxlength="5000"',
]);
$responsables = '';
foreach ($miembros as $m) {
    $responsables .= '<option value="' . (int) $m['id'] . '"' . ((string) $m['id'] === ($datos['responsable_id'] ?? '') ? ' selected' : '') . '>' . Html::e($m['nombre']) . '</option>';
}
$errorEstrategia = $errores['estrategia'] ?? '';
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1><?= $nuevo ? 'Llenar' : 'Editar' ?> plan de pruebas</h1>
    <p>Formulario 6 · uno por proyecto</p>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/formularios/plan_pruebas/editar">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="proyecto_id" value="<?= (int) $proyecto['id'] ?>">
    <div class="campos">
      <?= $campo('version', 'Versión', ['atributos' => 'maxlength="20"']) ?>
      <?= $campo('responsable_id', 'Responsable', ['tipo' => 'select', 'opciones' => $responsables, 'ayuda' => 'Miembros del proyecto.']) ?>
      <?= $campo('fecha', 'Fecha', ['tipo' => 'date']) ?>
      <?= $campo('estado', 'Estado', ['tipo' => 'select', 'opciones' => Catalogo::opciones('estado_plan', $datos['estado'] ?? null), 'ayuda' => 'No se cierra con incidentes stopper abiertos.']) ?>
    </div>
    <?= $texto('alcance', 'Alcance', 'Qué se va a probar y qué no.') ?>
    <?= $texto('objetivos', 'Objetivos', 'Metas de las pruebas.') ?>
    <fieldset class="grupo"<?= $errorEstrategia !== '' ? ' aria-describedby="f-estrategia-error"' : '' ?>>
      <legend>Estrategia <span class="requerido" aria-hidden="true">*</span></legend>
      <div class="grupo grupo-fila">
        <?php foreach (Catalogo::valores('estrategia') as $clave => $e): ?>
          <label class="opcion"><input type="radio" name="estrategia" value="<?= $clave ?>"<?= $clave === array_key_first(Catalogo::valores('estrategia')) ? ' required' : '' ?><?= $errorEstrategia !== '' ? ' aria-invalid="true"' : '' ?><?= (string) $clave === ($datos['estrategia'] ?? '') ? ' checked' : '' ?>> <?= Html::e($e['texto']) ?></label>
        <?php endforeach; ?>
      </div>
      <?php if ($errorEstrategia !== ''): ?>
        <p class="campo-error" id="f-estrategia-error"><?= Icono::svg('circle-x') ?><?= Html::e($errorEstrategia) ?></p>
      <?php endif; ?>
    </fieldset>
    <div class="campos">
      <?= $texto('recursos', 'Recursos', 'Herramientas, personal y tiempo.', false) ?>
      <?= $texto('cronograma', 'Cronograma', 'Fases y fechas.', false) ?>
      <?= $texto('criterios_aceptacion', 'Criterios de aceptación', 'Cuándo se consideran exitosas las pruebas.') ?>
      <?= $texto('riesgos', 'Riesgos', 'Cada riesgo con su mitigación.', false) ?>
    </div>
    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $nuevo ? '/formularios/plan_pruebas' : '/formularios/plan_pruebas?proyecto=' . (int) $proyecto['id'] ?>">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar plan</button>
    </div>
  </form>
</div>
