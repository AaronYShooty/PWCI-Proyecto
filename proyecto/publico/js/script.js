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
  initFormPerfil();
  initCatalogo();
  initCrearCurso();
  initAdminCategorias();
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
const LARGO_MIN_PASSWORD = 8;
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
  }, (formulario) => {
    formulario.submit();
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
  }, (formulario) => {
    formulario.submit();
  });

  // Si cambia la contraseña original, la confirmación deja de coincidir.
  const password = form.querySelector('#reg-password');
  const password2 = form.querySelector('#reg-password2');
  if (password2) {
    ['paste', 'drop'].forEach(evento => {
      password2.addEventListener(evento, (e) => e.preventDefault());
    });
  }
  if (password && password2) {
    password.addEventListener('input', () => {
      if (!password2.value) return;
      const error = validarConfirmacion(password2, form);
      if (error) mostrarError(password2, error);
      else limpiarError(password2, true);
    });
  }
}

function initFormPerfil() {
  const form = document.getElementById('form-perfil');
  if (!form) return;

  const correo = form.querySelector('#perfil-email');
  if (correo) {
    correo.addEventListener('blur', () => { correo.value = correo.value.trim().toLowerCase(); });
  }

  const fecha = form.querySelector('#perfil-fecha');
  if (fecha) fecha.max = new Date().toISOString().slice(0, 10);

  initSubidaFoto(form, 'perfil');

  configurarValidacion(form, {
    'perfil-foto': el => (el.files && el.files[0]) ? validarFoto(el) : '',
    'perfil-nombre': validarNombre,
    'perfil-email': validarCorreo,
    'perfil-genero': validarGenero,
    'perfil-fecha': validarFechaNacimiento
  }, (formulario) => {
    formulario.submit();
  });
}

// Botón "Subir foto": abre el selector, valida el archivo y muestra la miniatura.
function initSubidaFoto(form, prefijo = 'reg') {
  const input = form.querySelector('#' + prefijo + '-foto');
  const boton = form.querySelector('#' + prefijo + '-foto-btn');
  const preview = form.querySelector('#' + prefijo + '-foto-preview');
  if (!input || !boton) return;

  boton.addEventListener('click', () => input.click());

  input.addEventListener('change', () => {
    if (!preview) return;
    const archivo = input.files && input.files[0];

    if (!archivo || validarFoto(input)) {
      const original = preview.dataset.original;
      preview.style.backgroundImage = original ? 'url("' + original + '")' : '';
      preview.textContent = original ? '' : '🖼️';
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

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const CABECERAS_JSON = { Accept: 'application/json', 'Content-Type': 'application/json' };

function pedirApi(url, opciones) {
  return fetch(url, opciones).then(respuesta =>
    respuesta.json().then(cuerpo => ({ respuesta, cuerpo })));
}

function textoDeErrorApi(cuerpo, porDefecto) {
  const detalle = (cuerpo.errores || []).map(e => e.descripcion).join(' ');
  return [cuerpo.mensaje || porDefecto, detalle].join(' ').trim();
}

function mensajeDeError(error) {
  if (error instanceof TypeError) return 'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.';
  if (error instanceof SyntaxError) return 'El servidor respondió de forma inesperada.';
  return error.message;
}

function crearNodo(etiqueta, clase, texto) {
  const nodo = document.createElement(etiqueta);
  if (clase) nodo.className = clase;
  if (texto !== undefined) nodo.textContent = texto;
  return nodo;
}

function formatoMoneda(valor) {
  return '$' + valor.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatoFecha(texto) {
  const fecha = new Date(texto.replace(' ', 'T'));
  if (Number.isNaN(fecha.getTime())) return texto;
  return String(fecha.getDate()).padStart(2, '0') + '-' + MESES[fecha.getMonth()] + '-' + fecha.getFullYear();
}

function crearBotonReintentar(alPulsar) {
  const boton = crearNodo('button', 'btn btn--ghost btn--sm', 'Reintentar');
  boton.type = 'button';
  boton.addEventListener('click', alPulsar);
  return boton;
}

/* --- Catálogo público ------------------------------------------------------ */

function initCatalogo() {
  const bloques = Array.from(document.querySelectorAll('.course-grid[data-orden]'));
  if (!bloques.length) return;

  const filtros = { titulo: '', categoria: '' };
  const recargar = () => bloques.forEach(grid => cargarCursos(grid, filtros));

  const formulario = document.getElementById('form-busqueda');
  const entrada = document.getElementById('busqueda-titulo');
  if (formulario && entrada) {
    formulario.addEventListener('submit', (e) => {
      e.preventDefault();
      filtros.titulo = entrada.value.trim();
      recargar();
    });
  }

  const fila = document.getElementById('categoria-fila');
  if (fila) {
    cargarChips(fila, (id) => {
      filtros.categoria = id;
      recargar();
    });
  }

  recargar();
}

function cargarChips(fila, alElegir) {
  return pedirApi('api/categorias', { headers: { Accept: 'application/json' } })
    .then(({ respuesta, cuerpo }) => {
      if (!respuesta.ok || !cuerpo.exito) throw new Error(textoDeErrorApi(cuerpo, 'No se pudieron cargar las categorías.'));

      const chips = [crearChip('Todas', '', true)]
        .concat(cuerpo.datos.map(c => crearChip(c.nombre, String(c.id_categoria), false)));
      chips.forEach(chip => chip.addEventListener('click', () => {
        chips.forEach(otro => otro.classList.toggle('is-active', otro === chip));
        alElegir(chip.dataset.id);
      }));
      fila.replaceChildren(...chips);
    })
    .catch(error => {
      fila.replaceChildren(
        crearNodo('span', 'field-hint', mensajeDeError(error) + ' '),
        crearBotonReintentar(() => cargarChips(fila, alElegir))
      );
    });
}

function crearChip(texto, id, activo) {
  const chip = crearNodo('button', activo ? 'category-chip is-active' : 'category-chip', texto);
  chip.type = 'button';
  chip.dataset.id = id;
  return chip;
}

function cargarCursos(grid, filtros) {
  const solicitud = String(Number(grid.dataset.solicitud || 0) + 1);
  grid.dataset.solicitud = solicitud;
  mostrarEstadoCatalogo(grid, 'Cargando cursos…');

  const parametros = new URLSearchParams({ orden: grid.dataset.orden, por_pagina: '6' });
  if (filtros.titulo) parametros.set('titulo', filtros.titulo);
  if (filtros.categoria) parametros.set('categoria', filtros.categoria);

  return pedirApi('api/cursos?' + parametros.toString(), { headers: { Accept: 'application/json' } })
    .then(({ respuesta, cuerpo }) => {
      if (grid.dataset.solicitud !== solicitud) return;
      if (!respuesta.ok || !cuerpo.exito) throw new Error(textoDeErrorApi(cuerpo, 'No se pudo cargar el catálogo.'));
      pintarCursos(grid, cuerpo.datos, Boolean(filtros.titulo || filtros.categoria));
    })
    .catch(error => {
      if (grid.dataset.solicitud !== solicitud) return;
      mostrarErrorCatalogo(grid, mensajeDeError(error), () => cargarCursos(grid, filtros));
    });
}

function mostrarEstadoCatalogo(grid, texto) {
  grid.replaceChildren(crearNodo('p', 'course-grid__estado', texto));
}

function mostrarErrorCatalogo(grid, texto, reintentar) {
  const caja = crearNodo('div', 'course-grid__error');
  const aviso = crearNodo('div', 'form-alert is-visible form-alert--error', texto);
  aviso.setAttribute('role', 'alert');
  caja.append(aviso, crearBotonReintentar(reintentar));
  grid.replaceChildren(caja);
}

function pintarCursos(grid, cursos, filtrado) {
  if (!cursos.length) {
    mostrarEstadoCatalogo(grid, filtrado ? 'No encontramos cursos con esos filtros.' : 'Aún no hay cursos publicados.');
    return;
  }
  grid.replaceChildren(...cursos.map(crearTarjetaCurso));
}

function crearTarjetaCurso(curso) {
  const sinPrecio = curso.precio === null;
  const gratis = curso.precio === 0;
  const tarjeta = crearNodo('article', 'course-card');

  const miniatura = crearNodo('div', 'course-card__thumb');
  if (curso.imagen) {
    const imagen = document.createElement('img');
    imagen.src = curso.imagen;
    imagen.alt = '';
    imagen.loading = 'lazy';
    imagen.addEventListener('error', () => imagen.remove());
    miniatura.appendChild(imagen);
  }
  miniatura.appendChild(crearNodo('span', '', gratis ? 'Gratis' : (curso.categorias[0] || 'Curso')));

  const cuerpo = crearNodo('div', 'course-card__body');
  cuerpo.appendChild(crearNodo('h3', '', curso.titulo));
  cuerpo.appendChild(crearNodo('div', 'course-card__meta', '👤 Instructor: ' + curso.instructor));
  cuerpo.appendChild(crearCalificacion(curso.promedio_calificacion));

  const textoPrecio = sinPrecio ? 'Sin precio' : (gratis ? 'Gratis' : formatoMoneda(curso.precio));
  const pie = crearNodo('div', 'course-card__footer');
  pie.appendChild(crearNodo('span', gratis ? 'price free' : 'price', textoPrecio));
  pie.appendChild(crearNodo('span', 'pill', curso.nivel ? 'Nivel ' + curso.nivel : 'Nivel'));
  cuerpo.appendChild(pie);

  tarjeta.append(miniatura, cuerpo);
  tarjeta.tabIndex = 0;
  tarjeta.setAttribute('role', 'link');
  tarjeta.style.cursor = 'pointer';
  const irAlCurso = () => { window.location.href = 'index.php?ruta=curso&id=' + curso.id_curso; };
  tarjeta.addEventListener('click', irAlCurso);
  tarjeta.addEventListener('keydown', e => { if (e.key === 'Enter') irAlCurso(); });
  return tarjeta;
}

function crearCalificacion(promedio) {
  const fila = crearNodo('div', 'course-card__meta stars');
  if (promedio === null) {
    fila.appendChild(crearNodo('span', 'course-card__nota', 'Sin reseñas'));
    return fila;
  }
  const llenas = Math.round(promedio);
  fila.append('★'.repeat(llenas) + '☆'.repeat(5 - llenas) + ' ');
  fila.appendChild(crearNodo('span', 'course-card__nota', promedio.toFixed(1)));
  return fila;
}

/* --- Crear curso ----------------------------------------------------------- */

const CAMPOS_CURSO = {
  titulo: 'cc-titulo',
  descripcion: 'cc-descripcion',
  nivel: 'cc-nivel',
  precio: 'cc-precio',
  categorias: 'cc-categorias',
  imagen: 'cc-imagen'
};

function validarTituloCurso(el) {
  const valor = el.value.trim();
  if (!valor) return 'Escribe el título del curso.';
  if (valor.length > 150) return 'El título es demasiado largo (máximo 150 caracteres).';
  return '';
}

function validarDescripcionCurso(el) {
  const valor = el.value.trim();
  if (!valor) return 'Describe de qué trata el curso.';
  if (valor.length > 5000) return 'La descripción es demasiado larga (máximo 5000 caracteres).';
  return '';
}

function validarCategoriasCurso(el) {
  return el.querySelector('input:checked') ? '' : 'Selecciona al menos una categoría.';
}

function validarNivelCurso(el) {
  return el.value ? '' : 'Selecciona el nivel del curso.';
}

function validarPrecioCurso(el) {
  const valor = el.value.trim();
  if (!valor) return 'Escribe el precio (0 si el curso es gratis).';
  if (!/^\d+(\.\d{1,2})?$/.test(valor)) return 'Escribe un número positivo con máximo dos decimales (ejemplo: 499.00).';
  if (Number(valor) > 99999999.99) return 'El precio es demasiado alto.';
  return '';
}

function validarImagenCurso(el) {
  return el.value.trim().length > 255 ? 'La ruta es demasiado larga (máximo 255 caracteres).' : '';
}

function initCrearCurso() {
  const form = document.getElementById('form-curso');
  if (!form) return;

  const caja = form.querySelector('.form-alert');
  const botones = form.querySelectorAll('button[type="submit"]');
  const actualizarVista = initVistaPrevia(form);

  botones.forEach(boton => boton.addEventListener('click', () => {
    form.dataset.publicar = boton.dataset.publicar;
  }));

  cargarCategoriasCurso(form.querySelector('#cc-categorias'), actualizarVista);

  configurarValidacion(form, {
    'cc-titulo': validarTituloCurso,
    'cc-descripcion': validarDescripcionCurso,
    'cc-categorias': validarCategoriasCurso,
    'cc-nivel': validarNivelCurso,
    'cc-precio': validarPrecioCurso,
    'cc-imagen': validarImagenCurso
  }, () => enviarCurso(form, caja, botones, actualizarVista));
}

function cargarCategoriasCurso(grupo, alTerminar) {
  grupo.replaceChildren(crearNodo('span', 'field-hint', 'Cargando categorías…'));

  return pedirApi('api/categorias', { headers: { Accept: 'application/json' } })
    .then(({ respuesta, cuerpo }) => {
      if (!respuesta.ok || !cuerpo.exito) throw new Error(textoDeErrorApi(cuerpo, 'No se pudieron cargar las categorías.'));

      if (!cuerpo.datos.length) {
        grupo.replaceChildren(crearNodo('span', 'field-hint', 'Aún no hay categorías. Pide al administrador que cree una.'));
        return;
      }
      grupo.replaceChildren(...cuerpo.datos.map(categoria => {
        const etiqueta = crearNodo('label', 'categoria-opcion');
        const casilla = document.createElement('input');
        casilla.type = 'checkbox';
        casilla.value = String(categoria.id_categoria);
        casilla.dataset.nombre = categoria.nombre;
        etiqueta.append(casilla, ' ' + categoria.nombre);
        return etiqueta;
      }));
      alTerminar();
    })
    .catch(error => {
      grupo.replaceChildren(
        crearNodo('span', 'field-hint', mensajeDeError(error) + ' '),
        crearBotonReintentar(() => cargarCategoriasCurso(grupo, alTerminar))
      );
    });
}

function initVistaPrevia(form) {
  const contenedor = document.getElementById('vista-previa');
  if (!contenedor) return () => {};

  const instructor = form.dataset.instructor || '';

  const actualizar = () => {
    const textoPrecio = form.querySelector('#cc-precio').value.trim();
    const precio = textoPrecio !== '' && !Number.isNaN(Number(textoPrecio)) ? Number(textoPrecio) : null;
    const nivel = Number(form.querySelector('#cc-nivel').value) || null;
    const categorias = Array.from(form.querySelectorAll('#cc-categorias input:checked'), c => c.dataset.nombre);

    contenedor.replaceChildren(crearTarjetaCurso({
      titulo: form.querySelector('#cc-titulo').value.trim() || 'Título del curso',
      instructor,
      precio,
      nivel,
      categorias,
      imagen: null,
      promedio_calificacion: null
    }));
  };

  form.addEventListener('input', actualizar);
  form.addEventListener('change', actualizar);
  actualizar();
  return actualizar;
}

function enviarCurso(form, caja, botones, actualizarVista) {
  const publicar = form.dataset.publicar === '1';
  const cuerpo = {
    titulo: form.querySelector('#cc-titulo').value.trim(),
    descripcion: form.querySelector('#cc-descripcion').value.trim(),
    nivel: Number(form.querySelector('#cc-nivel').value),
    precio: Number(form.querySelector('#cc-precio').value.trim()),
    categorias: Array.from(form.querySelectorAll('#cc-categorias input:checked'), c => Number(c.value)),
    activo: publicar
  };
  const imagen = form.querySelector('#cc-imagen').value.trim();
  if (imagen) cuerpo.imagen = imagen;

  botones.forEach(boton => { boton.disabled = true; });
  mostrarAviso(caja, 'ok', 'Guardando el curso…');

  return pedirApi('api/cursos', { method: 'POST', headers: CABECERAS_JSON, body: JSON.stringify(cuerpo) })
    .then(({ respuesta, cuerpo: resultado }) => {
      if (respuesta.status === 201) {
        form.reset();
        form.querySelectorAll('.field').forEach(campo => campo.classList.remove('has-error', 'is-valid'));
        actualizarVista();
        mostrarAviso(caja, 'ok', publicar ? 'Curso publicado correctamente.' : 'Borrador guardado correctamente.');
        return;
      }
      if (respuesta.status === 400) {
        (resultado.errores || []).forEach(error => {
          const campo = form.querySelector('#' + (CAMPOS_CURSO[error.campo] || ''));
          if (campo) mostrarError(campo, error.descripcion);
        });
        mostrarAviso(caja, 'error', textoDeErrorApi(resultado, 'Revisa los campos marcados.'));
        return;
      }
      if (respuesta.status === 401) {
        mostrarAviso(caja, 'error', 'Tu sesión expiró. Vuelve a iniciar sesión para publicar.');
        return;
      }
      mostrarAviso(caja, 'error', resultado.mensaje || 'No se pudo guardar el curso.');
    })
    .catch(error => mostrarAviso(caja, 'error', mensajeDeError(error)))
    .finally(() => botones.forEach(boton => { boton.disabled = false; }));
}

/* --- Categorías del administrador ------------------------------------------ */

function validarNombreCategoria(el) {
  const valor = el.value.trim();
  if (!valor) return 'Escribe el nombre de la categoría.';
  if (valor.length > 80) return 'El nombre es demasiado largo (máximo 80 caracteres).';
  return '';
}

function validarDescripcionCategoria(el) {
  return el.value.length > 1000 ? 'La descripción es demasiado larga (máximo 1000 caracteres).' : '';
}

function initAdminCategorias() {
  const cuerpoTabla = document.getElementById('categorias-cuerpo');
  const form = document.getElementById('form-categoria');
  if (!cuerpoTabla || !form) return;

  cargarTablaCategorias(cuerpoTabla);

  configurarValidacion(form, {
    'cat-nombre': validarNombreCategoria,
    'cat-descripcion': validarDescripcionCategoria
  }, () => enviarCategoria(form, form.querySelector('.form-alert'), cuerpoTabla));
}

function filaMensaje(texto, reintentar) {
  const celda = crearNodo('td', '', texto + ' ');
  celda.colSpan = 4;
  if (reintentar) celda.appendChild(crearBotonReintentar(reintentar));
  const fila = document.createElement('tr');
  fila.appendChild(celda);
  return fila;
}

function cargarTablaCategorias(cuerpoTabla) {
  cuerpoTabla.replaceChildren(filaMensaje('Cargando categorías…'));

  return pedirApi('api/categorias', { headers: { Accept: 'application/json' } })
    .then(({ respuesta, cuerpo }) => {
      if (!respuesta.ok || !cuerpo.exito) throw new Error(textoDeErrorApi(cuerpo, 'No se pudieron cargar las categorías.'));

      if (!cuerpo.datos.length) {
        cuerpoTabla.replaceChildren(filaMensaje('Aún no hay categorías.'));
        return;
      }
      cuerpoTabla.replaceChildren(...cuerpo.datos.map(categoria => {
        const fila = document.createElement('tr');
        [categoria.nombre, String(categoria.total_cursos), categoria.creada_por, formatoFecha(categoria.fecha_creacion)]
          .forEach(texto => fila.appendChild(crearNodo('td', '', texto)));
        return fila;
      }));
    })
    .catch(error => {
      cuerpoTabla.replaceChildren(filaMensaje(mensajeDeError(error), () => cargarTablaCategorias(cuerpoTabla)));
    });
}

function enviarCategoria(form, caja, cuerpoTabla) {
  const boton = form.querySelector('button[type="submit"]');
  const cuerpo = { nombre: form.querySelector('#cat-nombre').value.trim() };
  const descripcion = form.querySelector('#cat-descripcion').value.trim();
  if (descripcion) cuerpo.descripcion = descripcion;

  boton.disabled = true;
  mostrarAviso(caja, 'ok', 'Guardando la categoría…');

  return pedirApi('api/categorias', { method: 'POST', headers: CABECERAS_JSON, body: JSON.stringify(cuerpo) })
    .then(({ respuesta, cuerpo: resultado }) => {
      if (respuesta.status === 201) {
        form.reset();
        form.querySelectorAll('.field').forEach(campo => campo.classList.remove('has-error', 'is-valid'));
        mostrarAviso(caja, 'ok', 'Categoría creada correctamente.');
        cargarTablaCategorias(cuerpoTabla);
        return;
      }
      if (respuesta.status === 400) {
        (resultado.errores || []).forEach(error => {
          const campo = form.querySelector(error.campo === 'nombre' ? '#cat-nombre' : '#cat-descripcion');
          if (campo) mostrarError(campo, error.descripcion);
        });
        mostrarAviso(caja, 'error', textoDeErrorApi(resultado, 'Revisa los campos marcados.'));
        return;
      }
      mostrarAviso(caja, 'error', resultado.mensaje || 'No se pudo crear la categoría.');
    })
    .catch(error => mostrarAviso(caja, 'error', mensajeDeError(error)))
    .finally(() => { boton.disabled = false; });
}