<?php
/** @var array|null $diploma */
$titulo = 'Diploma | Walter';
$activa = 'diploma';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-diploma">
  <div class="shell" style="max-width:900px;">
    <?php if (!$diploma): ?>
      <div class="section-heading"><h2>Todavía no tienes diplomas</h2></div>
      <div class="panel">
        <p>El diploma se genera automáticamente cuando terminas todos los niveles de un curso.</p>
        <a href="index.php?ruta=estudiante" class="btn btn--primary">Ir a mi panel</a>
      </div>
    <?php else: ?>
    <div class="section-heading">
      <h2>Diploma generado automáticamente</h2>
    </div>
    <div class="diploma">
      <div class="stripe-corner" style="opacity:.15;"><img src="publico/img/Fondo.png" alt=""></div>
      <div class="diploma__content">
        <img src="publico/img/Logo_Walter.png" alt="Walter" class="diploma__logo">
        <span class="diploma__eyebrow">Certificado de finalización</span>
        <h1 class="diploma__name"><?= htmlspecialchars($usuario['nombre']) ?></h1>
        <p class="diploma__text">ha completado satisfactoriamente el curso</p>
        <h2 class="diploma__course"><?= htmlspecialchars($diploma['titulo']) ?></h2>
        <div class="diploma__footer">
          <div>
            <span class="field-hint">Fecha de terminación</span>
            <strong><?= Formato::fecha($diploma['fecha_terminacion']) ?></strong>
          </div>
          <div>
            <span class="field-hint">Certificado por</span>
            <strong><?= htmlspecialchars($diploma['instructor']) ?> · Instructor</strong>
          </div>
        </div>
      </div>
    </div>
    <div style="text-align:center; margin-top:18px;">
      <button type="button" class="btn btn--primary" onclick="window.print()">Descargar diploma</button>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>