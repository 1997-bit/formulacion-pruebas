<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 1 de la paleta.
 *
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
// Cerrado mientras el caso siga Pendiente y sin evidencias.
$conResultado = ($datos['estado'] ?? '0') !== '0' || !empty($datos['evidencias']) || isset($errores['estado']);

// El proyecto sale del requerimiento: se agrupan por proyecto, sin grupos si hay uno solo.
$porProyecto = [];
foreach ($requerimientos as $r) {
    $porProyecto[$r['proyecto']][] = $r;
}
$opcionesReq = '';
foreach ($porProyecto as $proyecto => $reqs) {
    $opcionesReq .= count($porProyecto) > 1 ? '<optgroup label="' . Html::e($proyecto) . '">' : '';
    foreach ($reqs as $r) {
        $opcionesReq .= '<option value="' . $r['id'] . '"' . $elegido('requerimiento_id', $r['id']) . '>' . Html::e($r['codigo'] . ' · ' . $r['descripcion']) . '</option>';
    }
    $opcionesReq .= count($porProyecto) > 1 ? '</optgroup>' : '';
}
// La técnica sale de la sub-técnica.
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
      <?= $campo('requerimiento_id', 'Requerimiento', ['tipo' => 'select', 'opciones' => $opcionesReq, 'ayuda' => count($porProyecto) > 1 ? 'Agrupados por proyecto.' : '']) ?>
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

    <div class="campos">
      <?= $campo('subtecnica', 'Técnica y sub-técnica', ['tipo' => 'select', 'opciones' => $opcionesSub, 'ayuda' => 'Agrupadas en caja negra y caja blanca.']) ?>
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
    <details class="plegable"<?= $conResultado ? ' open' : '' ?>>
      <summary>
        <span class="titulo-seccion">Resultado</span>
        <span class="campo-ayuda">Si ya lo probó. Si no, el caso queda Pendiente.</span>
        <?= Icono::svg('chevron-right', 'icono flecha') ?>
      </summary>
      <div class="pila">
        <?= Vista::capturar('partials/resultado', ['datos' => $datos, 'errores' => $errores]) ?>
      </div>
    </details>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="/casos/listar">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar caso</button>
    </div>
  </form>
</div>
