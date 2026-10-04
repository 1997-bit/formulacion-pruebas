<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 5 (RF-12): una cobertura por requerimiento.
 *
 * @var list<array<string, mixed>> $requerimientos  con filas: métricas medidas
 * @var Paginacion $paginacion
 */
?>
<header class="encabezado">
  <div>
    <h1>Cobertura de caja blanca</h1>
    <p>Formulario 5. Una cobertura por requerimiento de sus proyectos.</p>
  </div>
</header>
<?php if (!$requerimientos): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>Todavía no hay requerimientos</h2>
    <p>La cobertura pertenece a un requerimiento. Registre uno primero.</p>
    <a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a>
  </div>
<?php else: ?>
  <div class="pila">
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Coberturas por requerimiento</caption>
        <thead>
          <tr><th scope="col">Requerimiento</th><th scope="col">Descripción</th><th scope="col">Proyecto</th><th scope="col">Métricas</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
        </thead>
        <tbody>
          <?php foreach ($requerimientos as $r): ?>
            <tr>
              <td data-columna="Requerimiento"><span class="codigo"><?= Html::e($r['codigo']) ?></span></td>
              <td data-columna="Descripción" class="celda-larga"><?= Html::e($r['descripcion']) ?></td>
              <td data-columna="Proyecto"><?= Html::e($r['proyecto']) ?></td>
              <td data-columna="Métricas"><?= $r['filas'] ? (int) $r['filas'] : '<span class="insignia insignia-borde">Sin cobertura</span>' ?></td>
              <td class="celda-acciones">
                <?php if ($r['filas']): ?>
                  <a class="btn btn-secundario" href="/formularios/cobertura_blanca?requerimiento=<?= (int) $r['id'] ?>"
                    aria-label="<?= Html::e('Ver cobertura de ' . $r['codigo'] . ', ' . $r['proyecto']) ?>">Ver cobertura</a>
                <?php else: ?>
                  <a class="btn btn-secundario" href="/formularios/cobertura_blanca/editar?requerimiento=<?= (int) $r['id'] ?>"
                    aria-label="<?= Html::e('Crear cobertura de ' . $r['codigo'] . ', ' . $r['proyecto']) ?>">Crear cobertura</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= Vista::capturar('partials/paginacion', ['paginacion' => $paginacion]) ?>
  </div>
<?php endif; ?>
