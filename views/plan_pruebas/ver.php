<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 6 (RF-13) de solo lectura e imprimible.
 *
 * @var array<string, mixed> $proyecto
 * @var ?array<string, mixed> $plan
 * @var ?string $flash
 */

$editar = '/formularios/plan_pruebas/editar?proyecto=' . (int) $proyecto['id'];
$texto = fn (?string $t): string => $t === null || $t === '' ? '—' : Html::e($t);
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1>Plan de pruebas</h1>
    <?php if ($plan !== null): ?><p class="fila">Versión <?= Html::e($plan['version']) ?> <?= Catalogo::insignia('estado_plan', $plan['estado']) ?></p><?php endif; ?>
    <p class="solo-impresion">Formulario 6 · Plan de pruebas del proyecto</p>
  </div>
  <?php if ($plan !== null): ?>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $editar ?>"><?= Icono::svg('pencil') ?> Editar</a>
      <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
    </div>
  <?php endif; ?>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($plan === null): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>El proyecto todavía no tiene plan</h2>
      <p>Defina alcance, objetivos, estrategia y criterios de aceptación.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Llenar plan</a>
    </div>
  <?php else: ?>
    <div class="vista-detalle">
      <section class="tarjeta" aria-labelledby="pl-contenido">
        <header class="tarjeta-encabezado"><h2 class="tarjeta-titulo" id="pl-contenido">Contenido</h2></header>
        <dl class="detalle">
          <dt>Alcance</dt><dd class="multilinea"><?= $texto($plan['alcance']) ?></dd>
          <dt>Objetivos</dt><dd class="multilinea"><?= $texto($plan['objetivos']) ?></dd>
          <dt>Recursos</dt><dd class="multilinea"><?= $texto($plan['recursos']) ?></dd>
          <dt>Cronograma</dt><dd class="multilinea"><?= $texto($plan['cronograma']) ?></dd>
          <dt>Criterios de aceptación</dt><dd class="multilinea"><?= $texto($plan['criterios_aceptacion']) ?></dd>
          <dt>Riesgos</dt><dd class="multilinea"><?= $texto($plan['riesgos']) ?></dd>
        </dl>
      </section>

      <aside class="tarjeta" aria-label="Datos del plan">
        <dl class="detalle detalle-apilado">
          <dt>Estado</dt><dd><?= Catalogo::insignia('estado_plan', $plan['estado']) ?></dd>
          <dt>Versión</dt><dd><?= Html::e($plan['version']) ?></dd>
          <dt>Responsable</dt><dd><?= Html::e($plan['responsable']) ?></dd>
          <dt>Fecha</dt><dd><?= Fecha::legible(new DateTime($plan['fecha'])) ?></dd>
          <dt>Estrategia</dt><dd><?= Html::e(Catalogo::texto('estrategia', $plan['estrategia'])) ?></dd>
          <dt>Actualizado</dt><dd><?= Fecha::legible($actualizado = new DateTime($plan['actualizado_en'])) ?> <?= $actualizado->format('H:i') ?></dd>
        </dl>
      </aside>
    </div>
  <?php endif; ?>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
