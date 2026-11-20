<?php
/** @var array|null $aviso @var string $csrf @var array $conteos @var int $totalCategorias @var array $usuarios @var array $comentarios
 *  @var array $causas @var array $global @var array $actividad @var array $categorias @var string $tipoReporte @var array $reporte @var array $filtros @var array $perfil */
$titulo = 'Panel del administrador | Walter';
$activa = 'admin';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-admin">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="<?= Formato::avatar($perfil['foto'], 'background:linear-gradient(135deg, var(--ink), var(--primary));') ?>"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Cuenta admin</span>
          </div>
        </div>
        <a class="is-active" data-tab="usuarios">Usuarios</a>
        <a data-tab="categorias">Categorías</a>
        <a data-tab="comentarios">Comentarios</a>
        <a data-tab="reportes">Reportes</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <div class="dash-main">
        <?php if (!empty($aviso)): ?>
        <div class="form-alert is-visible form-alert--<?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= htmlspecialchars($aviso['mensaje']) ?></div>
        <?php endif; ?>

        <div class="dash-tab-panel is-active" id="dashtab-usuarios">
          <div class="stat-row">
            <div class="stat-card"><span>Usuarios activos</span><strong><?= (int) $conteos['activos'] ?></strong></div>
            <div class="stat-card"><span>Instructores</span><strong><?= (int) $conteos['instructores'] ?></strong></div>
            <div class="stat-card"><span>Categorías</span><strong><?= (int) $totalCategorias ?></strong></div>
            <div class="stat-card"><span>Cuentas bloqueadas</span><strong><?= (int) $conteos['bloqueados'] ?></strong></div>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Gestión de usuarios</h3>
              <form class="filter-row" method="get" action="index.php">
                <input type="hidden" name="ruta" value="admin">
                <input type="hidden" name="tab" value="usuarios">
                <select name="tipo" aria-label="Tipo de usuario">
                  <option value="">Tipo: todos</option>
                  <option value="instructor"<?= ($filtros['tipo'] ?? '') === 'instructor' ? ' selected' : '' ?>>Instructor</option>
                  <option value="estudiante"<?= ($filtros['tipo'] ?? '') === 'estudiante' ? ' selected' : '' ?>>Estudiante</option>
                  <option value="administrador"<?= ($filtros['tipo'] ?? '') === 'administrador' ? ' selected' : '' ?>>Administrador</option>
                </select>
                <input type="text" name="q" value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" placeholder="Buscar usuario…">
                <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                <a href="index.php?ruta=admin&amp;tab=usuarios" class="btn btn--ghost btn--sm">Limpiar</a>
              </form>
            </div>
            <table>
              <thead>
                <tr><th>Usuario</th><th>Nombre</th><th>Tipo</th><th>Fecha ingreso</th><th>Estado</th><th></th></tr>
              </thead>
              <tbody>
                <?php if (!$usuarios): ?><tr><td colspan="6" class="field-hint">No hay usuarios con esos filtros.</td></tr><?php endif; ?>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                  <td><?= ucfirst($u['rol']) ?></td>
                  <td><?= Formato::fecha($u['fecha_registro']) ?></td>
                  <td><?= $u['activo'] ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--off">Bloqueado</span>' ?></td>
                  <td>
                    <?php if ($u['rol'] !== 'administrador'): ?>
                    <form method="post" action="index.php?ruta=usuario-estado" style="margin:0;">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="usuario" value="<?= (int) $u['id'] ?>">
                      <input type="hidden" name="accion" value="<?= $u['activo'] ? 'bloquear' : 'desbloquear' ?>">
                      <button type="submit" class="btn <?= $u['activo'] ? 'btn--outline' : 'btn--primary' ?> btn--sm"><?= $u['activo'] ? 'Bloquear' : 'Desbloquear' ?></button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-categorias">
          <div class="two-col">
            <div class="panel">
              <div class="panel__head"><h3>Categorías</h3></div>
              <table>
                <thead><tr><th>Categoría</th><th>Cursos</th><th>Creada por</th><th>Fecha</th></tr></thead>
                <tbody id="categorias-cuerpo"><tr><td colspan="4">Cargando categorías…</td></tr></tbody>
              </table>
            </div>
            <div class="panel">
              <div class="panel__head"><h3 style="font-size:0.98rem;">Nueva categoría</h3></div>
              <form id="form-categoria" style="display:flex; flex-direction:column; gap:12px;" novalidate>
                <div class="form-alert" role="status" aria-live="polite"></div>
                <div class="field">
                  <label for="cat-nombre">Nombre</label>
                  <input type="text" id="cat-nombre" name="nombre" placeholder="Ej. Fotografía" maxlength="80" required>
                </div>
                <div class="field">
                  <label for="cat-descripcion">Descripción</label>
                  <textarea id="cat-descripcion" name="descripcion" rows="3" placeholder="¿Qué tipo de cursos contendrá?" maxlength="1000"></textarea>
                </div>
                <button type="submit" class="btn btn--primary btn--sm" style="align-self:flex-start;">+ Agregar categoría</button>
              </form>
            </div>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-comentarios">
          <div class="panel">
            <div class="panel__head"><h3>Moderación de comentarios</h3></div>
            <?php if (!$comentarios): ?><p class="field-hint">Todavía no hay comentarios en los cursos.</p><?php endif; ?>
            <?php foreach ($comentarios as $m): ?>
            <div class="comment-row">
              <div class="dash-nav__avatar" style="width:34px; height:34px; <?= Formato::avatar($m['foto']) ?>"></div>
              <div class="comment-row__body">
                <div class="comment-row__head">
                  <strong><?= htmlspecialchars($m['autor']) ?></strong>
                  <span class="stars"><?= Formato::estrellas((float) $m['calificacion']) ?></span>
                  <span class="comment-row__date"><?= Formato::fecha($m['fecha_creacion']) ?> · <?= htmlspecialchars($m['curso']) ?></span>
                </div>
                <?php if ($m['eliminado']): ?>
                  <p class="field-hint">Eliminado el <?= Formato::fecha($m['fecha_eliminacion']) ?> · Causa: <?= htmlspecialchars($m['causa_eliminacion']) ?></p>
                <?php else: ?>
                  <p>"<?= htmlspecialchars($m['texto']) ?>"</p>
                  <form method="post" action="index.php?ruta=comentario-eliminar" style="display:flex; gap:8px; margin-top:10px;"
                        onsubmit="return confirm('¿Eliminar este comentario?');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="comentario" value="<?= (int) $m['id'] ?>">
                    <select name="causa" style="font-size:0.78rem;" aria-label="Causa de eliminación">
                      <?php foreach ($causas as $causa): ?><option value="<?= htmlspecialchars($causa) ?>">Causa: <?= htmlspecialchars(strtolower($causa)) ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn--danger btn--sm">Eliminar comentario</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-reportes">
          <div class="stat-row">
            <div class="stat-card"><span>Cursos activos</span><strong><?= (int) $global['cursos_activos'] ?></strong></div>
            <div class="stat-card"><span>Inscripciones</span><strong><?= (int) $global['inscripciones'] ?></strong></div>
            <div class="stat-card"><span>Cursos terminados</span><strong><?= (int) $global['terminados'] ?></strong></div>
            <div class="stat-card"><span>Ingresos de la plataforma</span><strong><?= Formato::moneda((float) $global['ingresos']) ?></strong></div>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Actividad por curso</h3>
              <form class="filter-row" method="get" action="index.php">
                <input type="hidden" name="ruta" value="admin">
                <input type="hidden" name="tab" value="reportes">
                <input type="hidden" name="reporte" value="<?= htmlspecialchars($tipoReporte) ?>">
                <select name="categoria" aria-label="Categoría">
                  <option value="">Categoría: todas</option>
                  <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id_categoria'] ?>"<?= (int) ($filtros['categoria'] ?? 0) === $c['id_categoria'] ? ' selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
              </form>
            </div>
            <table>
              <thead>
                <tr><th>Curso</th><th>Instructor</th><th>Categoría</th><th>Inscritos</th><th>Terminaron</th><th>Calificación</th><th>Ingresos</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <?php if (!$actividad): ?><tr><td colspan="8" class="field-hint">No hay cursos para mostrar.</td></tr><?php endif; ?>
                <?php foreach ($actividad as $a): $pct = $a['inscritos'] > 0 ? (int) round(100 * $a['terminaron'] / $a['inscritos']) : 0; ?>
                <tr>
                  <td><?= htmlspecialchars($a['titulo']) ?></td>
                  <td><?= htmlspecialchars($a['instructor']) ?></td>
                  <td><?= htmlspecialchars($a['categorias'] ?? '—') ?></td>
                  <td><?= (int) $a['inscritos'] ?></td>
                  <td><?= $pct ?>%<div class="progress-bar" style="margin-top:4px;"><span style="width:<?= $pct ?>%"></span></div></td>
                  <td><?= $a['calificacion'] === null ? 'Sin calificar' : number_format((float) $a['calificacion'], 1) . ' ★' ?></td>
                  <td><?= Formato::moneda((float) $a['ingresos']) ?></td>
                  <td><?= $a['activo'] ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--off">Inactivo</span>' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Reporte de usuarios activos</h3>
              <form class="filter-row" method="get" action="index.php">
                <input type="hidden" name="ruta" value="admin">
                <input type="hidden" name="tab" value="reportes">
                <input type="hidden" name="categoria" value="<?= (int) ($filtros['categoria'] ?? 0) ?: '' ?>">
                <select name="reporte" aria-label="Tipo de reporte" onchange="this.form.submit()">
                  <option value="instructor"<?= $tipoReporte === 'instructor' ? ' selected' : '' ?>>Instructores</option>
                  <option value="estudiante"<?= $tipoReporte === 'estudiante' ? ' selected' : '' ?>>Estudiantes</option>
                </select>
              </form>
            </div>
            <table>
              <?php if ($tipoReporte === 'instructor'): ?>
              <thead><tr><th>Usuario</th><th>Nombre</th><th>Fecha ingreso</th><th>Cursos ofrecidos</th><th>Total de ganancias</th></tr></thead>
              <tbody>
                <?php if (!$reporte): ?><tr><td colspan="5" class="field-hint">No hay instructores activos.</td></tr><?php endif; ?>
                <?php foreach ($reporte as $r): ?>
                <tr><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['nombre_completo']) ?></td><td><?= Formato::fecha($r['fecha_registro']) ?></td><td><?= (int) $r['cursos'] ?></td><td><?= Formato::moneda((float) $r['ganancias']) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
              <?php else: ?>
              <thead><tr><th>Usuario</th><th>Nombre</th><th>Fecha ingreso</th><th>Cursos inscritos</th><th>% de cursos terminados</th></tr></thead>
              <tbody>
                <?php if (!$reporte): ?><tr><td colspan="5" class="field-hint">No hay estudiantes activos.</td></tr><?php endif; ?>
                <?php foreach ($reporte as $r): ?>
                <tr><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['nombre_completo']) ?></td><td><?= Formato::fecha($r['fecha_registro']) ?></td><td><?= (int) $r['inscritos'] ?></td><td><?= (int) $r['pct_terminados'] ?>%</td></tr>
                <?php endforeach; ?>
              </tbody>
              <?php endif; ?>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_abrir_tab.php'; ?>
<?php require dirname(__DIR__) . '/_pie.php'; ?>