<?php
$titulo = 'Detalle del curso | Walter';
$activa = 'curso';
/** @var array $curso  @var bool $inscrito  @var array|null $aviso  @var string $csrf  @var array $niveles  @var array $vistos  @var array $comentarios */
$rolActual = $_SESSION['usuario']['rol'] ?? 'visitante';
$precio = (float) $curso['precio'];
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-curso">
  <div class="shell">

    <?php if (!empty($aviso)): ?>
    <div class="form-alert is-visible form-alert--<?= $aviso['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= htmlspecialchars($aviso['mensaje']) ?></div>
    <?php endif; ?>

    <div class="breadcrumb"><?= htmlspecialchars(implode(' / ', $curso['categorias'] ?? [])) ?></div>

    <div class="course-detail">
      <div class="course-detail__main">
        <div class="video-frame">
          <div class="stripe-corner" style="opacity:.55;"><img src="publico/img/Fondo.png" alt=""></div>
          <div class="video-frame__play">▶</div>
          <span class="video-frame__label"><?= $niveles ? 'Nivel ' . (int) $niveles[0]['orden'] . ' · ' . htmlspecialchars($niveles[0]['titulo']) : 'Este curso aún no tiene videos publicados' ?></span>
        </div>

        <h1 class="course-detail__title"><?= htmlspecialchars($curso['titulo']) ?></h1>
        <div class="course-detail__meta">
          <?php if ($curso['promedio_calificacion'] !== null): $n = (int) round($curso['promedio_calificacion']); ?>
          <span class="stars"><?= str_repeat('★', $n) . str_repeat('☆', 5 - $n) ?> <span style="color:var(--ink-soft); font-weight:500;"><?= number_format((float) $curso['promedio_calificacion'], 1) ?></span></span>
          <?php else: ?>
          <span style="color:var(--ink-soft);">Sin reseñas</span>
          <?php endif; ?>
          <span>·</span>
          <span>👤 Instructor: <?= htmlspecialchars($curso['instructor']) ?></span>
        </div>

        <div class="tabs-row" id="courseTabs">
          <button class="is-active" data-tab="contenido">Contenido</button>
          <button data-tab="descripcion">Descripción</button>
          <button data-tab="comentarios">Comentarios</button>
        </div>

        <div class="tab-panel is-active" id="tab-contenido">
          <?php if (!$niveles): ?>
          <p class="field-hint">El instructor aún no ha publicado los niveles de este curso.</p>
          <?php endif; ?>
          <div class="level-list">
            <?php $actual = true; foreach ($niveles as $n):
              $visto = in_array((int) $n['id'], $vistos, true);
              $clase = $visto ? ' is-done' : ($inscrito && $actual ? ' is-current' : '');
              if (!$visto) $actual = false;
              $marca = $visto ? '✔' : ($clase === ' is-current' ? '●' : '○'); ?>
            <div class="level-row<?= $clase ?>">
              <span class="level-row__check"><?= $marca ?></span>
              <div class="level-row__body">
                <strong>Nivel <?= (int) $n['orden'] ?> · <?= htmlspecialchars($n['titulo']) ?></strong>
                <span>Video<?= (int) $n['adjuntos'] > 0 ? ' · ' . (int) $n['adjuntos'] . ' archivo(s) adjunto(s)' : '' ?></span>
              </div>
              <?php if ((float) $n['precio'] <= 0): ?><span class="pill">Gratis</span>
              <?php else: ?><span class="pill"><?= Formato::moneda((float) $n['precio']) ?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="tab-panel" id="tab-descripcion">
          <p style="color:var(--ink-soft); line-height:1.6; max-width:64ch;"><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
          <div class="chip-list" style="margin-top:14px;">
            <?php foreach ($curso['categorias'] as $cat): ?><span class="pill"><?= htmlspecialchars($cat) ?></span><?php endforeach; ?>
            <span class="pill">Nivel de dificultad <?= (int) $curso['nivel'] ?> de 5</span>
          </div>
        </div>

        <div class="tab-panel" id="tab-comentarios">
          <?php if (!$comentarios): ?><p class="field-hint">Todavía no hay comentarios en este curso.</p><?php endif; ?>
          <?php foreach ($comentarios as $m): ?>
          <div class="comment-row">
            <div class="dash-nav__avatar" style="width:34px; height:34px; <?= Formato::avatar($m['foto']) ?>"></div>
            <div class="comment-row__body">
              <div class="comment-row__head">
                <strong><?= htmlspecialchars($m['autor']) ?></strong>
                <span class="stars"><?= Formato::estrellas((float) $m['calificacion']) ?></span>
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
      </div>

      <aside class="course-detail__side">
        <div class="panel">
          <div class="price" style="font-size:1.6rem;"><?= $precio > 0 ? '$' . number_format($precio, 2) : 'Gratis' ?></div>
          <span class="field-hint">Acceso de por vida</span>
          <?php if ($inscrito): ?>
          <div class="form-alert is-visible form-alert--ok" style="margin-top:16px;">✔ Ya estás inscrito en este curso</div>
          <?php elseif ($rolActual === 'estudiante'): ?>
          <form method="post" action="index.php?ruta=comprar">
            <input type="hidden" name="curso" value="<?= (int) $curso['id_curso'] ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button type="submit" class="btn btn--primary" style="width:100%; margin-top:16px;"><?= $precio > 0 ? 'Comprar curso' : 'Inscribirme gratis' ?></button>
          </form>
          <?php if ($precio > 0): ?><span class="field-hint" style="display:block; margin-top:8px;">🔒 Pago seguro con Stripe</span><?php endif; ?>
          <?php else: ?>
          <span class="field-hint" style="display:block; margin-top:16px;">Inicia sesión como estudiante para comprar este curso.</span>
          <?php endif; ?>
          <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--line); font-size:0.84rem; color:var(--ink-soft); display:flex; flex-direction:column; gap:8px;">
            <span>📄 <?= count($niveles) ?> nivel(es) · con video obligatorio</span>
            <span>💬 Mensajería directa con el instructor</span>
            <span>🏅 Diploma al completar el curso</span>
          </div>
        </div>
        <div class="panel">
          <div class="panel__head"><h3 style="font-size:0.95rem;">Instructor</h3></div>
          <div style="display:flex; gap:10px; align-items:center;">
            <div class="dash-nav__avatar" style="background:linear-gradient(135deg, var(--teal), var(--primary));"></div>
            <div><strong style="display:block; font-size:0.9rem;"><?= htmlspecialchars($curso['instructor']) ?></strong><span style="font-size:0.78rem; color:var(--ink-soft);">Instructor</span></div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>