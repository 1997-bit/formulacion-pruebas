<?php
declare(strict_types=1);

use App\Helpers\Catalogo;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 6 (RF-13): un plan por proyecto.
 *
 * @var list<array<string, mixed>> $proyectos  con estado, null sin plan
 */
?>
<header class="encabezado">
  <div>
    <h1>Plan de pruebas</h1>
    <p>Formulario 6. Un plan por cada uno de sus proyectos.</p>
  </div>
</header>
<?php if (!$proyectos): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>No está en ningún proyecto</h2>
    <p>Un administrador lo agrega como miembro.</p>
  </div>
<?php else: ?>
  <div class="tabla-contenedor">
    <table class="tabla tabla-tarjetas">
      <caption class="solo-lector">Plan por proyecto</caption>
      <thead>
        <tr><th scope="col">Proyecto</th><th scope="col">Estado del plan</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($proyectos as $p): ?>
          <tr>
            <th scope="row" data-columna="Proyecto"><?= Html::e($p['nombre']) ?></th>
            <td data-columna="Estado del plan"><?= $p['estado'] === null ? '<span class="insignia insignia-borde">Sin plan</span>' : Catalogo::insignia('estado_plan', $p['estado']) ?></td>
            <td class="celda-acciones">
              <?php if ($p['estado'] === null): ?>
                <a class="btn btn-secundario" href="/formularios/plan_pruebas/editar?proyecto=<?= (int) $p['id'] ?>"
                  aria-label="<?= Html::e('Llenar plan de ' . $p['nombre']) ?>">Llenar plan</a>
              <?php else: ?>
                <a class="btn btn-secundario" href="/formularios/plan_pruebas?proyecto=<?= (int) $p['id'] ?>"
                  aria-label="<?= Html::e('Ver plan de ' . $p['nombre']) ?>">Ver plan</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
