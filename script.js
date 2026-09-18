// Cada ventana del prototipo vive en su propio archivo HTML (index.html, auth.html,
// curso.html, crear-curso.html, estudiante.html, instructor.html, admin.html,
// diploma.html) y se navega entre ellas con enlaces normales. Este script solo
// controla las interacciones dentro de una misma ventana (tabs, chips), sin
// lógica de negocio real, ya que este proyecto cubre únicamente el diseño.

document.addEventListener('DOMContentLoaded', () => {
  // Tabs del panel lateral en Estudiante / Instructor / Administrador
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

  // Conversaciones de ejemplo en la ventana de Mensajes (solo resalta visualmente)
  document.querySelectorAll('.messenger__item').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.messenger__item').forEach(i => i.classList.remove('is-active'));
      item.classList.add('is-active');
    });
  });

  // Tabs de la ventana de detalle de curso (Contenido / Descripción / Comentarios)
  const courseTabs = document.querySelectorAll('#courseTabs button');
  courseTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      courseTabs.forEach(t => t.classList.remove('is-active'));
      tab.classList.add('is-active');
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('is-active'));
      document.getElementById('tab-' + tab.dataset.tab).classList.add('is-active');
    });
  });

  // Chips de categoría en la ventana pública (solo resalta visualmente)
  document.querySelectorAll('.category-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
    });
  });
});
