<?php
$titulo = 'Panel del administrador | Walter';
$activa = 'admin';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-admin">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="background:linear-gradient(135deg, var(--ink), var(--primary));"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Cuenta admin</span>
          </div>
        </div>
        <a class="is-active" data-tab="usuarios">Usuarios</a>
        <a data-tab="categorias">Categorías</a>
        <a data-tab="comentarios">Comentarios</a>
        <a data-tab="reportes">Reportes</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <div class="dash-main">

        <div class="dash-tab-panel is-active" id="dashtab-usuarios">
          <div class="stat-row">
            <div class="stat-card"><span>Usuarios activos</span><strong>1,204</strong></div>
            <div class="stat-card"><span>Instructores</span><strong>48</strong></div>
            <div class="stat-card"><span>Categorías</span><strong>9</strong></div>
            <div class="stat-card"><span>Cuentas bloqueadas</span><strong>7</strong></div>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Gestión de usuarios</h3>
              <div class="filter-row">
                <select><option>Tipo: todos</option><option>Instructor</option><option>Estudiante</option></select>
                <input type="text" placeholder="Buscar usuario…">
              </div>
            </div>
            <table>
              <thead>
                <tr><th>Usuario</th><th>Nombre</th><th>Tipo</th><th>Fecha ingreso</th><th>Estado</th><th></th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>fislas</td><td>Fausto Islas</td><td>Instructor</td><td>02 ene 2025</td>
                  <td><span class="badge badge--ok">Activo</span></td>
                  <td><button class="btn btn--outline btn--sm">Bloquear</button></td>
                </tr>
                <tr>
                  <td>atorres</td><td>Ana Torres</td><td>Estudiante</td><td>18 mar 2025</td>
                  <td><span class="badge badge--ok">Activo</span></td>
                  <td><button class="btn btn--outline btn--sm">Bloquear</button></td>
                </tr>
                <tr>
                  <td>jperez</td><td>Jorge Pérez</td><td>Estudiante</td><td>09 nov 2024</td>
                  <td><span class="badge badge--off">Bloqueado</span></td>
                  <td><button class="btn btn--primary btn--sm">Desbloquear</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-categorias">
          <div class="two-col">
            <div class="panel">
              <div class="panel__head"><h3>Categorías</h3></div>
              <table>
                <thead><tr><th>Categoría</th><th>Cursos</th><th>Creada por</th><th>Fecha</th></tr></thead>
                <tbody id="categorias-cuerpo"><tr><td colspan="4">Cargando categorías…</td></tr></tbody>
              </table>
            </div>
            <div class="panel">
              <div class="panel__head"><h3 style="font-size:0.98rem;">Nueva categoría</h3></div>
              <form id="form-categoria" style="display:flex; flex-direction:column; gap:12px;" novalidate>
                <div class="form-alert" role="status" aria-live="polite"></div>
                <div class="field">
                  <label for="cat-nombre">Nombre</label>
                  <input type="text" id="cat-nombre" name="nombre" placeholder="Ej. Fotografía" maxlength="80" required>
                </div>
                <div class="field">
                  <label for="cat-descripcion">Descripción</label>
                  <textarea id="cat-descripcion" name="descripcion" rows="3" placeholder="¿Qué tipo de cursos contendrá?" maxlength="1000"></textarea>
                </div>
                <button type="submit" class="btn btn--primary btn--sm" style="align-self:flex-start;">+ Agregar categoría</button>
              </form>
            </div>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-comentarios">
          <div class="panel">
            <div class="panel__head"><h3>Comentarios reportados</h3></div>
            <div class="comment-row">
              <div class="dash-nav__avatar" style="width:34px; height:34px; background:linear-gradient(135deg, var(--sky), var(--cyan));"></div>
              <div class="comment-row__body">
                <div class="comment-row__head"><strong>Jorge Pérez</strong><span class="comment-row__date">15 ago 2026 · MVC y arquitectura en tres capas</span></div>
                <p>"Este curso es una pérdida de tiempo, el instructor no sabe explicar…"</p>
                <div style="display:flex; gap:8px; margin-top:10px;">
                  <select style="font-size:0.78rem;"><option>Causa: lenguaje ofensivo</option><option>Causa: spam</option><option>Causa: contenido irrelevante</option></select>
                  <button class="btn btn--danger btn--sm">Eliminar comentario</button>
                  <button class="btn btn--outline btn--sm">Descartar reporte</button>
                </div>
              </div>
            </div>
            <div class="comment-row">
              <div class="dash-nav__avatar" style="width:34px; height:34px;"></div>
              <div class="comment-row__body">
                <div class="comment-row__head"><strong>Ana Torres</strong><span class="comment-row__date">10 ago 2026 · Fundamentos de PHP orientado a objetos</span></div>
                <p>"Contenido publicitario no relacionado con el curso."</p>
                <div style="display:flex; gap:8px; margin-top:10px;">
                  <select style="font-size:0.78rem;"><option>Causa: spam</option><option>Causa: lenguaje ofensivo</option><option>Causa: contenido irrelevante</option></select>
                  <button class="btn btn--danger btn--sm">Eliminar comentario</button>
                  <button class="btn btn--outline btn--sm">Descartar reporte</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-reportes">
          <div class="stat-row">
            <div class="stat-card"><span>Cursos activos</span><strong>38</strong></div>
            <div class="stat-card"><span>Inscripciones</span><strong>1,946</strong></div>
            <div class="stat-card"><span>Cursos terminados</span><strong>812</strong></div>
            <div class="stat-card"><span>Ingresos de la plataforma</span><strong>$64,310</strong></div>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Actividad por curso</h3>
              <div class="filter-row">
                <select><option>Categoría: todas</option><option>Programación</option><option>Bases de datos</option><option>Desarrollo web</option></select>
                <select><option>Últimos 30 días</option><option>Últimos 90 días</option><option>Este año</option></select>
              </div>
            </div>
            <table>
              <thead>
                <tr><th>Curso</th><th>Instructor</th><th>Categoría</th><th>Inscritos</th><th>Terminaron</th><th>Calificación</th><th>Ingresos</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>MySQL: relaciones, llaves e integridad referencial</td><td>Fausto Islas</td><td>Bases de datos</td><td>150</td>
                  <td>72%<div class="progress-bar" style="margin-top:4px;"><span style="width:72%"></span></div></td>
                  <td>4.8 ★</td><td>$5,980.00</td><td><span class="badge badge--ok">Activo</span></td>
                </tr>
                <tr>
                  <td>MVC y arquitectura en tres capas con PHP</td><td>Fausto Islas</td><td>Programación</td><td>96</td>
                  <td>43%<div class="progress-bar" style="margin-top:4px;"><span style="width:43%"></span></div></td>
                  <td>4.6 ★</td><td>$8,940.00</td><td><span class="badge badge--ok">Activo</span></td>
                </tr>
                <tr>
                  <td>Fundamentos de PHP orientado a objetos</td><td>L. Reyes</td><td>Programación</td><td>88</td>
                  <td>63%<div class="progress-bar" style="margin-top:4px;"><span style="width:63%"></span></div></td>
                  <td>4.7 ★</td><td>$7,150.00</td><td><span class="badge badge--ok">Activo</span></td>
                </tr>
                <tr>
                  <td>Introducción a APIs REST para no programadores</td><td>M. Cantú</td><td>Desarrollo web</td><td>12</td>
                  <td>66%<div class="progress-bar" style="margin-top:4px;"><span style="width:66%"></span></div></td>
                  <td>4.1 ★</td><td>$1,240.00</td><td><span class="badge badge--warn">Poco movimiento</span></td>
                </tr>
                <tr>
                  <td>HTML y CSS desde cero</td><td>L. Reyes</td><td>Desarrollo web</td><td>0</td>
                  <td>0%<div class="progress-bar" style="margin-top:4px;"><span style="width:0%"></span></div></td>
                  <td>Sin calificar</td><td>$0.00</td><td><span class="badge badge--off">Inactivo</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Instructores con más ingresos</h3>
            </div>
            <table>
              <thead>
                <tr><th>Usuario</th><th>Nombre</th><th>Fecha ingreso</th><th>Cursos ofrecidos</th><th>Total de ganancias</th></tr>
              </thead>
              <tbody>
                <tr><td>fislas</td><td>Fausto Islas</td><td>02 ene 2025</td><td>5</td><td>$18,420.00</td></tr>
                <tr><td>lreyes</td><td>L. Reyes</td><td>14 ago 2024</td><td>3</td><td>$9,150.00</td></tr>
                <tr><td>mcantu</td><td>M. Cantú</td><td>30 may 2025</td><td>2</td><td>$4,980.00</td></tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>