<?php
$titulo = 'Panel del estudiante | Walter';
$activa = 'estudiante';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-estudiante">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Estudiante</span>
          </div>
        </div>
        <a class="is-active" data-tab="mis-cursos">Mis cursos</a>
        <a data-tab="kardex">Kardex</a>
        <a data-tab="mensajes">Mensajes</a>
        <a data-tab="perfil">Mi perfil</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <div class="dash-main">

        <div class="dash-tab-panel is-active" id="dashtab-mis-cursos">
          <div class="stat-row">
            <div class="stat-card"><span>Cursos inscritos</span><strong>6</strong></div>
            <div class="stat-card"><span>Cursos terminados</span><strong>3</strong></div>
            <div class="stat-card"><span>En progreso</span><strong>3</strong></div>
            <div class="stat-card"><span>Diplomas obtenidos</span><strong>3</strong></div>
          </div>

          <div class="panel">
            <div class="panel__head">
              <h3>Continúa donde lo dejaste</h3>
            </div>
            <div style="display:flex; flex-direction:column; gap:12px;">
              <div class="course-row-card">
                <div class="course-row-card__thumb"></div>
                <div class="course-row-card__body">
                  <h4>MVC y arquitectura en tres capas con PHP</h4>
                  <span style="font-size:0.8rem; color:var(--ink-soft);">Nivel 3 de 6 · Última actividad: hace 2 días</span>
                  <div class="progress-bar"><span style="width:50%;"></span></div>
                </div>
                <a href="index.php?ruta=curso" class="btn btn--ghost btn--sm">Continuar</a>
              </div>
              <div class="course-row-card">
                <div class="course-row-card__thumb"></div>
                <div class="course-row-card__body">
                  <h4>Diseño de interfaces amigables para principiantes</h4>
                  <span style="font-size:0.8rem; color:var(--ink-soft);">Nivel 4 de 5 · Última actividad: hoy</span>
                  <div class="progress-bar"><span style="width:80%;"></span></div>
                </div>
                <a href="index.php?ruta=curso" class="btn btn--ghost btn--sm">Continuar</a>
              </div>
            </div>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-kardex">
          <div class="panel">
            <div class="panel__head">
              <h3>Kardex</h3>
              <div class="filter-row">
                <select><option>Categoría: todas</option></select>
                <select><option>Estado: todos</option></select>
                <input type="date" style="width:150px;">
              </div>
            </div>
            <table>
              <thead>
                <tr><th>Curso</th><th>Categoría</th><th>Inscripción</th><th>Último ingreso</th><th>Avance</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>Fundamentos de PHP orientado a objetos</td>
                  <td>IT &amp; Software</td>
                  <td>03 feb 2026</td>
                  <td>28 ago 2026</td>
                  <td><div class="progress-bar"><span style="width:100%;"></span></div></td>
                  <td><span class="badge badge--ok">Terminado</span></td>
                </tr>
                <tr>
                  <td>MVC y arquitectura en tres capas con PHP</td>
                  <td>IT &amp; Software</td>
                  <td>14 ago 2026</td>
                  <td>13 sep 2026</td>
                  <td><div class="progress-bar"><span style="width:50%;"></span></div></td>
                  <td><span class="badge badge--warn">En curso</span></td>
                </tr>
                <tr>
                  <td>Introducción a APIs REST para no programadores</td>
                  <td>Negocios</td>
                  <td>02 jul 2026</td>
                  <td>10 jul 2026</td>
                  <td><div class="progress-bar"><span style="width:100%;"></span></div></td>
                  <td><span class="badge badge--ok">Terminado</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-mensajes">
          <div class="panel messenger">
            <div class="panel__head"><h3>Mensajes</h3></div>
            <div class="messenger__layout">
              <div class="messenger__list">
                <div class="messenger__item is-active">
                  <div class="dash-nav__avatar" style="width:36px; height:36px; background:linear-gradient(135deg, var(--teal), var(--primary));"></div>
                  <div class="messenger__item-body">
                    <strong>Fausto Islas</strong>
                    <span>¿Ya viste el material del nivel 4?</span>
                  </div>
                  <span class="messenger__time">10:24</span>
                </div>
                <div class="messenger__item">
                  <div class="dash-nav__avatar" style="width:36px; height:36px;"></div>
                  <div class="messenger__item-body">
                    <strong>L. Reyes</strong>
                    <span>Gracias por tu comentario en el curso</span>
                  </div>
                  <span class="messenger__time">Ayer</span>
                </div>
                <div class="messenger__item">
                  <div class="dash-nav__avatar" style="width:36px; height:36px; background:linear-gradient(135deg, var(--sky), var(--cyan));"></div>
                  <div class="messenger__item-body">
                    <strong>M. Cantú</strong>
                    <span>Con gusto, cualquier duda me avisas</span>
                  </div>
                  <span class="messenger__time">Lun</span>
                </div>
              </div>
              <div class="messenger__thread">
                <div class="messenger__thread-head">
                  <div class="dash-nav__avatar" style="width:32px; height:32px; background:linear-gradient(135deg, var(--teal), var(--primary));"></div>
                  <div><strong>Fausto Islas</strong><span class="field-hint">Instructor · MVC y arquitectura en tres capas</span></div>
                </div>
                <div class="messenger__bubble messenger__bubble--in">¿Ya viste el material del nivel 4? Cualquier duda me dices.</div>
                <div class="messenger__bubble messenger__bubble--out">Sí, ya casi lo termino. Tengo una duda sobre el middleware.</div>
                <div class="messenger__bubble messenger__bubble--in">Claro, dime.</div>
                <div class="messenger__thread-input">
                  <input type="text" placeholder="Escribe un mensaje…">
                  <button class="btn btn--primary btn--sm">Enviar</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dash-tab-panel" id="dashtab-perfil">
          <div class="panel" style="max-width:520px;">
            <div class="panel__head"><h3>Mi perfil</h3></div>
            <div class="avatar-upload" style="margin-bottom:18px;">
              <div class="avatar-upload__preview">🖼️</div>
              <div class="avatar-upload__actions">
                <button type="button" class="btn btn--outline btn--sm">Cambiar foto</button>
                <span class="field-hint">Imagen tipo avatar (JPG o PNG).</span>
              </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:14px;">
              <div class="field">
                <label>Nombre completo</label>
                <input type="text" value="Ana Torres">
              </div>
              <div class="field">
                <label>Correo electrónico</label>
                <input type="email" value="ana.torres@correo.com">
              </div>
              <div style="display:flex; gap:14px;">
                <div class="field" style="flex:1;">
                  <label>Género</label>
                  <select><option>Femenino</option><option>Masculino</option><option>Otro</option><option>Prefiero no decir</option></select>
                </div>
                <div class="field" style="flex:1;">
                  <label>Fecha de nacimiento</label>
                  <input type="date" value="2003-05-14">
                </div>
              </div>
              <div class="field">
                <label>Nueva contraseña</label>
                <input type="password" placeholder="Dejar en blanco para no cambiar">
              </div>
              <button class="btn btn--primary" style="align-self:flex-start;">Guardar cambios</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>