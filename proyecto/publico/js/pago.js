/* Página de pago de WalterEdu: formulario de Stripe Elements dentro de nuestra propia página.
   El servidor verifica el PaymentIntent antes de conceder acceso. */
(function () {
  const raiz = document.getElementById('pago-app');
  if (!raiz) return;

  const { llave, secreto, csrf, retorno, confirmar } = raiz.dataset;
  const form = document.getElementById('pago-form');
  const boton = document.getElementById('pago-boton');
  const aviso = document.getElementById('pago-aviso');
  const acciones = document.getElementById('pago-pendiente');
  const vale = document.getElementById('pago-vale');

  function mostrar(tipo, texto) {
    aviso.textContent = texto;
    aviso.className = 'form-alert is-visible form-alert--' + tipo;
  }

  if (!window.Stripe) {
    mostrar('error', 'No se pudo cargar Stripe. Revisa tu conexión a internet y recarga la página.');
    return;
  }

  const stripe = window.Stripe(llave, { locale: 'es' });
  const elementos = stripe.elements({
    clientSecret: secreto,
    locale: 'es',
    appearance: { theme: 'stripe', variables: { colorPrimary: '#0a6fb8', borderRadius: '10px' } },
  });
  const campoPago = elementos.create('payment', { layout: 'tabs' });
  campoPago.mount('#payment-element');
  campoPago.on('ready', () => { boton.disabled = false; });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    boton.disabled = true;
    aviso.className = 'form-alert';
    const textoOriginal = boton.textContent;
    boton.textContent = 'Procesando…';

    let pagoEnviado = false;
    try {
      const { error, paymentIntent } = await stripe.confirmPayment({
        elements: elementos,
        confirmParams: { return_url: retorno },
        redirect: 'if_required',
      });
      if (error || !paymentIntent) throw new Error(error?.message || 'No se pudo procesar el pago.');
      pagoEnviado = ['succeeded', 'processing', 'requires_action'].includes(paymentIntent.status);

      const respuesta = await fetch(confirmar, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ payment_intent: paymentIntent.id }),
      });
      const cuerpo = await respuesta.json();
      if (!respuesta.ok || !cuerpo.exito) throw new Error(cuerpo.mensaje || 'No pudimos confirmar tu pago.');
      mostrar('ok', cuerpo.mensaje);
      if (!cuerpo.datos.pendiente) {
        window.location.assign(cuerpo.datos.redirigir);
        return;
      }
      if (cuerpo.datos.vale) {
        vale.href = cuerpo.datos.vale;
        vale.hidden = false;
      }
    } catch (error) {
      mostrar('error', pagoEnviado ? 'Consulta el estado del pago antes de volver a intentarlo.' : error.message);
    }
    if (pagoEnviado) {
      boton.hidden = true;
      acciones.hidden = false;
    } else {
      boton.disabled = false;
      boton.textContent = textoOriginal;
    }
  });
})();
