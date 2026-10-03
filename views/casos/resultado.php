<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Detalle de la paleta: la prueba a la izquierda, con el resultado para anotar; los datos a la derecha.
 *
 * @var array<string, mixed> $caso  con evidencias
 * @var bool $editable
 * @var ?string $flash
 * @var array<string, string> $errores
 * @var array<string, mixed> $datos
 * @var array{rol: int} $usuario
 */

$hora = fn (string $fecha): string => Fecha::legible(new DateTime($fecha)) . ' ' . (new DateTime($fecha))->format('H:i');
$iconos = [1 => 'image', 2 => 'terminal', 4 => 'link'];
?>
<header class="encabezado">
  <div>
    <p class="antetitulo fila"><span class="codigo"><?= Html::e($caso['codigo']) ?></span> <?= Catalogo::insignia('estado_caso', $caso['estado']) ?></p>
    <h1><?= Html::e($caso['modulo']) ?></h1>
  </div>
  <div class="acciones">
    <?php if ($editable): ?>
      <a class="btn btn-secundario" href="/casos/editar?id=<?= (int) $caso['id'] ?>"><?= Icono::svg('pencil') ?> Editar</a>
    <?php endif; ?>
    <?php if ($usuario['rol'] === 1): ?>
      <button class="btn btn-secundario btn-icono" type="button" data-abrir-dialogo="dlg-eliminar" aria-label="Eliminar caso" data-tooltip="Eliminar" data-tooltip-alinear="fin"><?= Icono::svg('trash-2') ?></button>
      <dialog class="dialogo" id="dlg-eliminar" aria-labelledby="dlg-eliminar-titulo">
        <h2 id="dlg-eliminar-titulo">¿Eliminar el caso <?= Html::e($caso['codigo']) ?>?</h2>
        <p>Si tiene evidencias o incidentes no se elimina. Esta acción no se puede deshacer.</p>
        <form method="post" action="/casos/eliminar" class="acciones">
          <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
          <input type="hidden" name="id" value="<?= (int) $caso['id'] ?>">
          <button class="btn btn-secundario" type="submit" formmethod="dialog" autofocus>Cancelar</button>
          <button class="btn btn-peligro" type="submit">Eliminar</button>
        </form>
      </dialog>
    <?php endif; ?>
  </div>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash, 'errores' => $errores]) ?>
  <div class="vista-detalle">
    <div class="pila">
      <section class="tarjeta" aria-labelledby="dt-prueba">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-prueba">Prueba</h2></header>
        <dl class="detalle">
          <dt>Objetivo</dt><dd><?= Html::e($caso['objetivo']) ?></dd>
          <?php if ($caso['precondiciones'] !== null): ?>
            <dt>Precondiciones</dt><dd class="multilinea"><?= Html::e($caso['precondiciones']) ?></dd>
          <?php endif; ?>
          <dt>Datos de entrada</dt><dd class="multilinea"><?= Html::e($caso['entrada']) ?></dd>
          <dt>Pasos</dt><dd class="multilinea"><?= Html::e($caso['pasos']) ?></dd>
          <dt>Resultado esperado</dt><dd class="multilinea"><?= Html::e($caso['resultado_esperado']) ?></dd>
        </dl>
      </section>

      <section class="tarjeta pila" aria-labelledby="dt-resultado">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="dt-resultado">Resultado</h2></header>
        <?php if ($caso['evidencias']): ?>
          <div>
            <h3 class="titulo-seccion" style="margin:0 0 8px;font-size:var(--letra-sm)">Evidencia</h3>
            <ul class="archivos">
              <?php foreach ($caso['evidencias'] as $e): ?>
                <li class="archivo">
                  <span class="archivo-icono"><?= Icono::svg($iconos[$e['tipo']] ?? 'file-text') ?></span>
                  <p class="archivo-nombre"><?= Html::e($e['descripcion']) ?></p>
                  <p class="archivo-detalle">
                    <span class="codigo"><?= Html::e($e['nombre_original'] ?? $e['enlace']) ?></span>
                    · <?= Html::e($e['autor']) ?> · <?= $hora($e['subido_en']) ?>
                  </p>
                  <div class="acciones">
                    <a class="btn btn-fantasma btn-icono" href="/evidencias/ver?id=<?= (int) $e['id'] ?>" target="_blank" rel="noopener"
                       aria-label="Abrir <?= Html::e($e['descripcion']) ?>" data-tooltip="Abrir" data-tooltip-alinear="fin"><?= Icono::svg($e['tipo'] === 4 ? 'link' : 'download') ?></a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <form class="pila" method="post" action="/casos/resultado" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
          <input type="hidden" name="id" value="<?= (int) $caso['id'] ?>">
          <?= Vista::capturar('partials/resultado', ['datos' => $datos, 'errores' => $errores, 'previas' => count($caso['evidencias'])]) ?>
          <div class="acciones">
            <button class="btn btn-primario" type="submit">Guardar resultado</button>
          </div>
        </form>
      </section>
    </div>

    <aside class="tarjeta" aria-label="Datos del caso">
      <dl class="detalle detalle-apilado">
        <dt>Estado</dt><dd><?= Catalogo::insignia('estado_caso', $caso['estado']) ?></dd>
        <dt>Proyecto</dt><dd><?= Html::e($caso['proyecto']) ?></dd>
        <dt>Requerimiento</dt><dd><?= Html::e($caso['requerimiento'] . ' · ' . $caso['requerimiento_descripcion']) ?></dd>
        <dt>Tipo de prueba</dt><dd><?= Html::e(Catalogo::texto('tipo_prueba', $caso['tipo_prueba'])) ?></dd>
        <dt>Plataforma</dt><dd><?= Html::e(Catalogo::texto('plataforma', $caso['plataforma']) . ($caso['entorno'] !== null ? ' · ' . $caso['entorno'] : '')) ?></dd>
        <dt>Técnica</dt><dd><?= $caso['subtecnica'] > 10 ? 'Caja blanca' : 'Caja negra' ?> · <?= Html::e(Catalogo::texto('subtecnica', $caso['subtecnica'])) ?></dd>
        <dt>Fechas</dt><dd><?= Fecha::legible(new DateTime($caso['fecha_inicio'])) ?> al <?= Fecha::legible(new DateTime($caso['fecha_fin'])) ?></dd>
        <?php if ($caso['anotador'] !== null): ?>
          <dt>Resultado anotado por</dt><dd><?= Html::e($caso['anotador']) ?> · <?= $hora($caso['anotado_en']) ?></dd>
        <?php endif; ?>
        <dt>Creado por</dt><dd><?= Html::e($caso['autor']) ?> · <?= $hora($caso['creado_en']) ?></dd>
      </dl>
    </aside>
  </div>
</div>
