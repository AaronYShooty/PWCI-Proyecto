// Cada ventana del prototipo vive en su propio archivo HTML (index.html, login.html,
// register.html, curso.html, crear-curso.html, estudiante.html, instructor.html,
// admin.html, diploma.html) y se navega entre ellas con enlaces normales.
//
// Este script hace dos cosas: controla las interacciones dentro de una misma ventana
// (tabs, chips) y valida los formularios de acceso y de registro.
//
// IMPORTANTE: la validación de este archivo es solo del lado del cliente. Sirve para
// avisarle al usuario qué está mal antes de enviar el formulario, pero cualquiera
// puede saltársela desactivando JavaScript. En la versión final estas mismas reglas
// tienen que repetirse en el servidor antes de guardar nada en la base de datos.

document.addEventListener('DOMContentLoaded', () => {
  initTabsPanelLateral();
  initListaMensajes();
  initTabsCurso();
  initChipsCategoria();
  initMostrarContrasena();
  initFormLogin();
  initFormRegistro();
});

/* =========================================================================
   1. Interacciones de la interfaz
   ========================================================================= */

// Tabs del panel lateral en Estudiante / Instructor / Administrador
function initTabsPanelLateral() {
  const dashLinks = document.querySelectorAll('.dash-nav a[data-tab]');
  dashLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      dashLinks.forEach(l => l.classList.remove('is-active'));
      link.classList.add('is-active');
      document.querySelectorAll('.dash-tab-panel').forEach(p => p.classList.remove('is-active'));
      document.getElementById('dashtab-' + link.dataset.tab).classList.add('is-active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });
}

// Conversaciones de ejemplo en la ventana de Mensajes (solo resalta visualmente)
function initListaMensajes() {
  document.querySelectorAll('.messenger__item').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.messenger__item').forEach(i => i.classList.remove('is-active'));
      item.classList.add('is-active');
    });
  });
}

// Tabs de la ventana de detalle de curso (Contenido / Descripción / Comentarios)
function initTabsCurso() {
  const courseTabs = document.querySelectorAll('#courseTabs button');
  courseTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      courseTabs.forEach(t => t.classList.remove('is-active'));
      tab.classList.add('is-active');
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('is-active'));
      document.getElementById('tab-' + tab.dataset.tab).classList.add('is-active');
    });
  });
}

// Chips de categoría en la ventana pública (solo resalta visualmente)
function initChipsCategoria() {
  document.querySelectorAll('.category-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
    });
  });
}

// Botón "Mostrar / Ocultar" de los campos de contraseña
function initMostrarContrasena() {
  document.querySelectorAll('.pw-toggle').forEach(boton => {
    boton.addEventListener('click', () => {
      const input = document.getElementById(boton.dataset.target);
      if (!input) return;
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      boton.textContent = visible ? 'Mostrar' : 'Ocultar';
      boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
      input.focus();
    });
  });
}

/* =========================================================================
   2. Reglas de validación
   ========================================================================= */

// Formato de correo: usuario@dominio.ext, sin espacios, sin puntos dobles y sin
// puntos o guiones sueltos al principio o al final de cada parte.
const RE_CORREO = /^[a-z0-9]+([._%+-][a-z0-9]+)*@[a-z0-9]+([.-][a-z0-9]+)*\.[a-z]{2,}$/i;

// Nombre completo: al menos dos palabras, solo letras (con acentos), espacios,
// apóstrofos y guiones.
const RE_NOMBRE = /^[a-záéíóúüñ]+(?:[ \u0027\-][a-záéíóúüñ]+)+$/i;

// Caracteres que contamos como "símbolo" dentro de la contraseña.
const RE_SIMBOLO = /[!@#$%^&*()_\-+=[\]{};:\u0027"\\|,.<>\/?¿¡~`€£°]/;

const EDAD_MINIMA = 13;
const EDAD_MAXIMA = 120;
const MAX_FOTO = 2 * 1024 * 1024;              // 2 MB
const TIPOS_FOTO = ['image/jpeg', 'image/png'];
const LARGO_MIN_PASSWORD = 5;
const LARGO_MAX_PASSWORD = 64;

// Requisitos de la contraseña. El mismo arreglo alimenta el mensaje de error y la
// lista de requisitos que se va marcando mientras el usuario escribe.
const REGLAS_PASSWORD = [
  { id: 'largo',   falta: 'al menos ' + LARGO_MIN_PASSWORD + ' caracteres', prueba: v => v.length >= LARGO_MIN_PASSWORD },
  { id: 'mayus',   falta: 'una letra mayúscula',                          prueba: v => /[A-ZÁÉÍÓÚÜÑ]/.test(v) },
  { id: 'numero',  falta: 'un número',                                    prueba: v => /[0-9]/.test(v) },
  { id: 'simbolo', falta: 'un carácter especial (! @ # $ …)',             prueba: v => RE_SIMBOLO.test(v) }
];

/* --- Validadores de cada campo -------------------------------------------- */

function validarCorreo(el) {
  const valor = el.value.trim();
  if (!valor) return 'Escribe tu correo electrónico.';
  if (/\s/.test(valor)) return 'El correo no puede llevar espacios.';
  if (valor.length > 254) return 'El correo es demasiado largo (máximo 254 caracteres).';

  const partes = valor.split('@');
  if (partes.length === 1) return 'Al correo le falta el signo @ (ejemplo: nombre@correo.com).';
  if (partes.length > 2) return 'El correo solo puede llevar un signo @.';

  const local = partes[0];
  const dominio = partes[1];
  if (!local) return 'Falta el nombre de usuario antes del @.';
  if (local.length > 64) return 'La parte antes del @ es demasiado larga.';
  if (!dominio) return 'Falta el dominio después del @ (ejemplo: correo.com).';
  if (!dominio.includes('.')) return 'Al dominio le falta la extensión (ejemplo: .com o .mx).';
  if (valor.includes('..')) return 'El correo no puede tener dos puntos seguidos.';

  if (!RE_CORREO.test(valor)) {
    return 'El formato del correo no es válido. Revisa que no tenga caracteres extraños ni puntos o guiones sueltos.';
  }
  return '';
}

function validarNombre(el) {
  const valor = el.value.trim().replace(/\s+/g, ' ');
  if (!valor) return 'Escribe tu nombre completo.';
  if (valor.length < 5) return 'El nombre es demasiado corto.';
  if (valor.length > 80) return 'El nombre es demasiado largo (máximo 80 caracteres).';
  if (/[0-9]/.test(valor)) return 'El nombre no debe llevar números.';
  if (!valor.includes(' ')) return 'Escribe nombre y apellidos, no solo el nombre.';
  if (!RE_NOMBRE.test(valor)) return 'El nombre solo puede llevar letras, espacios, guiones y apóstrofos.';
  return '';
}

function validarGenero(el) {
  if (!el.value) return 'Selecciona una opción.';
  return '';
}

function validarFechaNacimiento(el) {
  const valor = el.value;
  if (!valor) return 'Selecciona tu fecha de nacimiento.';

  const fecha = new Date(valor + 'T00:00:00');
  if (Number.isNaN(fecha.getTime())) return 'La fecha no es válida.';

  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  if (fecha > hoy) return 'La fecha de nacimiento no puede estar en el futuro.';

  const edad = calcularEdad(fecha, hoy);
  if (edad < EDAD_MINIMA) return 'Debes tener al menos ' + EDAD_MINIMA + ' años para crear una cuenta.';
  if (edad > EDAD_MAXIMA) return 'Revisa la fecha: el año parece estar mal escrito.';
  return '';
}

function calcularEdad(nacimiento, hoy) {
  let edad = hoy.getFullYear() - nacimiento.getFullYear();
  const cumpleEsteAnio = new Date(hoy.getFullYear(), nacimiento.getMonth(), nacimiento.getDate());
  if (hoy < cumpleEsteAnio) edad--;
  return edad;
}

function validarFoto(el) {
  const archivo = el.files && el.files[0];
  if (!archivo) return 'La foto de perfil es obligatoria.';
  if (!TIPOS_FOTO.includes(archivo.type)) return 'La foto debe ser JPG o PNG.';
  if (archivo.size > MAX_FOTO) {
    return 'La foto pesa ' + (archivo.size / 1024 / 1024).toFixed(1) + ' MB y el máximo son 2 MB.';
  }
  return '';
}

// Contraseña nueva: debe cumplir todos los requisitos de REGLAS_PASSWORD.
function validarPasswordNueva(el) {
  const valor = el.value;
  if (!valor) return 'Escribe una contraseña.';
  if (valor.length > LARGO_MAX_PASSWORD) return 'La contraseña no puede pasar de ' + LARGO_MAX_PASSWORD + ' caracteres.';

  const faltantes = REGLAS_PASSWORD.filter(r => !r.prueba(valor)).map(r => r.falta);
  if (faltantes.length) return 'A la contraseña le falta ' + listaEnTexto(faltantes) + '.';
  return '';
}

function validarConfirmacion(el, form) {
  const original = form.querySelector('#reg-password');
  if (!el.value) return 'Repite la contraseña.';
  if (original && el.value !== original.value) return 'Las dos contraseñas no coinciden.';
  return '';
}

// En el login no revisamos la fuerza (la cuenta podría ser anterior a estas reglas),
// solo que el campo venga lleno y con un largo posible.
function validarPasswordLogin(el) {
  if (!el.value) return 'Escribe tu contraseña.';
  if (el.value.length < LARGO_MIN_PASSWORD) {
    return 'Las contraseñas del portal tienen al menos ' + LARGO_MIN_PASSWORD + ' caracteres; revisa que no te falten letras.';
  }
  if (el.value.length > LARGO_MAX_PASSWORD) return 'La contraseña no puede pasar de ' + LARGO_MAX_PASSWORD + ' caracteres.';
  return '';
}

/* --- Apoyos para la contraseña -------------------------------------------- */

// Nivel de 0 a 4 para la barra de seguridad: un punto por cada requisito cumplido.
function calcularFuerza(valor) {
  if (!valor) return { nivel: 0, etiqueta: '' };

  const cumplidos = REGLAS_PASSWORD.filter(r => r.prueba(valor)).length;
  const etiquetas = ['Muy débil', 'Muy débil', 'Débil', 'Casi lista', 'Válida'];
  return { nivel: Math.max(cumplidos, 1), etiqueta: etiquetas[cumplidos] };
}

function listaEnTexto(items) {
  if (items.length === 1) return items[0];
  return items.slice(0, -1).join(', ') + ' y ' + items[items.length - 1];
}

/* =========================================================================
   3. Motor de validación: marcado de errores y control del envío
   ========================================================================= */

function contenedorDeCampo(el) {
  return el.closest('.field');
}

function mostrarError(el, mensaje) {
  const campo = contenedorDeCampo(el);
  if (!campo) return;

  let aviso = campo.querySelector('.field-error');
  if (!aviso) {
    aviso = document.createElement('span');
    aviso.className = 'field-error';
    aviso.id = (el.id || 'campo') + '-error';
    aviso.setAttribute('role', 'alert');
    campo.appendChild(aviso);
  }
  aviso.textContent = mensaje;
  campo.classList.add('has-error');
  campo.classList.remove('is-valid');
  el.setAttribute('aria-invalid', 'true');
  // Sin perder lo que el campo ya describía (por ejemplo el medidor de seguridad).
  el.setAttribute('aria-describedby', [descripcionOriginal(el), aviso.id].filter(Boolean).join(' '));
}

// Guarda una sola vez el aria-describedby que traía el HTML para poder restaurarlo.
function descripcionOriginal(el) {
  if (el.dataset.describe === undefined) {
    el.dataset.describe = el.getAttribute('aria-describedby') || '';
  }
  return el.dataset.describe;
}

function limpiarError(el, marcarValido) {
  const campo = contenedorDeCampo(el);
  if (!campo) return;

  campo.classList.remove('has-error');
  campo.classList.toggle('is-valid', Boolean(marcarValido) && el.value !== '');
  el.removeAttribute('aria-invalid');

  const original = descripcionOriginal(el);
  if (original) el.setAttribute('aria-describedby', original);
  else el.removeAttribute('aria-describedby');

  const aviso = campo.querySelector('.field-error');
  if (aviso) aviso.textContent = '';
}

function mostrarAviso(caja, tipo, texto) {
  if (!caja) return;
  caja.className = 'form-alert is-visible form-alert--' + tipo;
  caja.textContent = texto;
}

function ocultarAviso(caja) {
  if (!caja) return;
  caja.className = 'form-alert';
  caja.textContent = '';
}

// Conecta un formulario con sus reglas. `reglas` es un objeto { idDelCampo: validador };
// cada validador recibe (elemento, formulario) y devuelve '' o el texto del error.
function configurarValidacion(form, reglas, alEnviarValido) {
  if (!form) return null;
  form.setAttribute('novalidate', '');

  const caja = form.querySelector('.form-alert');
  let yaIntento = false;

  const campos = Object.keys(reglas)
    .map(id => ({ el: form.querySelector('#' + id), validar: reglas[id] }))
    .filter(campo => campo.el);

  function revisar(campo) {
    const error = campo.validar(campo.el, form) || '';
    if (error) mostrarError(campo.el, error);
    else limpiarError(campo.el, true);
    return !error;
  }

  campos.forEach(campo => {
    // Al salir del campo avisamos si ya escribió algo. Mientras escribe solo
    // actualizamos el mensaje si ya intentó enviar, para no regañarlo desde la
    // primera letra.
    campo.el.addEventListener('blur', () => {
      if (yaIntento || campo.el.value) revisar(campo);
    });
    campo.el.addEventListener('change', () => {
      if (yaIntento || campo.el.value) revisar(campo);
    });
    campo.el.addEventListener('input', () => {
      if (yaIntento) revisar(campo);
      ocultarAviso(caja);
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    yaIntento = true;

    let primeroMal = null;
    let errores = 0;
    campos.forEach(campo => {
      if (!revisar(campo)) {
        errores++;
        if (!primeroMal) primeroMal = campo.el;
      }
    });

    if (errores) {
      mostrarAviso(caja, 'error', errores === 1
        ? 'Falta corregir 1 campo. Revisa lo marcado en rojo.'
        : 'Faltan por corregir ' + errores + ' campos. Revisa lo marcado en rojo.');
      enfocar(primeroMal);
      return;
    }

    ocultarAviso(caja);
    if (alEnviarValido) alEnviarValido(form, caja);
  });

  return { revisarTodo: () => campos.every(revisar) };
}

// Los inputs de archivo están ocultos, así que enfocamos su botón visible.
function enfocar(el) {
  if (!el) return;
  const campo = contenedorDeCampo(el);
  const visible = el.offsetParent !== null ? el : (campo ? campo.querySelector('button') : null);
  if (visible && typeof visible.focus === 'function') visible.focus();
  if (campo) campo.scrollIntoView({ block: 'center', behavior: 'smooth' });
}

/* =========================================================================
   4. Formularios concretos
   ========================================================================= */

function initFormLogin() {
  const form = document.getElementById('form-login');
  if (!form) return;

  // Normalizamos el correo al salir del campo: sin espacios y en minúsculas.
  const correo = form.querySelector('#login-email');
  if (correo) {
    correo.addEventListener('blur', () => { correo.value = correo.value.trim().toLowerCase(); });
  }

  configurarValidacion(form, {
    'login-email': validarCorreo,
    'login-password': validarPasswordLogin
  }, (formulario, caja) => {
    mostrarAviso(caja, 'ok', 'Los datos tienen el formato correcto. En la versión final se enviarían al servidor para comprobar la cuenta.');
  });
}

function initFormRegistro() {
  const form = document.getElementById('form-register');
  if (!form) return;

  const correo = form.querySelector('#reg-email');
  if (correo) {
    correo.addEventListener('blur', () => { correo.value = correo.value.trim().toLowerCase(); });
  }

  // La fecha de nacimiento no puede ser posterior a hoy.
  const fecha = form.querySelector('#reg-fecha');
  if (fecha) fecha.max = new Date().toISOString().slice(0, 10);

  initSubidaFoto(form);
  initMedidorPassword(form);

  configurarValidacion(form, {
    'reg-foto': validarFoto,
    'reg-nombre': validarNombre,
    'reg-email': validarCorreo,
    'reg-genero': validarGenero,
    'reg-fecha': validarFechaNacimiento,
    'reg-password': validarPasswordNueva,
    'reg-password2': validarConfirmacion
  }, (formulario, caja) => {
    mostrarAviso(caja, 'ok', 'Todo correcto. En la versión final aquí se crearía la cuenta en la base de datos.');
  });

  // Si cambia la contraseña original, la confirmación deja de coincidir.
  const password = form.querySelector('#reg-password');
  const password2 = form.querySelector('#reg-password2');
  if (password && password2) {
    password.addEventListener('input', () => {
      if (!password2.value) return;
      const error = validarConfirmacion(password2, form);
      if (error) mostrarError(password2, error);
      else limpiarError(password2, true);
    });
  }
}

// Botón "Subir foto": abre el selector, valida el archivo y muestra la miniatura.
function initSubidaFoto(form) {
  const input = form.querySelector('#reg-foto');
  const boton = form.querySelector('#reg-foto-btn');
  const preview = form.querySelector('#reg-foto-preview');
  if (!input || !boton) return;

  boton.addEventListener('click', () => input.click());

  input.addEventListener('change', () => {
    if (!preview) return;
    const archivo = input.files && input.files[0];

    if (!archivo || validarFoto(input)) {
      preview.style.backgroundImage = '';
      preview.textContent = '🖼️';
      return;
    }
    preview.textContent = '';
    preview.style.backgroundImage = 'url("' + URL.createObjectURL(archivo) + '")';
    boton.textContent = 'Cambiar foto';
  });
}

// Barra de seguridad y lista de requisitos que se marcan mientras el usuario escribe.
function initMedidorPassword(form) {
  const input = form.querySelector('#reg-password');
  const medidor = form.querySelector('#reg-password-medidor');
  if (!input || !medidor) return;

  const relleno = medidor.querySelector('.pw-meter__fill');
  const etiqueta = medidor.querySelector('.pw-meter__label');
  const reglas = medidor.querySelectorAll('.pw-rule');

  input.addEventListener('input', () => {
    const valor = input.value;
    const fuerza = calcularFuerza(valor);

    medidor.dataset.nivel = String(fuerza.nivel);
    if (relleno) relleno.style.width = (fuerza.nivel * 25) + '%';
    if (etiqueta) etiqueta.textContent = fuerza.etiqueta ? 'Seguridad: ' + fuerza.etiqueta : '';

    reglas.forEach(regla => {
      const definicion = REGLAS_PASSWORD.find(r => r.id === regla.dataset.regla);
      regla.classList.toggle('is-ok', Boolean(definicion) && definicion.prueba(valor));
    });
  });
}
