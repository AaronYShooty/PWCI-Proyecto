<?php
$titulo = 'Detalle del curso | Walter';
$activa = 'curso';
/** @var array $curso  @var bool $inscrito  @var array|null $aviso  @var string $csrf */
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
          <span class="video-frame__label">Nivel 1 · Introducción a MVC</span>
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
          <div class="level-list">
            <div class="level-row is-done">
              <span class="level-row__check">✔</span>
              <div class="level-row__body"><strong>Nivel 1 · Introducción a MVC</strong><span>Video · 12 min</span></div>
              <span class="pill">Gratis</span>
            </div>
            <div class="level-row is-done">
              <span class="level-row__check">✔</span>
              <div class="level-row__body"><strong>Nivel 2 · Modelos y acceso a datos</strong><span>Video · 18 min · PDF adjunto</span></div>
            </div>
            <div class="level-row is-current">
              <span class="level-row__check">●</span>
              <div class="level-row__body"><strong>Nivel 3 · Controladores y enrutamiento</strong><span>Video · 15 min</span></div>
            </div>
            <div class="level-row">
              <span class="level-row__check">○</span>
              <div class="level-row__body"><strong>Nivel 4 · Vistas y separación de capas</strong><span>Video · 14 min · Enlace externo</span></div>
            </div>
            <div class="level-row">
              <span class="level-row__check">○</span>
              <div class="level-row__body"><strong>Nivel 5 · Middleware y control de acceso</strong><span>Video · 20 min</span></div>
            </div>
            <div class="level-row">
              <span class="level-row__check">○</span>
              <div class="level-row__body"><strong>Nivel 6 · Integración con API propia</strong><span>Video · 22 min</span></div>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="tab-descripcion">
          <p style="color:var(--ink-soft); line-height:1.6; max-width:64ch;"><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
          <div class="chip-list" style="margin-top:14px;">
            <span class="pill">PHP puro</span>
            <span class="pill">POO</span>
            <span class="pill">MVC</span>
            <span class="pill">MySQL</span>
          </div>
        </div>

        <div class="tab-panel" id="tab-comentarios">
          <div class="comment-row">
            <div class="dash-nav__avatar" style="width:34px; height:34px;"></div>
            <div class="comment-row__body">
              <div class="comment-row__head"><strong>Ana Torres</strong><span class="stars">★★★★★</span><span class="comment-row__date">28 ago 2026</span></div>
              <p>Muy claro el manejo del enrutamiento, me sirvió bastante para mi proyecto final.</p>
            </div>
          </div>
          <div class="comment-row">
            <div class="dash-nav__avatar" style="width:34px; height:34px; background:linear-gradient(135deg, var(--teal), var(--primary));"></div>
            <div class="comment-row__body">
              <div class="comment-row__head"><strong>Jorge Pérez</strong><span class="stars">★★★★☆</span><span class="comment-row__date">15 ago 2026</span></div>
              <p>Este comentario fue eliminado por el administrador.</p>
            </div>
          </div>
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
            <span>📄 6 niveles · con video obligatorio</span>
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