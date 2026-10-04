<?php
declare(strict_types=1);

use App\Core\Vista;
use App\Helpers\Catalogo;
use App\Helpers\Fecha;
use App\Helpers\Html;
use App\Helpers\Icono;

/**
 * Formulario 8 (RF-15) de solo lectura: lo que llenó el usuario y los promedios del equipo, calculados en el servidor.
 *
 * @var array<string, mixed> $proyecto
 * @var bool $puedeLlenar  el admin no es miembro: solo ve los promedios
 * @var ?array{evaluado: string, auto: array<int, int>, co: array<int, int>, comentario: array<int, string>, promedio_auto: float, promedio_co: float} $evaluacion
 * @var list<array{id: int, nombre: string, auto: ?float, co: ?float}> $promedios
 * @var ?string $flash
 */

$editar = '/formularios/autoevaluacion/editar?proyecto=' . (int) $proyecto['id'];
$numero = fn (?float $n): string => $n === null ? '—' : number_format($n, 1);
?>
<header class="encabezado">
  <div>
    <p class="antetitulo"><?= Html::e($proyecto['nombre']) ?></p>
    <h1>Autoevaluación y coevaluación</h1>
    <?php if ($evaluacion !== null): ?><p>Coevaluación de <?= Html::e($evaluacion['evaluado']) ?>.</p><?php endif; ?>
    <p class="solo-impresion">Formulario 8 · Autoevaluación y coevaluación</p>
  </div>
  <?php if ($evaluacion !== null): ?>
    <div class="acciones">
      <a class="btn btn-secundario" href="<?= $editar ?>"><?= Icono::svg('pencil') ?> Editar</a>
      <button class="btn btn-secundario" type="button" data-imprimir><?= Icono::svg('printer') ?> Imprimir</button>
    </div>
  <?php endif; ?>
</header>

<div class="pila">
  <?= Vista::capturar('partials/mensajes', ['flash' => $flash]) ?>
  <?php if ($puedeLlenar && $evaluacion === null): ?>
    <div class="vacio">
      <span class="vacio-icono"><?= Icono::svg('inbox') ?></span>
      <h2>Todavía no ha llenado su evaluación</h2>
      <p>Puntúe de 1 a 5 cada aspecto, para usted y para su compañero.</p>
      <a class="btn btn-primario" href="<?= $editar ?>"><?= Icono::svg('plus') ?> Llenar evaluación</a>
    </div>
  <?php elseif ($evaluacion !== null): ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Puntos de 1 a 5 por aspecto</caption>
        <thead><tr><th scope="col">Aspecto</th><th scope="col">Autoevaluación</th><th scope="col">Coevaluación</th><th scope="col">Comentarios</th></tr></thead>
        <tbody>
          <?php foreach (Catalogo::valores('aspecto_evaluacion') as $clave => $a): ?>
            <tr>
              <th scope="row" data-columna="Aspecto"><?= Html::e($a['texto']) ?></th>
              <td data-columna="Autoevaluación"><?= (int) ($evaluacion['auto'][$clave] ?? 0) ?: '—' ?></td>
              <td data-columna="Coevaluación"><?= (int) ($evaluacion['co'][$clave] ?? 0) ?: '—' ?></td>
              <td data-columna="Comentarios" class="multilinea"><?= Html::e($evaluacion['comentario'][$clave] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th scope="row">Promedio</th><td data-columna="Autoevaluación"><?= $numero($evaluacion['promedio_auto']) ?></td><td data-columna="Coevaluación"><?= $numero($evaluacion['promedio_co']) ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>

  <section class="tarjeta pila" aria-labelledby="equipo">
    <h2 class="tarjeta-titulo" id="equipo">Promedios del equipo</h2>
    <div class="tabla-contenedor">
      <table class="tabla tabla-tarjetas">
        <caption class="solo-lector">Promedio de 1 a 5 por persona</caption>
        <thead><tr><th scope="col">Persona</th><th scope="col">Autoevaluación</th><th scope="col">Coevaluación recibida</th></tr></thead>
        <tbody>
          <?php foreach ($promedios as $p): ?>
            <tr>
              <th scope="row" data-columna="Persona"><?= Html::e($p['nombre']) ?></th>
              <td data-columna="Autoevaluación"><?= $numero($p['auto']) ?></td>
              <td data-columna="Coevaluación recibida"><?= $numero($p['co']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="campo-ayuda">Sin dato, la persona aún no se evalúa o nadie la ha evaluado.</p>
  </section>
  <p class="solo-impresion campo-ayuda">Impreso el <?= Fecha::legible(new DateTime()) ?> desde Casos de Prueba.</p>
</div>
