<?php
/** @var array $contactos @var array|null $conActual @var array $hilo @var string $csrf @var string $panelRuta */
$miId = (int) $_SESSION['usuario']['id'];
?>
<div class="panel messenger">
  <div class="panel__head"><h3>Mensajes</h3></div>
  <?php if (!$contactos): ?>
    <p class="field-hint" style="padding:8px 4px;">
      <?= $panelRuta === 'instructor'
          ? 'Todavía no tienes conversaciones. Cuando un estudiante se inscriba a uno de tus cursos podrás escribirle aquí.'
          : 'Todavía no tienes conversaciones. Cuando te inscribas a un curso podrás escribirle a su instructor desde aquí.' ?>
    </p>
  <?php else: ?>
  <div class="messenger__layout">
    <div class="messenger__list">
      <?php foreach ($contactos as $c): ?>
      <a class="messenger__item<?= (int) $c['id'] === (int) $conActual['id'] ? ' is-active' : '' ?>"
         href="index.php?ruta=<?= $panelRuta ?>&amp;tab=mensajes&amp;con=<?= (int) $c['id'] ?>" style="text-decoration:none; color:inherit;">
        <div class="dash-nav__avatar" style="width:36px; height:36px; <?= Formato::avatar($c['foto']) ?>"></div>
        <div class="messenger__item-body">
          <strong><?= htmlspecialchars($c['nombre']) ?></strong>
          <span><?= htmlspecialchars($c['ultimo_texto'] ?? $c['curso']) ?></span>
        </div>
        <span class="messenger__time"><?= Formato::hora($c['ultima_fecha']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="messenger__thread">
      <div class="messenger__thread-head">
        <div class="dash-nav__avatar" style="width:32px; height:32px; <?= Formato::avatar($conActual['foto']) ?>"></div>
        <div><strong><?= htmlspecialchars($conActual['nombre']) ?></strong>
          <span class="field-hint"><?= $panelRuta === 'instructor' ? 'Estudiante' : 'Instructor' ?> · <?= htmlspecialchars($conActual['curso']) ?></span></div>
      </div>
      <?php if (!$hilo): ?>
        <p class="field-hint" style="padding:12px;">Aún no hay mensajes. Escribe el primero.</p>
      <?php endif; ?>
      <?php foreach ($hilo as $m): ?>
        <div class="messenger__bubble messenger__bubble--<?= (int) $m['emisor_id'] === $miId ? 'out' : 'in' ?>">
          <?= nl2br(htmlspecialchars($m['texto'])) ?>
          <span class="field-hint messenger__timestamp"><?= Formato::fecha($m['fecha_envio']) ?> · <?= date('H:i', strtotime($m['fecha_envio'])) ?></span>
        </div>
      <?php endforeach; ?>
      <form class="messenger__thread-input" method="post" action="index.php?ruta=mensaje-enviar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="para" value="<?= (int) $conActual['id'] ?>">
        <input type="text" name="texto" placeholder="Escribe un mensaje…" maxlength="1000" required>
        <button type="submit" class="btn btn--primary btn--sm">Enviar</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>
