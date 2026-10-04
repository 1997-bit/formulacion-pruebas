<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * RF-23: cierre de un proyecto, imprimible.
 *
 * @var array<string, mixed> $proyecto  con criterios, go, casos_sin_evidencia y stoppers_abiertos
 */

$p = $proyecto;
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($p['nombre']) ?></p>
    <h1>Reporte de cierre</h1>
    <p class="fila">Decisión <?= $p['go'] ? '<span class="insignia insignia-exito">Go</span>' : '<span class="insignia insignia-peligro">No-Go</span>' ?></p>
  </div>
  <div class="acciones">
    <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
  </div>
</header>

<div class="pila">
  <div class="cifras">
    <?php foreach ([['Casos', 'clipboard-list', 'casos'], ['OK', 'circle-check', 'ok'], ['FAULT', 'circle-x', 'fault'], ['Pendientes', 'circle-alert', 'pendientes']] as [$etiqueta, $icono, $clave]): ?>
      <div class="tarjeta cifra">
        <p class="cifra-etiqueta"><?= $etiqueta ?> <?= Icono::svg($icono) ?></p>
        <p class="cifra-valor"><?= (int) $p[$clave] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <section class="tarjeta" aria-labelledby="ci-criterios">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="ci-criterios">Criterios Go / No-Go</h2>
      <p class="tarjeta-descripcion">Go si el proyecto tiene casos y cumple todos.</p>
    </header>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Criterios Go / No-Go</caption>
        <thead><tr><th scope="col">Criterio</th><th scope="col">Resultado</th><th scope="col">Detalle</th></tr></thead>
        <tbody>
          <?php foreach ($p['criterios'] as $c): ?>
            <tr>
              <th scope="row" data-columna="Criterio"><?= Html::e($c['texto']) ?></th>
              <td data-columna="Resultado"><?= $c['cumple'] ? '<span class="insignia insignia-exito">Cumple</span>' : '<span class="insignia insignia-peligro">No cumple</span>' ?></td>
              <td data-columna="Detalle"><?= Html::e($c['detalle']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="tarjeta" aria-labelledby="ci-evidencia">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="ci-evidencia">Casos sin evidencia</h2>
      <p class="tarjeta-descripcion"><?= count($p['casos_sin_evidencia']) ?> caso(s).</p>
    </header>
    <?php if ($p['casos_sin_evidencia']): ?>
      <div class="tabla-contenedor">
        <table class="tabla tabla-tarjetas">
          <caption class="solo-lector">Casos sin evidencia</caption>
          <thead><tr><th scope="col">Código</th><th scope="col">Objetivo</th><th scope="col">Estado</th></tr></thead>
          <tbody>
            <?php foreach ($p['casos_sin_evidencia'] as $c): ?>
              <tr>
                <td data-columna="Código"><a class="codigo" href="/casos/resultado?id=<?= (int) $c['id'] ?>"><?= Html::e($c['codigo']) ?></a></td>
                <td data-columna="Objetivo" class="celda-larga"><?= Html::e($c['objetivo']) ?></td>
                <td data-columna="Estado"><?= Catalogo::insignia('estado_caso', $c['estado']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="tarjeta" aria-labelledby="ci-stoppers">
    <header class="tarjeta-encabezado">
      <h2 class="tarjeta-titulo" id="ci-stoppers">Stoppers abiertos</h2>
      <p class="tarjeta-descripcion"><?= count($p['stoppers_abiertos']) ?> incidente(s).</p>
    </header>
    <?php if ($p['stoppers_abiertos']): ?>
      <div class="tabla-contenedor">
        <table class="tabla tabla-tarjetas">
          <caption class="solo-lector">Stoppers abiertos</caption>
          <thead><tr><th scope="col">Código</th><th scope="col">Título</th><th scope="col">Caso</th><th scope="col">Severidad</th><th scope="col">Estado</th></tr></thead>
          <tbody>
            <?php foreach ($p['stoppers_abiertos'] as $i): ?>
              <tr>
                <td data-columna="Código"><a class="codigo" href="/formularios/incidentes/ver?id=<?= (int) $i['id'] ?>"><?= Html::e($i['codigo']) ?></a></td>
                <td data-columna="Título" class="celda-larga"><?= Html::e($i['titulo']) ?></td>
                <td data-columna="Caso"><a class="codigo" href="/casos/resultado?id=<?= (int) $i['caso_id'] ?>"><?= Html::e($i['caso']) ?></a></td>
                <td data-columna="Severidad"><?= Catalogo::insignia('severidad', $i['severidad']) ?></td>
                <td data-columna="Estado"><?= Catalogo::insignia('estado_incidente', $i['estado']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
