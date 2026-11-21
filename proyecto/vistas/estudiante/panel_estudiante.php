<?php
/** @var array|null $aviso @var string $csrf @var array $resumen @var array $continuar @var array $kardex
 *  @var array $filtros @var array $categorias @var array $perfil @var array $contactos @var array|null $conActual @var array $hilo */
$titulo = 'Panel del estudiante | Walter';
$activa = 'estudiante';
$panelRuta = 'estudiante';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-estudiante">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="<?= Formato::avatar($perfil['foto']) ?>"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Estudiante</span>
          </div>
        </div>
        <a class="is-active" data-tab="mis-cursos">Mis cursos</a>
        <a data-tab="kardex">Kardex</a>
        <a data-tab="mensajes">Mensajes</a>
        <a data-tab="perfil">Mi perfil</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <div class="dash-main">
        <?php if (!empty($aviso)): ?>
        <div class="form-alert is-visible form-alert--<?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= htmlspecialchars($aviso['mensaje']) ?></div>
        <?php endif; ?>

        <div class="dash-tab-panel is-active" id="dashtab-mis-cursos">
          <div class="stat-row">
            <div class="stat-card"><span>Cursos inscritos</span><strong><?= $resumen['inscritos'] ?></strong></div>
            <div class="stat-card"><span>Cursos terminados</span><strong><?= $resumen['terminados'] ?></strong></div>
            <div class="stat-card"><span>En progreso</span><strong><?= $resumen['en_progreso'] ?></strong></div>
            <div class="stat-card"><span>Diplomas obtenidos</span><strong><?= $resumen['terminados'] ?></strong></div>
          </div>

          <div class="panel">
            <div class="panel__head"><h3>Continúa donde lo dejaste</h3></div>
            <?php if (!$continuar): ?>
              <p class="field-hint">
                <?= $resumen['inscritos'] === 0
                    ? 'Aún no te has inscrito a ningún curso.'
                    : 'No tienes cursos en progreso.' ?>
                <a href="index.php?ruta=inicio">Explora el catálogo</a>.
              </p>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
              <?php foreach ($continuar as $k): ?>
              <div class="course-row-card">
                <div class="course-row-card__thumb"></div>
                <div class="course-row-card__body">
                  <h4><?= htmlspecialchars($k['titulo']) ?></h4>
                  <span style="font-size:0.8rem; color:var(--ink-soft);">
                    <?php if ((int) $k['total_niveles'] > 0): ?>
                      Nivel <?= min((int) $k['niveles_vistos'] + 1, (int) $k['total_niveles']) ?> de <?= (int) $k['total_niveles'] ?> ·
                    <?php endif; ?>
                    Última actividad: <?= Formato::fecha($k['ultima_fecha_acceso'] ?? $k['fecha_inscripcion']) ?>
                  </span>
                  <div class="progress-bar"><span style="width:<?= (int) $k['avance'] ?>%;"></span></div>
                </div>
                <a href="index.php?ruta=curso&amp;id=<?= (int) $k['curso_id'] ?>" class="btn btn--ghost btn--sm">Continuar</a>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-kardex">
          <div class="panel">
            <div class="panel__head">
              <h3>Kardex</h3>
              <form class="filter-row" method="get" action="index.php">
                <input type="hidden" name="ruta" value="estudiante">
                <input type="hidden" name="tab" value="kardex">
                <select name="categoria" aria-label="Categoría">
                  <option value="">Categoría: todas</option>
                  <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id_categoria'] ?>"<?= (int) ($filtros['categoria'] ?? 0) === $c['id_categoria'] ? ' selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="estado" aria-label="Estado">
                  <option value="">Estado: todos</option>
                  <option value="terminado"<?= ($filtros['estado'] ?? '') === 'terminado' ? ' selected' : '' ?>>Solo terminados</option>
                </select>
                <select name="cursos" aria-label="Cursos activos">
                  <option value="">Activos e inactivos</option>
                  <option value="activos"<?= ($filtros['cursos'] ?? '') === 'activos' ? ' selected' : '' ?>>Solo activos</option>
                </select>
                <input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>" style="width:150px;" title="Inscrito desde" aria-label="Inscrito desde">
                <input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>" style="width:150px;" title="Inscrito hasta" aria-label="Inscrito hasta">
                <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                <a href="index.php?ruta=estudiante&amp;tab=kardex" class="btn btn--ghost btn--sm">Limpiar</a>
              </form>
            </div>
            <table>
              <thead>
                <tr><th>Curso</th><th>Categoría</th><th>Inscripción</th><th>Último ingreso</th><th>Terminación</th><th>Avance</th><th>Estado</th><th></th></tr>
              </thead>
              <tbody>
                <?php if (!$kardex): ?>
                <tr><td colspan="8" class="field-hint">No hay cursos que coincidan con estos filtros.</td></tr>
                <?php endif; ?>
                <?php foreach ($kardex as $k): ?>
                <tr>
                  <td><?= htmlspecialchars($k['titulo']) ?></td>
                  <td><?= htmlspecialchars($k['categorias'] ?? '—') ?></td>
                  <td><?= Formato::fecha($k['fecha_inscripcion']) ?></td>
                  <td><?= Formato::fecha($k['ultima_fecha_acceso']) ?></td>
                  <td><?= Formato::fecha($k['fecha_terminacion']) ?></td>
                  <td><div class="progress-bar"><span style="width:<?= (int) $k['avance'] ?>%;"></span></div></td>
                  <td>
                    <?= $k['completado'] ? '<span class="badge badge--ok">Terminado</span>' : '<span class="badge badge--warn">En curso</span>' ?>
                    <?= $k['activo'] ? '' : '<span class="badge badge--off">Baja</span>' ?>
                  </td>
                  <td>
                    <?php if ($k['completado']): ?>
                      <a class="btn btn--outline btn--sm" href="index.php?ruta=diploma&amp;curso=<?= (int) $k['curso_id'] ?>">Diploma</a>
                    <?php elseif ($k['activo']): ?>
                      <a class="btn btn--ghost btn--sm" href="index.php?ruta=curso&amp;id=<?= (int) $k['curso_id'] ?>">Continuar</a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
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