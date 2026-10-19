<?php
$titulo = 'Diploma | Walter';
$activa = 'diploma';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-diploma">
  <div class="shell" style="max-width:900px;">
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
        <h2 class="diploma__course">Fundamentos de PHP orientado a objetos</h2>
        <div class="diploma__footer">
          <div>
            <span class="field-hint">Fecha de terminación</span>
            <strong>28 de agosto de 2026</strong>
          </div>
          <div>
            <span class="field-hint">Certificado por</span>
            <strong>L. Reyes · Instructor</strong>
          </div>
        </div>
      </div>
    </div>
    <div style="text-align:center; margin-top:18px;">
      <button class="btn btn--primary">Descargar diploma</button>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>