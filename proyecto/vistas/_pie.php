<?php if (!empty($_SESSION['usuario'])): $csrfSalida = $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); ?>
<dialog class="salida-dialog" id="confirmar-salida" aria-labelledby="salida-dialog-titulo">
  <h2 id="salida-dialog-titulo">¿Cerrar sesión?</h2>
  <form method="post" action="index.php?ruta=salir" class="salida-acciones">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfSalida) ?>">
    <button class="btn btn--outline btn--sm" id="cancelar-salida" type="button" autofocus>Cancelar</button>
    <button class="btn btn--primary btn--sm" type="submit">Confirmar</button>
  </form>
</dialog>
<?php endif; ?>
<script src="publico/js/script.js"></script>
</body>
</html>
