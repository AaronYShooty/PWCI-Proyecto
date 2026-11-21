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
          <div class="panel__head"><h3>Forma de pago</h3></div>

          <?php if ($error): ?>
            <div class="form-alert is-visible form-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
          <?php elseif ($pendiente): ?>
            <div class="form-alert is-visible form-alert--ok" role="status">Tu pago sigue pendiente. El acceso se activa al confirmarse.</div>
            <div class="curso-acciones">
              <?php if ($vale): ?><a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($vale) ?>" target="_blank" rel="noopener noreferrer">Abrir referencia de OXXO</a><?php endif; ?>
              <a class="btn btn--outline btn--sm" href="index.php?ruta=pago&amp;curso=<?= $curso['id_curso'] ?>">Consultar estado</a>
            </div>
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
              <div class="curso-acciones" id="pago-pendiente" hidden>
                <a class="btn btn--primary btn--sm" id="pago-vale" target="_blank" rel="noopener noreferrer" hidden>Abrir referencia de OXXO</a>
                <a class="btn btn--outline btn--sm" href="index.php?ruta=pago&amp;curso=<?= $curso['id_curso'] ?>">Consultar estado</a>
              </div>
              <span class="field-hint">Stripe procesa el pago. Si eliges OXXO, tendrás acceso cuando se confirme el pago en efectivo.</span>
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

<?php if (!$error && !$pendiente): ?>
<script src="https://js.stripe.com/v3/"></script>
<script src="publico/js/pago.js"></script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/_pie.php'; ?>
