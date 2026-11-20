<?php
/** @var array $curso @var string $llavePublica @var string|null $secreto @var string|null $error
 *  @var string $csrf @var string $urlRetorno @var string $urlConfirmar */
$titulo = 'Pago del curso | Walter';
$activa = 'curso';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-pago">
  <div class="shell" style="max-width:980px;">
    <div class="breadcrumb"><a href="index.php?ruta=curso&amp;id=<?= (int) $curso['id_curso'] ?>">← Volver al curso</a></div>
    <h1 class="course-detail__title">Finalizar compra</h1>

    <div class="course-detail">
      <div class="course-detail__main">
        <div class="panel">
          <div class="panel__head"><h3>Pago con tarjeta</h3></div>

          <?php if ($error): ?>
            <div class="form-alert is-visible form-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
          <?php else: ?>
          <div id="pago-app"
               data-llave="<?= htmlspecialchars($llavePublica) ?>"
               data-secreto="<?= htmlspecialchars($secreto) ?>"
               data-csrf="<?= htmlspecialchars($csrf) ?>"
               data-retorno="<?= htmlspecialchars($urlRetorno) ?>"
               data-confirmar="<?= htmlspecialchars($urlConfirmar) ?>">
            <form id="pago-form" style="display:flex; flex-direction:column; gap:16px;">
              <div id="payment-element"><span class="field-hint">Cargando formulario de pago…</span></div>
              <div class="form-alert" id="pago-aviso" role="alert" aria-live="polite"></div>
              <button type="submit" class="btn btn--primary" id="pago-boton" disabled>Pagar <?= Formato::moneda((float) $curso['precio']) ?></button>
              <span class="field-hint">🔒 Los datos de tu tarjeta los captura Stripe de forma segura; WalterEdu nunca los recibe. Pagos de prueba: 4242 4242 4242 4242.</span>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <aside class="course-detail__side">
        <div class="panel">
          <div class="panel__head"><h3>Resumen</h3></div>
          <strong><?= htmlspecialchars($curso['titulo']) ?></strong>
          <span class="field-hint" style="display:block; margin:4px 0 14px;">Instructor: <?= htmlspecialchars($curso['instructor']) ?></span>
          <div style="display:flex; justify-content:space-between; border-top:1px solid var(--line); padding-top:12px;">
            <span>Total</span><strong class="price"><?= Formato::moneda((float) $curso['precio']) ?></strong>
          </div>
          <span class="field-hint" style="display:block; margin-top:8px;">Acceso de por vida · MXN</span>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php if (!$error): ?>
<script src="https://js.stripe.com/v3/"></script>
<script src="publico/js/pago.js"></script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/_pie.php'; ?>