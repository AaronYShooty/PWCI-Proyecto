<?php
/** @var array|null $aviso @var string $csrf @var array $resumen @var array $misCursos @var array $niveles @var array $ventas
 *  @var array $detalle @var array $porForma @var float $totalVentas @var array $filtros @var array $categorias @var array $perfil
 *  @var array $contactos @var array|null $conActual @var array $hilo */
$titulo = 'Panel del instructor | Walter';
$activa = 'instructor';
$panelRuta = 'instructor';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-instructor">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="<?= Formato::avatar($perfil['foto'], 'background:linear-gradient(135deg, var(--teal), var(--primary));') ?>"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Instructor</span>
          </div>
        </div>
        <a class="is-active" data-tab="mis-cursos">Mis cursos</a>
        <a data-tab="ventas">Ventas</a>
        <a data-tab="mensajes">Mensajes</a>
        <a data-tab="perfil">Mi perfil</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <div class="dash-main">
        <?php if (!empty($aviso)): ?>
        <div class="form-alert is-visible form-alert--<?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= htmlspecialchars($aviso['mensaje']) ?></div>
        <?php endif; ?>

        <div class="dash-tab-panel is-active" id="dashtab-mis-cursos">
          <div class="panel__head" style="margin-bottom:-6px;">
            <h2 style="font-size:1.3rem;">Mis cursos</h2>
            <a href="index.php?ruta=crear-curso" class="btn btn--primary btn--sm">+ Crear curso</a>
          </div>

          <div class="stat-row">
            <div class="stat-card"><span>Cursos activos</span><strong><?= (int) $resumen['cursos_activos'] ?></strong></div>
            <div class="stat-card"><span>Alumnos inscritos</span><strong><?= (int) $resumen['alumnos'] ?></strong></div>
            <div class="stat-card"><span>Ingresos del mes</span><strong><?= Formato::moneda((float) $resumen['ingresos_mes']) ?></strong></div>
            <div class="stat-card"><span>Calificación promedio</span><strong><?= $resumen['calificacion'] === null ? '—' : number_format((float) $resumen['calificacion'], 1) ?></strong></div>
          </div>

          <div class="panel">
            <div class="panel__head"><h3>Mis cursos publicados</h3></div>
            <?php if (!$misCursos): ?>
              <p class="field-hint">Todavía no has publicado cursos. <a href="index.php?ruta=crear-curso">Crea el primero</a>.</p>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
              <?php foreach ($misCursos as $c): ?>
              <div class="course-row-card">
                <div class="course-row-card__thumb"></div>
                <div class="course-row-card__body">
                  <h4><?= htmlspecialchars($c['titulo']) ?></h4>
                  <span style="font-size:0.8rem; color:var(--ink-soft);">
                    <?= (int) $c['alumnos'] ?> alumnos · <?= (int) $c['total_niveles'] ?> niveles · <?= $c['activo'] ? 'Activo' : 'Dado de baja' ?>
                  </span>
                </div>
                <?php if ($c['activo']): ?>
                  <a href="index.php?ruta=curso&amp;id=<?= (int) $c['curso_id'] ?>" class="btn btn--ghost btn--sm">Ver</a>
                <?php endif; ?>
                <form method="post" action="index.php?ruta=curso-estado" style="margin:0;"
                      <?= $c['activo'] ? 'onsubmit="return confirm(\'El curso dejará de mostrarse en el catálogo, pero se conserva en tus reportes. ¿Dar de baja?\');"' : '' ?>>
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="curso" value="<?= (int) $c['curso_id'] ?>">
                  <input type="hidden" name="accion" value="<?= $c['activo'] ? 'baja' : 'reactivar' ?>">
                  <button type="submit" class="btn <?= $c['activo'] ? 'btn--danger' : 'btn--outline' ?> btn--sm"><?= $c['activo'] ? 'Dar de baja' : 'Reactivar' ?></button>
                </form>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <?php foreach (array_slice($misCursos, 0, 5) as $c): ?>
          <div class="panel">
            <div class="panel__head"><h3>Contenido del curso: <?= htmlspecialchars($c['titulo']) ?></h3></div>
            <div class="chip-list">
              <?php foreach ($niveles[$c['curso_id']] as $n): ?>
                <span class="pill">Nivel <?= (int) $n['orden'] ?> · <?= htmlspecialchars($n['titulo']) ?> · Video ✔</span>
              <?php endforeach; ?>
              <?php if (!$niveles[$c['curso_id']]): ?>
                <span class="pill" style="background:#fff3da; color:#b3760a;">Sin niveles publicados</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="dash-tab-panel" id="dashtab-ventas">
          <div class="panel">
            <div class="panel__head">
              <h3>Resumen de ventas por curso</h3>
              <form class="filter-row" method="get" action="index.php">
                <input type="hidden" name="ruta" value="instructor">
                <input type="hidden" name="tab" value="ventas">
                <select name="categoria" aria-label="Categoría">
                  <option value="">Categoría: todas</option>
                  <?php foreach ($categorias as $cat): ?>
                  <option value="<?= $cat['id_categoria'] ?>"<?= (int) ($filtros['categoria'] ?? 0) === $cat['id_categoria'] ? ' selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="cursos" aria-label="Estado del curso">
                  <option value="">Activos e inactivos</option>
                  <option value="activos"<?= ($filtros['cursos'] ?? '') === 'activos' ? ' selected' : '' ?>>Solo activos</option>
                </select>
                <input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>" style="width:150px;" title="Curso creado desde" aria-label="Curso creado desde">
                <input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>" style="width:150px;" title="Curso creado hasta" aria-label="Curso creado hasta">
                <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                <a href="index.php?ruta=instructor&amp;tab=ventas" class="btn btn--ghost btn--sm">Limpiar</a>
              </form>
            </div>
            <table>
              <thead>
                <tr><th>Curso</th><th>Alumnos</th><th>Nivel promedio</th><th>Ingresos</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <?php if (!$ventas): ?>
                <tr><td colspan="5" class="field-hint">No hay cursos que coincidan con estos filtros.</td></tr>
                <?php endif; ?>
                <?php foreach ($ventas as $v): ?>
                <tr>
                  <td><?= htmlspecialchars($v['titulo']) ?></td>
                  <td><?= (int) $v['alumnos'] ?></td>
                  <td><?= number_format((float) $v['nivel_promedio'], 1) ?> de <?= (int) $v['total_niveles'] ?></td>
                  <td><?= Formato::moneda((float) $v['ingresos']) ?></td>
                  <td><?= $v['activo'] ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--off">Dado de baja</span>' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p class="field-hint" style="margin-top:12px;">
              Total de ingresos: <?= Formato::moneda($totalVentas) ?>
              <?php foreach ($porForma as $forma => $monto): ?> · <?= htmlspecialchars($forma) ?> <?= Formato::moneda((float) $monto) ?><?php endforeach; ?>
            </p>
          </div>

          <?php foreach ($ventas as $v): $alumnos = $detalle[$v['curso_id']] ?? []; ?>
          <div class="panel">
            <details>
              <summary style="cursor:pointer; font-weight:600;">
                Detalle: <?= htmlspecialchars($v['titulo']) ?> · <?= count($alumnos) ?> alumno(s) · <?= Formato::moneda((float) $v['ingresos']) ?>
              </summary>
              <table style="margin-top:12px;">
                <thead><tr><th>Alumno</th><th>Inscripción</th><th>Nivel de avance</th><th>Precio pagado</th><th>Forma de pago</th></tr></thead>
                <tbody>
                  <?php if (!$alumnos): ?><tr><td colspan="5" class="field-hint">Aún no hay alumnos inscritos.</td></tr><?php endif; ?>
                  <?php foreach ($alumnos as $a): ?>
                  <tr>
                    <td><?= htmlspecialchars($a['alumno']) ?></td>
                    <td><?= Formato::fecha($a['fecha_inscripcion']) ?></td>
                    <td><?= (int) $a['vistos'] ?> de <?= (int) $v['total_niveles'] ?></td>
                    <td><?= Formato::moneda((float) $a['precio_pagado']) ?></td>
                    <td><?= htmlspecialchars($a['forma_pago']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <p class="field-hint" style="margin-top:8px;">Total del curso: <?= Formato::moneda((float) $v['ingresos']) ?></p>
            </details>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="dash-tab-panel" id="dashtab-mensajes">
          <?php require dirname(__DIR__) . '/_mensajeria.php'; ?>
        </div>

        <div class="dash-tab-panel" id="dashtab-perfil">
          <?php require dirname(__DIR__) . '/_perfil_tab.php'; ?>
        </div>

      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_abrir_tab.php'; ?>
<?php require dirname(__DIR__) . '/_pie.php'; ?>