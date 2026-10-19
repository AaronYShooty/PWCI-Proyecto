<?php
$titulo = 'Crear cuenta | Walter';
$activa = 'registro';
$errores = $errores ?? [];
$datos = $datos ?? [];
$claseCampo = fn($c) => isset($errores[$c]) ? ' has-error' : '';
$errorCampo = fn($c) => isset($errores[$c]) ? '<span class="field-error" role="alert">' . htmlspecialchars($errores[$c]) . '</span>' : '';
$valor = fn($c) => htmlspecialchars($datos[$c] ?? '');
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-register">
  <div class="shell" style="max-width: 980px;">
    <div class="auth-shell">
      <div class="auth-visual">
        <div class="stripe-corner" style="opacity:.5;"><img src="publico/img/Fondo.png" alt=""></div>
        <div class="auth-visual__top"><img src="publico/img/Logo_Walter.png" alt="Walter"> Walter</div>
        <div class="auth-visual__quote">
          <h2>Aprende a tu ritmo, con instructores reales</h2>
          <p>Crea tu cuenta gratis para inscribirte a cursos o para empezar a ofrecer los tuyos.</p>
        </div>
      </div>
      <div class="auth-panel">
        <h2 style="font-size:1.4rem; margin-bottom:22px;">Crea tu cuenta</h2>

        <form class="auth-form is-active" id="form-register" method="post" action="index.php?ruta=registro" enctype="multipart/form-data" novalidate>
          <?php if ($errores): ?>
          <div class="form-alert is-visible form-alert--error" role="status" aria-live="polite">Revisa los campos marcados en rojo.</div>
          <?php else: ?>
          <div class="form-alert" role="status" aria-live="polite"></div>
          <?php endif; ?>

          <div class="field<?= $claseCampo('foto') ?>">
            <label for="reg-foto">Foto de perfil</label>
            <div class="avatar-upload">
              <div class="avatar-upload__preview" id="reg-foto-preview">🖼️</div>
              <div class="avatar-upload__actions">
                <input type="file" id="reg-foto" name="foto" accept="image/jpeg,image/png" hidden required>
                <button type="button" class="btn btn--outline btn--sm" id="reg-foto-btn">Subir foto</button>
                <span class="field-hint">Obligatoria, tipo avatar (JPG o PNG, máximo 2 MB).</span>
              </div>
            </div>
            <?= $errorCampo('foto') ?>
          </div>
          <div class="field<?= $claseCampo('nombre') ?>">
            <label for="reg-nombre">Nombre completo</label>
            <input type="text" id="reg-nombre" name="nombre" value="<?= $valor('nombre') ?>" placeholder="Nombre y apellidos"
                   autocomplete="name" maxlength="80" required>
            <?= $errorCampo('nombre') ?>
          </div>
          <div class="field<?= $claseCampo('email') ?>">
            <label for="reg-email">Correo electrónico</label>
            <input type="email" id="reg-email" name="correo" value="<?= $valor('email') ?>" placeholder="tu@correo.com"
                   autocomplete="email" maxlength="254" required>
            <?= $errorCampo('email') ?>
          </div>
          <div style="display:flex; gap:14px;">
            <div class="field<?= $claseCampo('genero') ?>" style="flex:1;">
              <label for="reg-genero">Género</label>
              <select id="reg-genero" name="genero" required>
                <option value="">Selecciona…</option>
                <?php foreach (['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'] as $g): ?>
                <option value="<?= $g ?>"<?= ($datos['genero'] ?? '') === $g ? ' selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
              <?= $errorCampo('genero') ?>
            </div>
            <div class="field<?= $claseCampo('nacimiento') ?>" style="flex:1;">
              <label for="reg-fecha">Fecha de nacimiento</label>
              <input type="date" id="reg-fecha" name="nacimiento" value="<?= $valor('nacimiento') ?>" required>
              <?= $errorCampo('nacimiento') ?>
            </div>
          </div>
          <div class="field<?= $claseCampo('password') ?>">
            <label for="reg-password">Contraseña</label>
            <div class="input-wrap">
              <input type="password" id="reg-password" name="password" placeholder="Mínimo 8 caracteres"
                     autocomplete="new-password" maxlength="64" required
                     aria-describedby="reg-password-medidor">
              <button type="button" class="pw-toggle" data-target="reg-password"
                      aria-label="Mostrar contraseña">Mostrar</button>
            </div>
            <div class="pw-meter" id="reg-password-medidor" data-nivel="0">
              <div class="pw-meter__track"><span class="pw-meter__fill"></span></div>
              <span class="pw-meter__label" aria-live="polite"></span>
              <ul class="pw-rules">
                <li class="pw-rule" data-regla="largo">Mínimo 8 caracteres</li>
                <li class="pw-rule" data-regla="mayus">Una mayúscula</li>
                <li class="pw-rule" data-regla="numero">Un número</li>
                <li class="pw-rule" data-regla="simbolo">Un carácter especial</li>
              </ul>
            </div>
            <?= $errorCampo('password') ?>
          </div>
          <div class="field<?= $claseCampo('password2') ?>">
            <label for="reg-password2">Confirmar contraseña</label>
            <div class="input-wrap">
              <input type="password" id="reg-password2" name="password2" placeholder="Repite la contraseña"
                     autocomplete="new-password" maxlength="64" required>
              <button type="button" class="pw-toggle" data-target="reg-password2"
                      aria-label="Mostrar contraseña">Mostrar</button>
            </div>
            <span class="field-hint">Escríbela de nuevo a mano, no se permite pegar.</span>
            <?= $errorCampo('password2') ?>
          </div>
          <div class="field<?= $claseCampo('rol') ?>">
            <label>Quiero registrarme como</label>
            <div class="role-toggle">
              <label><input type="radio" name="role" value="estudiante"<?= ($datos['rol'] ?? 'estudiante') === 'estudiante' ? ' checked' : '' ?> style="margin-right:6px;">Estudiante</label>
              <label><input type="radio" name="role" value="instructor"<?= ($datos['rol'] ?? '') === 'instructor' ? ' checked' : '' ?> style="margin-right:6px;">Instructor</label>
            </div>
            <?= $errorCampo('rol') ?>
          </div>
          <button type="submit" class="btn btn--primary" style="margin-top:8px;">Crear mi cuenta</button>
        </form>

        <div class="admin-note">
          🔒 Las cuentas de <strong>administrador</strong> no se crean desde este formulario. Se asignan directamente en la base de datos por el equipo del portal.
        </div>

        <p class="auth-switch">¿Ya tienes cuenta? <a href="index.php?ruta=login">Inicia sesión</a></p>
      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>