<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * RF-23: decisión Go / No-Go de cada proyecto.
 *
 * @var list<array<string, mixed>> $proyectos
 */
?>
<header class="encabezado">
  <div>
    <h1>Reporte de cierre</h1>
    <p>Decisión Go / No-Go de cada uno de sus proyectos.</p>
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
      <caption class="solo-lector">Decisión por proyecto</caption>
      <thead>
        <tr><th scope="col">Proyecto</th><th scope="col">Casos</th><th scope="col">Pendientes</th><th scope="col">Sin evidencia</th><th scope="col">FAULT sin incidente</th><th scope="col">Stoppers abiertos</th><th scope="col">Decisión</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($proyectos as $p): ?>
          <tr>
            <th scope="row" data-columna="Proyecto"><?= Html::e($p['nombre']) ?></th>
            <td data-columna="Casos"><?= (int) $p['casos'] ?></td>
            <td data-columna="Pendientes"><?= (int) $p['pendientes'] ?></td>
            <td data-columna="Sin evidencia"><?= (int) $p['sin_evidencia'] ?></td>
            <td data-columna="FAULT sin incidente"><?= (int) $p['fault_sin_incidente'] ?></td>
            <td data-columna="Stoppers abiertos"><?= (int) $p['stoppers'] ?></td>
            <td data-columna="Decisión"><?= $p['go'] ? '<span class="insignia insignia-exito">Go</span>' : '<span class="insignia insignia-peligro">No-Go</span>' ?></td>
            <td class="celda-acciones">
              <a class="btn btn-secundario" href="/reportes/cierre?proyecto=<?= (int) $p['id'] ?>"
                aria-label="<?= Html::e('Ver cierre de ' . $p['nombre']) ?>">Ver cierre</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
