<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\CasoServicio;

/**
 * Formulario 1 de la paleta.
 *
 * @var list<array<string, mixed>> $proyectos
 * @var list<array<string, mixed>> $requerimientos
 * @var array<string, string> $errores
 * @var array<string, mixed> $datos  evidencias: tipos marcados
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

// Atributos de un control escrito a mano: error y ayuda enlazados (RNF-07).
$describir = function (string $id, string $nombre) use ($errores): string {
    return isset($errores[$nombre])
        ? ' aria-invalid="true" aria-describedby="' . $id . '-error ' . $id . '-ayuda"'
        : ' aria-describedby="' . $id . '-ayuda"';
};
$errorDe = fn (string $id, string $nombre): string => isset($errores[$nombre])
    ? '<p class="campo-error" id="' . $id . '-error">' . Icono::svg('circle-x') . Html::e($errores[$nombre]) . '</p>'
    : '';
// Un bloque por tipo marcado: ícono, atributos del control, ayuda y ejemplo.
$evidencias = [
    1 => ['image', 'file', 'accept=".png,.jpg,.jpeg" data-vista-previa="f-captura-previa"', 'PNG o JPG, máximo 5 MB.', 'Pantalla de login con el mensaje de error'],
    2 => ['terminal', 'file', 'accept=".txt,.log"', 'Salida de consola o del servidor: TXT o LOG, máximo 5 MB.', 'Registro del servidor durante el intento'],
    4 => ['link', 'url', 'maxlength="500" placeholder="https://…"', 'Video, carpeta de Drive o ejecución en CI.', 'Video del intento de inicio de sesión'],
];
$marcadas = $datos['evidencias'] ?? [];

$opcionesProy = '';
foreach ($proyectos as $p) {
    $opcionesProy .= '<option value="' . $p['id'] . '"' . $elegido('proyecto_id', $p['id']) . '>' . Html::e($p['nombre']) . '</option>';
}
// Agrupados por proyecto, como la sub-técnica por técnica.
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
    <h1>Registrar caso de prueba</h1>
    <p>Formulario 1</p>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="/casos/registrar" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <div class="campos">
      <?= $campo('proyecto_id', 'Proyecto', ['tipo' => 'select', 'opciones' => $opcionesProy]) ?>
      <?= $campo('requerimiento_id', 'Requerimiento', ['tipo' => 'select', 'opciones' => $opcionesReq]) ?>
      <?= $campo('tipo_prueba', 'Tipo de prueba', ['tipo' => 'select', 'opciones' => Catalogo::opciones('tipo_prueba', $datos['tipo_prueba'] ?? null)]) ?>
      <div class="campo">
        <label for="f-codigo">Código</label>
        <input class="control codigo" id="f-codigo" placeholder="SIS-001" disabled aria-describedby="f-codigo-ayuda">
        <p class="campo-ayuda" id="f-codigo-ayuda">Se genera al guardar con la sigla del tipo: SIS-001.</p>
      </div>
      <?= $campo('modulo', 'Módulo o funcionalidad', ['atributos' => 'maxlength="100"']) ?>
      <?= $campo('plataforma', 'Plataforma', ['tipo' => 'select', 'opciones' => Catalogo::opciones('plataforma', $datos['plataforma'] ?? null)]) ?>
      <?= $campo('entorno', 'Entorno', [
          'requerido' => false,
          'atributos' => 'maxlength="255"',
          'ayuda' => 'Detalle de la plataforma: sistema operativo, navegador y versión probada.',
      ]) ?>
    </div>

    <fieldset class="grupo"<?= $aria('tecnica') ?>>
      <legend>Técnica utilizada <span class="requerido" aria-hidden="true">*</span></legend>
      <div class="grupo grupo-fila">
        <label class="opcion"><input type="radio" name="tecnica" value="1" required<?= $marcado('tecnica', 1) ?>> Caja negra</label>
        <label class="opcion"><input type="radio" name="tecnica" value="2"<?= $marcado('tecnica', 2) ?>> Caja blanca</label>
      </div>
      <?= $error('tecnica') ?>
    </fieldset>
    <div class="campos">
      <?= $campo('subtecnica', 'Sub-técnica', ['tipo' => 'select', 'opciones' => $opcionesSub, 'ayuda' => 'Debe ser de la técnica elegida: 10 de caja negra y 10 de caja blanca.']) ?>
      <?= $campo('fecha_inicio', 'Fecha de inicio', ['tipo' => 'date']) ?>
      <?= $campo('fecha_fin', 'Fecha final', ['tipo' => 'date', 'atributos' => 'data-desde="f-fecha_inicio"', 'ayuda' => 'Igual o después de la fecha de inicio.']) ?>
    </div>

    <?= $campo('objetivo', 'Objetivo', ['tipo' => 'textarea']) ?>
    <div class="campos">
      <?= $campo('precondiciones', 'Precondiciones', ['tipo' => 'textarea', 'requerido' => false, 'ayuda' => 'Estado inicial requerido.']) ?>
      <?= $campo('entrada', 'Datos de entrada', ['tipo' => 'textarea', 'ayuda' => 'Valores con los que se prueba.']) ?>
      <?= $campo('pasos', 'Pasos de ejecución', ['tipo' => 'textarea', 'ayuda' => 'Un paso por línea.']) ?>
      <?= $campo('resultado_esperado', 'Resultado esperado', ['tipo' => 'textarea']) ?>
    </div>

    <hr class="separador">
    <p class="titulo-seccion" style="margin:0">Resultado</p>
    <fieldset class="resultado"<?= $aria('estado') ?>>
      <legend>Estado <span class="requerido" aria-hidden="true">*</span></legend>
      <label class="resultado-opcion resultado-pendiente">
        <input type="radio" name="estado" value="0" required<?= $marcado('estado', 0) ?>>
        <?= Icono::svg('circle-alert') ?> Pendiente
      </label>
      <label class="resultado-opcion resultado-ok">
        <input type="radio" name="estado" value="1"<?= $marcado('estado', 1) ?>>
        <?= Icono::svg('circle-check') ?> OK
      </label>
      <label class="resultado-opcion resultado-fault">
        <input type="radio" name="estado" value="2"<?= $marcado('estado', 2) ?>>
        <?= Icono::svg('circle-x') ?> FAULT
      </label>
      <?= $error('estado') ?>
    </fieldset>
    <div class="campos">
      <?= $campo('resultado_obtenido', 'Resultado obtenido', ['tipo' => 'textarea', 'requerido' => false, 'atributos' => 'placeholder="Qué hizo el sistema"']) ?>
      <?= $campo('observaciones', 'Observaciones', ['tipo' => 'textarea', 'requerido' => false, 'atributos' => 'placeholder="Notas adicionales"']) ?>
    </div>

    <fieldset class="grupo" aria-describedby="<?= isset($errores['evidencias']) ? 'f-evidencias-error ' : '' ?>f-evidencias-ayuda">
      <legend>Evidencias</legend>
      <div class="grupo grupo-fila">
        <?php foreach (Catalogo::valores('tipo_evidencia') as $clave => $tipo): ?>
          <label class="opcion"><input type="checkbox" name="evidencias[]" value="<?= $clave ?>"<?= in_array((string) $clave, $marcadas, true) ? ' checked' : '' ?>> <?= Icono::svg($evidencias[$clave][0]) ?> <?= Html::e($tipo['texto']) ?></label>
        <?php endforeach; ?>
      </div>
      <p class="campo-ayuda" id="f-evidencias-ayuda">Marque las que va a usar. Con OK o FAULT hace falta al menos una.</p>
      <?= $error('evidencias') ?>
    </fieldset>
    <?php foreach ($evidencias as $clave => [$icono, $control, $atributos, $ayuda, $ejemplo]): ?>
      <?php
      $nombre = CasoServicio::EVIDENCIAS[$clave][0];
      $id = 'f-' . $nombre;
      ?>
      <div class="tarjeta pila" data-cuando="evidencias[]=<?= $clave ?>">
        <p class="titulo-seccion fila" style="margin:0;justify-content:flex-start"><?= Icono::svg($icono) ?> <?= Html::e(Catalogo::texto('tipo_evidencia', $clave)) ?></p>
        <div class="campos">
          <div class="campo">
            <label for="<?= $id ?>"><?= $control === 'url' ? 'Dirección' : 'Archivo' ?> <span class="requerido" aria-hidden="true">*</span></label>
            <input class="control" id="<?= $id ?>" name="evidencia_<?= $nombre ?>" type="<?= $control ?>" <?= $atributos ?><?= $control === 'url' ? ' value="' . Html::e($datos['evidencia_enlace'] ?? '') . '"' : '' ?><?= $describir($id, 'evidencia_' . $nombre) ?>>
            <p class="campo-ayuda" id="<?= $id ?>-ayuda"><?= Html::e($ayuda) ?></p>
            <?= $errorDe($id, 'evidencia_' . $nombre) ?>
            <?php if ($clave === 1): ?><img id="f-captura-previa" class="vista-previa" alt="" hidden><?php endif; ?>
          </div>
          <div class="campo">
            <label for="<?= $id ?>-desc">Qué muestra <span class="requerido" aria-hidden="true">*</span></label>
            <input class="control" id="<?= $id ?>-desc" name="descripcion_<?= $nombre ?>" maxlength="255" placeholder="<?= Html::e($ejemplo) ?>" value="<?= Html::e($datos['descripcion_' . $nombre] ?? '') ?>"<?= $describir($id . '-desc', 'descripcion_' . $nombre) ?>>
            <p class="campo-ayuda" id="<?= $id ?>-desc-ayuda">Texto alternativo (RNF-07).</p>
            <?= $errorDe($id . '-desc', 'descripcion_' . $nombre) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/casos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar caso</button>
    </div>
  </form>
</div>
