<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 1 de la paleta. Al editar, sin el resultado y con el historial.
 *
 * @var ?array<string, mixed> $caso  null al registrar
 * @var list<array<string, mixed>> $historial
 * @var ?string $huella  solo al editar (#100)
 * @var list<array<string, mixed>> $requerimientos
 * @var array<int, list<array<string, mixed>>> $miembros  por proyecto, para los dos selects de aprobacion
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
// Solicitado y aprobado: los miembros del proyecto, en el mismo orden que los requerimientos.
// Un option por campo, porque cada select marca su propia selección.
$proyectosConMiembros = [];
foreach ($porProyecto as $proyecto => $reqs) {
    $lista = $miembros[(int) $reqs[0]['proyecto_id']] ?? [];
    if ($lista !== []) {
        $proyectosConMiembros[$proyecto] = $lista;
    }
}
// Quien ya no es miembro sigue como opción mientras el caso lo tenga: guardar no lo borra.
$nombreActual = ['solicitado_por' => $caso['solicitante'] ?? null, 'aprobado_por' => $caso['aprobador'] ?? null];
$opcionesPersona = function (string $campo) use ($proyectosConMiembros, $datos, $elegido, $caso, $nombreActual): string {
    $html = '';
    $actual = (int) ($caso[$campo] ?? 0);
    $ids = [];
    foreach ($proyectosConMiembros as $lista) {
        $ids = [...$ids, ...array_map('intval', array_column($lista, 'id'))];
    }
    if ($actual !== 0 && !in_array($actual, $ids, true)) {
        $html .= '<option value="' . $actual . '"' . $elegido($campo, $actual) . '>' . Html::e($nombreActual[$campo] . ' (ya no es miembro)') . '</option>';
    }
    foreach ($proyectosConMiembros as $proyecto => $lista) {
        $html .= count($proyectosConMiembros) > 1 ? '<optgroup label="' . Html::e($proyecto) . '">' : '';
        foreach ($lista as $m) {
            $html .= '<option value="' . (int) $m['id'] . '"' . $elegido($campo, (int) $m['id']) . '>' . Html::e($m['nombre']) . '</option>';
        }
        $html .= count($proyectosConMiembros) > 1 ? '</optgroup>' : '';
    }

    return $html;
};
?>
<header class="encabezado">
  <div>
    <?php if ($caso === null): ?>
      <h1>Registrar caso de prueba</h1>
    <?php else: ?>
      <p class="antetitulo fila"><span class="codigo"><?= Html::e($caso['codigo']) ?></span> <?= Catalogo::insignia('estado_caso', $caso['estado']) ?></p>
      <h1>Editar caso de prueba</h1>
    <?php endif; ?>
    <p>Formulario 1</p>
  </div>
</header>
<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['errores' => $errores]) ?>
  <form class="tarjeta pila" method="post" action="<?= $caso === null ? '/casos/registrar' : '/casos/editar' ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
    <input type="hidden" name="envio" value="<?= Csrf::envio() ?>">
    <?php if (isset($huella)): ?>
      <input type="hidden" name="huella" value="<?= Html::e($huella) ?>">
    <?php endif; ?>
    <?php if ($caso !== null): ?><input type="hidden" name="id" value="<?= (int) $caso['id'] ?>"><?php endif; ?>
    <div class="campos">
      <?= $campo('requerimiento_id', 'Requerimiento', ['tipo' => 'select', 'opciones' => $opcionesReq, 'ayuda' => count($porProyecto) > 1 ? 'Agrupados por proyecto.' : '']) ?>
      <?= $campo('tipo_prueba', 'Tipo de prueba', ['tipo' => 'select', 'opciones' => Catalogo::opciones('tipo_prueba', $datos['tipo_prueba'] ?? null)]) ?>
      <div class="campo">
        <label for="f-codigo">Código</label>
        <?php if ($caso === null): ?>
          <input class="control codigo" id="f-codigo" placeholder="SIS-001" disabled aria-describedby="f-codigo-ayuda">
          <p class="campo-ayuda" id="f-codigo-ayuda">Se genera al guardar con la sigla del tipo: SIS-001.</p>
        <?php else: ?>
          <input class="control codigo" id="f-codigo" value="<?= Html::e($caso['codigo']) ?>" disabled aria-describedby="f-codigo-ayuda">
          <p class="campo-ayuda" id="f-codigo-ayuda">No cambia aunque cambie el tipo.</p>
        <?php endif; ?>
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
      <?= $campo('solicitado_por', 'Solicitado por', ['tipo' => 'select', 'requerido' => false, 'opciones' => $opcionesPersona('solicitado_por'), 'ayuda' => 'Quién pidió el caso. Solo informativo.']) ?>
      <?= $campo('aprobado_por', 'Aprobado por', ['tipo' => 'select', 'requerido' => false, 'opciones' => $opcionesPersona('aprobado_por'), 'ayuda' => 'Quién lo aprueba. Solo informativo: no bloquea anotar OK ni FAULT.']) ?>
    </div>

    <?= $campo('objetivo', 'Objetivo', ['tipo' => 'textarea']) ?>
    <div class="campos">
      <?= $campo('precondiciones', 'Precondiciones', ['tipo' => 'textarea', 'requerido' => false, 'ayuda' => 'Estado inicial requerido.']) ?>
      <?= $campo('entrada', 'Datos de entrada', ['tipo' => 'textarea', 'ayuda' => 'Valores con los que se prueba.']) ?>
      <?= $campo('pasos', 'Pasos de ejecución', ['tipo' => 'textarea', 'ayuda' => 'Un paso por línea.']) ?>
      <?= $campo('resultado_esperado', 'Resultado esperado', ['tipo' => 'textarea']) ?>
    </div>

    <?php if ($caso === null): ?>
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
    <?php endif; ?>

    <p class="campo-ayuda"><span class="requerido" aria-hidden="true">*</span> Campo obligatorio</p>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $caso === null ? '/casos/listar' : '/casos/resultado?id=' . (int) $caso['id'] ?>">Cancelar</a>
      <button class="btn btn-primario" type="submit"><?= $caso === null ? 'Guardar caso' : 'Guardar cambios' ?></button>
    </div>
  </form>

  <?php if ($caso !== null): ?>
    <section class="pila" aria-labelledby="h-historial">
      <h2 class="titulo-seccion" id="h-historial">Historial</h2>
      <?php if (!$historial): ?>
        <p class="campo-ayuda">Sin cambios todavía.</p>
      <?php else: ?>
        <div class="tabla-contenedor">
          <table class="tabla tabla-tarjetas">
            <caption class="solo-lector">Cambios de <?= Html::e($caso['codigo']) ?></caption>
            <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Campo</th><th scope="col">Antes</th><th scope="col">Después</th></tr></thead>
            <tbody>
              <?php foreach ($historial as $h): ?>
                <tr>
                  <td data-columna="Fecha"><?= Fecha::legible(new DateTime($h['fecha'])) ?> <?= (new DateTime($h['fecha']))->format('H:i') ?></td>
                  <td data-columna="Usuario"><?= Html::e($h['usuario']) ?></td>
                  <td data-columna="Campo"><?= Html::e($h['etiqueta']) ?></td>
                  <td data-columna="Antes" class="celda-larga"><del class="cambio"><?= Html::e($h['antes']) ?></del></td>
                  <td data-columna="Después" class="celda-larga"><ins class="cambio"><?= Html::e($h['despues']) ?></ins></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
