<?php
$id = $curso['id_curso'];
$base = 'index.php?ruta=editar-niveles&amp;curso=' . $id;
$titulo = 'Niveles del curso | Walter';
$activa = 'crear-curso';
require dirname(__DIR__) . '/_encabezado.php';
?>
<section class="window is-active">
  <div class="shell">
    <div class="section-heading">
      <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
      <div class="curso-acciones">
        <a class="btn btn--outline btn--sm" href="index.php?ruta=instructor&amp;tab=mis-cursos">Mis cursos</a>
        <a class="btn btn--outline btn--sm" href="index.php?ruta=crear-curso&amp;curso=<?= $id ?>">Editar datos</a>
        <?php if ($niveles): ?>
          <a class="btn btn--outline btn--sm" href="index.php?ruta=curso&amp;id=<?= $id ?>">Vista previa</a>
        <?php endif; ?>
        <?php if (!$curso['activo']): ?>
          <form method="post" action="<?= $base ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button class="btn btn--primary btn--sm" type="submit" name="accion" value="publicar">Publicar curso</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($error || $aviso): ?>
      <div class="form-alert is-visible form-alert--<?= $error ? 'error' : 'ok' ?>" role="status"><?= htmlspecialchars($error ?: $aviso) ?></div>
    <?php endif; ?>
    <div class="two-col">
      <div class="panel">
        <div class="panel__head"><h2>Niveles</h2><a class="btn btn--outline btn--sm" href="<?= $base ?>">Agregar nivel</a></div>
        <?php if (!$niveles): ?><p class="field-hint">Agrega tu primer nivel para publicar el curso.</p><?php endif; ?>
        <div class="level-list">
          <?php foreach ($niveles as $n): ?>
            <a class="level-row<?= $nivel && $nivel['id'] === $n['id'] ? ' is-current' : '' ?>" href="<?= $base ?>&amp;nivel=<?= (int) $n['id'] ?>">
              <div class="level-row__body"><strong>Nivel <?= (int) $n['orden'] ?> · <?= htmlspecialchars($n['titulo']) ?></strong></div>
              <span class="pill"><?= $n['gratis'] ? 'Gratis' : 'Para inscritos' ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel">
        <div class="panel__head"><h2><?= $nivel ? 'Editar nivel' : 'Agregar nivel' ?></h2></div>
        <?php if ($videoDisponible): ?>
          <video class="curso-video" controls preload="metadata" src="api/niveles/<?= (int) $nivel['id'] ?>/video"></video>
        <?php endif; ?>
        <?php foreach ($adjuntos as $archivo): ?>
          <p class="field-hint"><a href="api/adjuntos/<?= (int) $archivo['id'] ?>"><?= htmlspecialchars($archivo['nombre']) ?></a></p>
        <?php endforeach; ?>
        <form class="nivel-form" method="post" enctype="multipart/form-data" action="<?= $base ?><?= $nivel ? '&amp;nivel=' . (int) $nivel['id'] : '' ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="field">
            <label for="nivel-titulo">Título</label>
            <input id="nivel-titulo" name="titulo" maxlength="150" required value="<?= htmlspecialchars(is_string($_POST['titulo'] ?? null) ? $_POST['titulo'] : ($nivel['titulo'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="nivel-contenido">Contenido del nivel</label>
            <textarea id="nivel-contenido" name="contenido" maxlength="5000" rows="4"><?= htmlspecialchars(is_string($_POST['contenido'] ?? null) ? $_POST['contenido'] : ($nivel['contenido'] ?? '')) ?></textarea>
          </div>
          <label><input type="checkbox" name="gratis" value="1" <?= (($_POST['accion'] ?? '') === 'guardar' ? ($_POST['gratis'] ?? '') === '1' : !empty($nivel['gratis'])) ? 'checked' : '' ?>> Permitir vista previa gratis</label>
          <div class="field">
            <label for="nivel-video"><?= $videoDisponible ? 'Reemplazar video' : 'Video del nivel' ?></label>
            <input id="nivel-video" type="file" name="video" accept="video/mp4,video/webm,.mp4,.webm" <?= $videoDisponible ? '' : 'required' ?>>
            <span class="field-hint">MP4 o WebM.</span>
          </div>
          <div class="field">
            <label for="nivel-pdf">Agregar material en PDF</label>
            <input id="nivel-pdf" type="file" name="pdf" accept="application/pdf,.pdf">
          </div>
          <div class="curso-acciones">
            <button class="btn btn--primary btn--sm" type="submit" name="accion" value="guardar">Guardar nivel</button>
            <?php if ($nivel): ?>
              <button class="btn btn--danger btn--sm" type="submit" name="accion" value="eliminar" formnovalidate onclick="return confirm('¿Eliminar este nivel?');">Eliminar nivel</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require dirname(__DIR__) . '/_pie.php'; ?>
