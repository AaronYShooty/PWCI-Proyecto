<?php
$titulo = 'Iniciar sesión | Walter';
$activa = 'login';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-login">
  <div class="shell" style="max-width: 980px;">
    <div class="auth-shell">
      <div class="auth-visual">
        <div class="stripe-corner" style="opacity:.5;"><img src="publico/img/Fondo.png" alt=""></div>
        <div class="auth-visual__top"><img src="publico/img/Logo_Walter.png" alt="Walter"> Walter</div>
        <div class="auth-visual__quote">
          <h2>Retoma tu curso justo donde lo dejaste</h2>
          <p>Guarda tu avance, obtén tu diploma y platica con tu instructor sin salir del portal.</p>
        </div>
      </div>
      <div class="auth-panel">
        <h2 style="font-size:1.4rem; margin-bottom:22px;">Inicia sesión en tu cuenta</h2>

        <form class="auth-form is-active" id="form-login" method="post" action="index.php?ruta=login" novalidate>
          <?php if (!empty($error)): ?>
          <div class="form-alert is-visible form-alert--error" role="status" aria-live="polite"><?= htmlspecialchars($error) ?></div>
          <?php elseif (!empty($aviso)): ?>
          <div class="form-alert is-visible form-alert--ok" role="status" aria-live="polite"><?= htmlspecialchars($aviso) ?></div>
          <?php else: ?>
          <div class="form-alert" role="status" aria-live="polite"></div>
          <?php endif; ?>

          <div class="field">
            <label for="login-email">Correo electrónico</label>
            <input type="email" id="login-email" name="correo" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="tu@correo.com"
                   autocomplete="email" maxlength="254" required>
          </div>
          <div class="field">
            <label for="login-password">Contraseña</label>
            <div class="input-wrap">
              <input type="password" id="login-password" name="password" placeholder="••••••••"
                     autocomplete="current-password" maxlength="64" required>
              <button type="button" class="pw-toggle" data-target="login-password"
                      aria-label="Mostrar contraseña">Mostrar</button>
            </div>
            <span class="field-hint">3 intentos fallidos consecutivos bloquean la cuenta.</span>
          </div>
          <button type="submit" class="btn btn--primary" style="margin-top:8px;">Entrar al portal</button>
          <a href="#" class="link-more" style="text-align:center; font-size:0.85rem;">¿Olvidaste tu contraseña?</a>
        </form>

        <p class="auth-switch">¿Aún no tienes cuenta? <a href="index.php?ruta=registro">Regístrate aquí</a></p>
      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>