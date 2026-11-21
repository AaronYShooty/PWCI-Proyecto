<?php
$titulo = 'Detalle del curso | Walter';
$activa = 'curso';
/** @var array $curso  @var bool $inscrito  @var array|null $aviso  @var string $csrf  @var array $niveles  @var array $vistos  @var array $comentarios */
$rolActual = $_SESSION['usuario']['rol'] ?? 'visitante';
$precio = (float) $curso['precio'];
$id = $curso['id_curso'];
$nivelId = $seleccion ? (int) $seleccion['id'] : null;
$tabResenas = ($_GET['tab'] ?? '') === 'comentarios';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-curso">
  <div class="shell course-shell">

    <?php if (!empty($aviso)): ?>
    <div class="form-alert is-visible form-alert--<?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= htmlspecialchars($aviso['mensaje']) ?></div>
    <?php endif; ?>

    <div class="course-detail course-detail--aprendizaje<?= $niveles ? '' : ' course-detail--sin-lista' ?>">
      <div class="course-detail__player">
        <?php if ($seleccion): ?>
          <video class="curso-video" id="curso-video" controls preload="metadata" src="api/niveles/<?= $nivelId ?>/video"
                 <?= $inscrito && !in_array($nivelId, $vistos, true) ? 'data-avance="api/niveles/' . $nivelId . '/avance"' : '' ?>></video>
          <p class="field-hint">Nivel <?= (int) $seleccion['orden'] ?> · <?= htmlspecialchars($seleccion['titulo']) ?></p>
          <div class="form-alert" id="curso-avance-aviso" aria-live="polite"></div>
        <?php elseif ($niveles && !$inscrito && !$gestiona): ?>
          <p class="panel field-hint">Inscríbete para acceder a los videos del curso.</p>
        <?php else: ?>
          <p class="panel field-hint">Este curso aún no tiene videos disponibles.</p>
        <?php endif; ?>
      </div>

      <?php if ($niveles): ?>
      <aside class="panel course-detail__content" aria-labelledby="curso-contenido-titulo">
        <h2 id="curso-contenido-titulo">Contenido del curso</h2>
        <div class="level-list">
          <?php foreach ($niveles as $n):
            $nId = (int) $n['id'];
            $visto = in_array($nId, $vistos, true);
            $actual = $nId === $nivelId;
            $clase = ($visto ? ' is-done' : '') . ($actual ? ' is-current' : ''); ?>
          <div class="level-row<?= $clase ?>" id="curso-nivel-<?= $nId ?>">
            <span class="level-row__check" aria-label="<?= $visto ? 'Completado' : 'Pendiente' ?>"><?= $visto ? '✔' : '○' ?></span>
            <div class="level-row__body">
              <strong>
                <?php if ($n['disponible']): ?><a href="index.php?ruta=curso&amp;id=<?= $id ?>&amp;nivel=<?= $nId ?>"<?= $actual ? ' aria-current="true"' : '' ?>><?php endif; ?>
                Nivel <?= (int) $n['orden'] ?> · <?= htmlspecialchars($n['titulo']) ?>
                <?php if ($n['disponible']): ?></a><?php endif; ?>
              </strong>
              <span>Video<?= (int) $n['adjuntos'] > 0 ? ' · ' . (int) $n['adjuntos'] . ' archivo(s) adjunto(s)' : '' ?></span>
            </div>
            <span class="pill"><?= $n['gratis'] ? 'Gratis' : 'Para inscritos' ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </aside>
      <?php endif; ?>

      <div class="course-detail__main">
        <div class="course-detail__instructor">
          <div class="dash-nav__avatar" style="<?= Formato::avatar($curso['foto_instructor'], 'background:linear-gradient(135deg, var(--teal), var(--primary));') ?>"></div>
          <div>
            <strong><?= htmlspecialchars($curso['instructor']) ?></strong>
            <span class="field-hint">Instructor</span>
          </div>
        </div>
        <div class="breadcrumb"><?= htmlspecialchars(implode(' / ', $curso['categorias'] ?? [])) ?></div>

        <h1 class="course-detail__title"><?= htmlspecialchars($curso['titulo']) ?></h1>
        <div class="course-detail__meta">
          <?php if ($curso['promedio_calificacion'] !== null): $n = (int) round($curso['promedio_calificacion']); ?>
          <span class="stars"><?= str_repeat('★', $n) . str_repeat('☆', 5 - $n) ?> <span style="color:var(--ink-soft); font-weight:500;"><?= number_format((float) $curso['promedio_calificacion'], 1) ?></span></span>
          <?php else: ?>
          <span style="color:var(--ink-soft);">Sin reseñas</span>
          <?php endif; ?>
        </div>

        <div class="tabs-row" id="courseTabs">
          <button type="button"<?= $tabResenas ? '' : ' class="is-active"' ?> data-tab="descripcion">Descripción</button>
          <button type="button"<?= $tabResenas ? ' class="is-active"' : '' ?> data-tab="comentarios">Reseñas</button>
        </div>

        <div class="tab-panel<?= $tabResenas ? '' : ' is-active' ?>" id="tab-descripcion">
          <p class="course-detail__cost">Precio del curso · <?= $precio > 0 ? Formato::moneda($precio) : 'Gratis' ?></p>
          <p class="course-detail__description"><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
          <span class="pill">Nivel de dificultad <?= (int) $curso['nivel'] ?> de 5</span>
        </div>

        <div class="tab-panel<?= $tabResenas ? ' is-active' : '' ?>" id="tab-comentarios">
          <?php if ($completado && ($_GET['resena'] ?? '') === 'publicada' && !$puedeResenar): ?>
            <div class="form-alert is-visible form-alert--ok" role="status">Reseña publicada correctamente.</div>
          <?php endif; ?>
          <?php if ($puedeResenar): ?>
            <form class="panel nivel-form" id="form-resena" data-endpoint="api/cursos/<?= $id ?>/resenas">
              <h3>Deja tu reseña</h3>
              <div class="form-alert" role="status"></div>
              <div class="field">
                <label for="resena-calificacion">Calificación</label>
                <select id="resena-calificacion" name="calificacion" required>
                  <option value="">Elige una calificación</option>
                  <?php for ($nota = 5; $nota >= 1; --$nota): ?>
                    <option value="<?= $nota ?>"><?= str_repeat('★', $nota) ?> (<?= $nota ?>/5)</option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="field">
                <label for="resena-texto">Tu comentario</label>
                <textarea id="resena-texto" name="texto" maxlength="2000" rows="4" required></textarea>
              </div>
              <button class="btn btn--primary btn--sm" type="submit">Publicar reseña</button>
            </form>
          <?php elseif ($rolActual === 'estudiante' && !$completado): ?>
            <p class="field-hint">Completa el curso para publicar tu reseña.</p>
          <?php endif; ?>
          <?php if (!$comentarios): ?><p class="field-hint">Todavía no hay reseñas en este curso.</p><?php endif; ?>
          <?php foreach ($comentarios as $m): ?>
          <div class="comment-row">
            <div class="dash-nav__avatar" style="width:34px; height:34px; <?= Formato::avatar($m['foto']) ?>"></div>
            <div class="comment-row__body">
              <div class="comment-row__head">
                <strong><?= htmlspecialchars($m['autor']) ?></strong>
                <?php if (!$m['eliminado']): ?><span class="stars" aria-label="<?= (int) $m['calificacion'] ?> de 5 estrellas"><?= Formato::estrellas((float) $m['calificacion']) ?></span><?php endif; ?>
                <span class="comment-row__date"><?= Formato::fecha($m['fecha_creacion']) ?></span>
              </div>
              <?php if ($m['eliminado']): ?>
                <p class="field-hint">Este comentario fue eliminado por el administrador.</p>
              <?php else: ?>
                <p><?= nl2br(htmlspecialchars($m['texto'])) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="course-detail__access">
          <?php if ($completado): ?>
            <p class="course-detail__status">✔ Curso completado</p>
            <a class="btn btn--primary btn--sm" href="index.php?ruta=diploma&amp;curso=<?= $id ?>">Ver diploma</a>
          <?php elseif ($inscrito): ?>
            <p class="course-detail__status">✔ Ya estás inscrito en este curso</p>
          <?php elseif ($rolActual === 'estudiante' && $curso['activo']): ?>
            <form method="post" action="index.php?ruta=comprar">
              <input type="hidden" name="curso" value="<?= $id ?>">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <button type="submit" class="btn btn--primary"><?= $precio > 0 ? 'Comprar curso' : 'Inscribirme gratis' ?></button>
            </form>
            <?php if ($precio > 0): ?><span class="field-hint">Pago seguro con Stripe</span><?php endif; ?>
          <?php elseif ($gestiona): ?>
            <span class="field-hint">Vista previa del curso</span>
          <?php else: ?>
            <span class="field-hint">Inicia sesión como estudiante para comprar este curso.</span>
          <?php endif; ?>
        </div>
        <?php if ($seleccion && ($seleccion['contenido'] || $adjuntos)): ?>
          <section class="course-detail__materials">
            <h3>Material del nivel</h3>
            <?php if ($seleccion['contenido']): ?><p><?= nl2br(htmlspecialchars($seleccion['contenido'])) ?></p><?php endif; ?>
            <?php foreach ($adjuntos as $archivo): ?>
              <p><a href="api/adjuntos/<?= (int) $archivo['id'] ?>"><?= htmlspecialchars($archivo['nombre']) ?></a></p>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>
