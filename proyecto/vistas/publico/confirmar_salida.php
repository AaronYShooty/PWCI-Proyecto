<?php
$titulo = 'Cerrar sesión | Walter';
require dirname(__DIR__) . '/_encabezado.php';
?>
<main class="window is-active">
  <div class="shell">
    <section class="panel salida-panel" aria-labelledby="salida-pregunta">
      <h1 id="salida-pregunta">¿Cerrar sesión?</h1>
      <?php if ($error): ?>
        <p class="form-alert is-visible form-alert--error" role="alert">La confirmación expiró. Inténtalo de nuevo.</p>
      <?php endif; ?>
      <form method="post" action="index.php?ruta=salir" class="salida-acciones">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <a class="btn btn--outline btn--sm" href="index.php">Cancelar</a>
        <button class="btn btn--primary btn--sm" type="submit">Confirmar</button>
      </form>
    </section>
  </div>
</main>
<?php require dirname(__DIR__) . '/_pie.php'; ?>
