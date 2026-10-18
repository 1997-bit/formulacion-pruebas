<?php
declare(strict_types=1);

use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 8, RF-15. Una evaluación por persona
 *
 * @var list<array<string, mixed>> $proyectos  con guardadas
 */
?>
<header class="encabezado">
  <div>
    <h1>Autoevaluación y coevaluación</h1>
    <p>Formulario 8. Cada persona se evalúa y evalúa a su compañero en cada proyecto.</p>
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
      <caption class="solo-lector">Evaluaciones por proyecto</caption>
      <thead>
        <tr><th scope="col">Proyecto</th><th scope="col" class="num">Personas que la llenaron</th><th scope="col" class="celda-acciones"><span class="solo-lector">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($proyectos as $p): ?>
          <tr>
            <th scope="row" data-columna="Proyecto"><?= Html::e($p['nombre']) ?></th>
            <td data-columna="Personas que la llenaron" class="num"><?= $p['guardadas'] ? (int) $p['guardadas'] : '<span class="sin-dato"><span aria-hidden="true">—</span><span class="solo-lector">Ninguna</span></span>' ?></td>
            <td class="celda-acciones">
              <a class="btn btn-secundario" href="/formularios/autoevaluacion?proyecto=<?= (int) $p['id'] ?>"
                aria-label="<?= Html::e('Ver evaluación de ' . $p['nombre']) ?>">Ver evaluación</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
