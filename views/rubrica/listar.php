<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;
use App\Services\RubricaServicio;

/**
 * Formulario 7 (RF-14): una rúbrica por proyecto, solo admin.
 *
 * @var list<array<string, mixed>> $proyectos  con total, null sin rúbrica
 */
?>
<header class="encabezado">
  <div>
    <h1>Rúbrica de evaluación</h1>
    <p>Formulario 7. El administrador evalúa cada proyecto en 6 criterios de 1 a 5.</p>
  </div>
</header>
<?php if (!$proyectos): ?>
  <div class="vacio">
    <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
    <h2>No hay proyectos</h2>
    <p>Cree uno en Administración › Proyectos.</p>
  </div>
<?php else: ?>
  <div class="tabla-contenedor">
    <table class="tabla tabla-tarjetas">
      <caption class="solo-lector">Total de la rúbrica por proyecto</caption>
      <thead>
        <tr><th scope="col">Proyecto</th><th scope="col">Total</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($proyectos as $p): ?>
          <tr>
            <th scope="row" data-columna="Proyecto"><?= Html::e($p['nombre']) ?></th>
            <td data-columna="Total"><?= $p['total'] === null ? '<span class="insignia insignia-borde">Sin evaluar</span>' : (int) $p['total'] . ' / ' . RubricaServicio::MAXIMO ?></td>
            <td class="celda-acciones">
              <a class="btn btn-secundario" href="/formularios/rubrica?proyecto=<?= (int) $p['id'] ?>"
                aria-label="<?= Html::e('Ver rúbrica de ' . $p['nombre']) ?>">Ver rúbrica</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
