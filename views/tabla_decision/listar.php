<?php
declare(strict_types=1);

use App\Core\Paginacion;
use App\Core\Vista;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 4 (RF-11): una tabla por requerimiento.
 *
 * @var list<array<string, mixed>> $requerimientos  con filas
 * @var Paginacion $paginacion
 */
?>
<header class="encabezado">
  <div>
    <h1>Tablas de decisión</h1>
    <p>Formulario 4. Una tabla por requerimiento de sus proyectos.</p>
  </div>
</header>
<?php if (!$requerimientos): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>Todavía no hay requerimientos</h2>
    <p>La tabla pertenece a un requerimiento. Registre uno primero.</p>
    <a class="btn btn-primario" href="/requerimientos/registrar"><?= Icono::svg('plus') ?> Registrar requerimiento</a>
  </div>
<?php else: ?>
  <div class="pila">
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Tablas por requerimiento</caption>
        <thead>
          <tr><th scope="col">Requerimiento</th><th scope="col">Descripción</th><th scope="col">Proyecto</th><th scope="col">Filas</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
        </thead>
        <tbody>
          <?php foreach ($requerimientos as $r): ?>
            <tr>
              <td data-columna="Requerimiento"><span class="codigo"><?= Html::e($r['codigo']) ?></span></td>
              <td data-columna="Descripción" class="celda-larga"><?= Html::e($r['descripcion']) ?></td>
              <td data-columna="Proyecto"><?= Html::e($r['proyecto']) ?></td>
              <td data-columna="Filas"><?= $r['filas'] ? (int) $r['filas'] : '<span class="insignia insignia-borde">Sin tabla</span>' ?></td>
              <td class="celda-acciones">
                <?php if ($r['filas']): ?>
                  <a class="btn btn-secundario" href="/formularios/tabla_decision?requerimiento=<?= (int) $r['id'] ?>"
                    aria-label="<?= Html::e('Ver tabla de ' . $r['codigo'] . ', ' . $r['proyecto']) ?>">Ver tabla</a>
                <?php else: ?>
                  <a class="btn btn-secundario" href="/formularios/tabla_decision/editar?requerimiento=<?= (int) $r['id'] ?>"
                    aria-label="<?= Html::e('Crear tabla de ' . $r['codigo'] . ', ' . $r['proyecto']) ?>">Crear tabla</a>
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
