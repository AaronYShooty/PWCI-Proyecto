<?php
$perfil = $usuario ?? [];
$titulo = 'Mi perfil | Walter';
$activa = 'perfil';
$errores = $errores ?? [];
$datos = $datos ?? [];
$actual = [
    'nombre'     => $perfil['nombre_completo'] ?? '',
    'email'      => $perfil['email'] ?? '',
    'genero'     => $perfil['genero'] ?? '',
    'nacimiento' => $perfil['fecha_nacimiento'] ?? '',
];
$claseCampo = fn($c) => isset($errores[$c]) ? ' has-error' : '';
$errorCampo = fn($c) => isset($errores[$c]) ? '<span class="field-error" role="alert">' . htmlspecialchars($errores[$c]) . '</span>' : '';
$valor = fn($c) => htmlspecialchars($datos[$c] ?? $actual[$c]);
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = function (?string $texto) use ($meses): string {
    if (!$texto) return '—';
    $momento = strtotime($texto);
    return date('d', $momento) . ' ' . $meses[(int) date('n', $momento) - 1] . ' ' . date('Y', $momento);
};
$etiquetasRol = ['estudiante' => 'Estudiante', 'instructor' => 'Instructor', 'administrador' => 'Administrador'];
$foto = $perfil['foto'] ?? '';
require __DIR__ . '/_encabezado.php';
?>

<section class="window is-active" id="win-perfil">
  <div class="shell" style="max-width: 720px;">
    <div class="auth-panel">
      <h2 style="font-size:1.4rem; margin-bottom:6px;">Mi perfil</h2>
      <p class="field-hint" style="margin-bottom:22px;">
        <?= $etiquetasRol[$perfil['rol'] ?? ''] ?? '' ?> ·
        Miembro desde <?= $fecha($perfil['fecha_registro'] ?? null) ?> ·
        Última actualización <?= $fecha($perfil['fecha_actualizacion'] ?? null) ?>
      </p>

      <form class="auth-form is-active" id="form-perfil" method="post" action="index.php?ruta=perfil" enctype="multipart/form-data" novalidate>
        <?php if ($errores): ?>
        <div class="form-alert is-visible form-alert--error" role="status" aria-live="polite">Revisa los campos marcados en rojo.</div>
        <?php elseif (!empty($aviso)): ?>
        <div class="form-alert is-visible form-alert--ok" role="status" aria-live="polite"><?= htmlspecialchars($aviso) ?></div>
        <?php else: ?>
        <div class="form-alert" role="status" aria-live="polite"></div>
        <?php endif; ?>

        <div class="field<?= $claseCampo('foto') ?>">
          <label for="perfil-foto">Foto de perfil</label>
          <div class="avatar-upload">
            <div class="avatar-upload__preview" id="perfil-foto-preview"<?= $foto ? ' style="background-image:url(\'' . htmlspecialchars($foto) . '\')" data-original="' . htmlspecialchars($foto) . '"' : '' ?>><?= $foto ? '' : '🖼️' ?></div>
            <div class="avatar-upload__actions">
              <input type="file" id="perfil-foto" name="foto" accept="image/jpeg,image/png" hidden>
              <button type="button" class="btn btn--outline btn--sm" id="perfil-foto-btn">Cambiar foto</button>
              <span class="field-hint">Opcional. Si no eliges una, se conserva la actual (JPG o PNG, máximo 2 MB).</span>
            </div>
          </div>
          <?= $errorCampo('foto') ?>
        </div>
        <div class="field<?= $claseCampo('nombre') ?>">
          <label for="perfil-nombre">Nombre completo</label>
          <input type="text" id="perfil-nombre" name="nombre" value="<?= $valor('nombre') ?>" placeholder="Nombre y apellidos"
                 autocomplete="name" maxlength="80" required>
          <?= $errorCampo('nombre') ?>
        </div>
        <div class="field<?= $claseCampo('email') ?>">
          <label for="perfil-email">Correo electrónico</label>
          <input type="email" id="perfil-email" name="correo" value="<?= $valor('email') ?>" placeholder="tu@correo.com"
                 autocomplete="email" maxlength="254" required>
          <?= $errorCampo('email') ?>
        </div>
        <div style="display:flex; gap:14px;">
          <div class="field<?= $claseCampo('genero') ?>" style="flex:1;">
            <label for="perfil-genero">Género</label>
            <select id="perfil-genero" name="genero" required>
              <option value="">Selecciona…</option>
              <?php foreach (['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'] as $g): ?>
              <option value="<?= $g ?>"<?= ($datos['genero'] ?? $actual['genero']) === $g ? ' selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
            <?= $errorCampo('genero') ?>
          </div>
          <div class="field<?= $claseCampo('nacimiento') ?>" style="flex:1;">
            <label for="perfil-fecha">Fecha de nacimiento</label>
            <input type="date" id="perfil-fecha" name="nacimiento" value="<?= $valor('nacimiento') ?>" required>
            <?= $errorCampo('nacimiento') ?>
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:8px;">Guardar cambios</button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_pie.php'; ?>
