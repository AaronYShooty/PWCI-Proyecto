<?php
$titulo = 'Walter | Portal de cursos en línea';
$activa = 'inicio';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-publica">
  <div class="shell">

    <div class="hero">
      <div class="stripe-corner"><img src="publico/img/Fondo.png" alt=""></div>
      <div class="hero__content">
        <span class="pill" style="background:rgba(255,255,255,0.14); color:white;">Aprende a tu ritmo</span>
        <h1 style="margin-top:14px;">Cursos en línea guiados por instructores reales</h1>
        <p>Explora cursos por niveles, avanza cuando quieras y obtén tu diploma al terminar. Walter te acompaña en cada paso.</p>
        <form class="search-bar" id="form-busqueda" role="search">
          <input type="search" id="busqueda-titulo" placeholder="Buscar cursos por título…" maxlength="150" aria-label="Buscar cursos por título">
          <button type="submit">Buscar</button>
        </form>
      </div>
      <div class="hero__visual">
        <div class="hero__badge">
          <img src="publico/img/Logo_Walter.png" alt="Walter">
          <strong>¡Hola, soy Walter!</strong>
          <span>Tu guía dentro del portal</span>
        </div>
      </div>
    </div>

    <div class="category-row" id="categoria-fila" aria-label="Categorías">
      <button type="button" class="category-chip is-active" data-id="">Todas</button>
    </div>

    <section class="catalogo-bloque">
      <div class="section-heading"><h2>Los mejor calificados</h2></div>
      <div class="course-grid" data-orden="calificacion" aria-live="polite">
        <p class="course-grid__estado">Cargando cursos…</p>
      </div>
    </section>

    <section class="catalogo-bloque">
      <div class="section-heading"><h2>Los más vendidos</h2></div>
      <div class="course-grid" data-orden="ventas" aria-live="polite">
        <p class="course-grid__estado">Cargando cursos…</p>
      </div>
    </section>

    <section class="catalogo-bloque">
      <div class="section-heading"><h2>Los más recientes</h2></div>
      <div class="course-grid" data-orden="recientes" aria-live="polite">
        <p class="course-grid__estado">Cargando cursos…</p>
      </div>
    </section>

    <noscript><p class="course-grid__estado">Activa JavaScript para ver el catálogo de cursos.</p></noscript>

    <p class="footer-note">© 2026 WalterEdu — Portal de cursos en línea · Por Aaron Ernesto Ibarra Villarreal y Sinuhe Martinez Hernandez</p>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>