<?php /** @var array $perfil */ ?>
<div class="panel" style="max-width:520px;">
  <div class="panel__head"><h3>Mi perfil</h3></div>
  <div class="avatar-upload" style="margin-bottom:18px;">
    <div class="avatar-upload__preview" style="<?= Formato::avatar($perfil['foto'] ?? null, 'background:linear-gradient(135deg, var(--teal), var(--primary));') ?>"><?= empty($perfil['foto']) ? '🖼️' : '' ?></div>
    <div class="avatar-upload__actions">
      <a href="index.php?ruta=perfil" class="btn btn--outline btn--sm">Editar mi perfil</a>
    </div>
  </div>
  <div style="display:flex; flex-direction:column; gap:14px;">
    <div class="field"><label>Nombre completo</label><input type="text" value="<?= htmlspecialchars($perfil['nombre_completo']) ?>" readonly></div>
    <div class="field"><label>Correo electrónico</label><input type="email" value="<?= htmlspecialchars($perfil['email']) ?>" readonly></div>
    <div style="display:flex; gap:14px;">
      <div class="field" style="flex:1;"><label>Género</label><input type="text" value="<?= htmlspecialchars($perfil['genero']) ?>" readonly></div>
      <div class="field" style="flex:1;"><label>Fecha de nacimiento</label><input type="text" value="<?= Formato::fecha($perfil['fecha_nacimiento']) ?>" readonly></div>
    </div>
    <span class="field-hint">Miembro desde <?= Formato::fecha($perfil['fecha_registro']) ?> · Última actualización: <?= Formato::fecha($perfil['fecha_actualizacion']) ?></span>
  </div>
</div>