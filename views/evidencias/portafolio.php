<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 9 (RF-16). Archivos del Detalle de la paleta, agrupados por proyecto.
 *
 * @var list<array<string, mixed>> $evidencias
 * @var Paginacion $paginacion
 */

$hora = fn (string $fecha): string => Fecha::legible(new DateTime($fecha)) . ' ' . (new DateTime($fecha))->format('H:i');
$iconos = [1 => 'image', 2 => 'terminal', 4 => 'link'];
$proyectos = [];
foreach ($evidencias as $e) {
    $proyectos[$e['proyecto']][] = $e;
}
?>
<header class="encabezado">
  <div>
    <h1>Portafolio de evidencias</h1>
    <p>Las evidencias de los casos de sus proyectos, con su descripción.</p>
  </div>
</header>
<div class="pila">
  <?php if (!$evidencias): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no hay evidencias</h2>
      <p>Se agregan al anotar el resultado de un caso.</p>
      <a class="btn btn-primario" href="/casos/listar"><?= Icono::svg('clipboard-list') ?> Ver casos</a>
    </div>
  <?php else: ?>
    <?php foreach ($proyectos as $proyecto => $lista): ?>
      <section class="tarjeta" aria-labelledby="pf-<?= (int) $lista[0]['id'] ?>">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="pf-<?= (int) $lista[0]['id'] ?>"><?= Html::e($proyecto) ?></h2></header>
        <ul class="archivos">
          <?php foreach ($lista as $e): ?>
            <li class="archivo">
              <span class="archivo-icono"><?= Icono::svg($iconos[$e['tipo']] ?? 'file-text') ?></span>
              <p class="archivo-nombre"><?= Html::e($e['descripcion']) ?></p>
              <p class="archivo-detalle">
                <a class="codigo" href="/casos/resultado?id=<?= (int) $e['caso_id'] ?>"><?= Html::e($e['caso']) ?></a>
                · <span class="codigo"><?= Html::e($e['nombre_original'] ?? $e['enlace']) ?></span>
                · <?= Html::e($e['autor']) ?> · <?= $hora($e['subido_en']) ?>
              </p>
              <div class="acciones">
                <a class="btn btn-fantasma btn-icono" href="/evidencias/ver?id=<?= (int) $e['id'] ?>" target="_blank" rel="noopener"
                   aria-label="Abrir <?= Html::e($e['descripcion']) ?>" data-tooltip="Abrir" data-tooltip-alinear="fin"><?= Icono::svg($e['tipo'] === 4 ? 'link' : 'download') ?></a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  <?php endif; ?>
</div>
