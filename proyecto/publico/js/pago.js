/* Página de pago de WalterEdu: formulario de Stripe Elements dentro de nuestra propia página.
   El servidor crea el cobro (PaymentIntent) y lo verifica al final; aquí solo se captura la tarjeta
   en los campos seguros de Stripe y se confirma el pago. */
(function () {
  const raiz = document.getElementById('pago-app');
  if (!raiz) return;

  const { llave, secreto, csrf, retorno, confirmar } = raiz.dataset;
  const form = document.getElementById('pago-form');
  const boton = document.getElementById('pago-boton');
  const aviso = document.getElementById('pago-aviso');

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

    const { error, paymentIntent } = await stripe.confirmPayment({
      elements: elementos,
      confirmParams: { return_url: retorno },
      redirect: 'if_required',
    });

    if (error) {
      mostrar('error', error.message || 'No se pudo procesar el pago.');
      boton.disabled = false;
      boton.textContent = textoOriginal;
      return;
    }

    if (paymentIntent && paymentIntent.status === 'succeeded') {
      try {
        const respuesta = await fetch(confirmar, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ payment_intent: paymentIntent.id }),
        });
        const cuerpo = await respuesta.json();
        if (cuerpo.exito) {
          mostrar('ok', cuerpo.mensaje);
          window.location.href = cuerpo.datos.redirigir;
          return;
        }
        mostrar('error', cuerpo.mensaje || 'No pudimos confirmar tu pago.');
      } catch (err) {
        mostrar('error', 'El pago se realizó, pero no pudimos confirmarlo. Recarga el curso o contáctanos.');
      }
    } else {
      mostrar('error', 'El pago no se completó (estado: ' + (paymentIntent ? paymentIntent.status : 'desconocido') + ').');
    }
    boton.disabled = false;
    boton.textContent = textoOriginal;
  });
})();