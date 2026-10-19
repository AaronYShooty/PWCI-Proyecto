<?php
$titulo = 'Crear curso | Walter';
$activa = 'crear-curso';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-crear-curso">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="background:linear-gradient(135deg, var(--teal), var(--primary));"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Instructor</span>
          </div>
        </div>
        <a>Mis cursos</a>
        <a>Ventas</a>
        <a>Mensajes</a>
        <a class="is-active">Crear curso</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <form class="dash-main" id="form-curso" data-instructor="<?= htmlspecialchars($usuario['nombre']) ?>" novalidate>
        <div class="panel__head" style="margin-bottom:-6px;">
          <h2 style="font-size:1.3rem;">Crear curso</h2>
          <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn--outline btn--sm" data-publicar="0">Guardar borrador</button>
            <button type="submit" class="btn btn--primary btn--sm" data-publicar="1">Publicar curso</button>
          </div>
        </div>

        <div class="form-alert" role="status" aria-live="polite"></div>

        <div class="two-col">
          <div class="panel" style="display:flex; flex-direction:column; gap:14px;">
            <div class="field">
              <label for="cc-titulo">Título del curso</label>
              <input type="text" id="cc-titulo" name="titulo" placeholder="Ej. Fundamentos de PHP orientado a objetos" maxlength="150" required>
            </div>
            <div class="field">
              <label for="cc-descripcion">Descripción</label>
              <textarea id="cc-descripcion" name="descripcion" rows="4" placeholder="¿Qué aprenderá el alumno en este curso?" maxlength="5000" required></textarea>
            </div>
            <div class="field">
              <label id="cc-categorias-etiqueta">Categorías</label>
              <div class="categoria-lista" id="cc-categorias" role="group" aria-labelledby="cc-categorias-etiqueta"></div>
              <span class="field-hint">Puedes elegir más de una categoría.</span>
            </div>
            <div style="display:flex; gap:14px;">
              <div class="field" style="flex:1;">
                <label for="cc-nivel">Nivel del catálogo</label>
                <select id="cc-nivel" name="nivel" required>
                  <option value="">Selecciona…</option>
                  <option value="1">Nivel 1</option>
                  <option value="2">Nivel 2</option>
                  <option value="3">Nivel 3</option>
                  <option value="4">Nivel 4</option>
                  <option value="5">Nivel 5</option>
                </select>
                <span class="field-hint">El nivel al que pertenece el curso.</span>
              </div>
              <div class="field" style="flex:1;">
                <label for="cc-precio">Precio del curso completo</label>
                <input type="text" id="cc-precio" name="precio" inputmode="decimal" placeholder="0.00" required>
                <span class="field-hint">Escribe 0 si el curso es gratis.</span>
              </div>
            </div>
            <div class="field">
              <label for="cc-imagen">Imagen del curso</label>
              <input type="text" id="cc-imagen" name="imagen" placeholder="publico/img/mi-curso.png o https://…" maxlength="255">
              <span class="field-hint">Opcional. Ruta o enlace de la imagen; la carga de archivos llegará más adelante.</span>
            </div>

            <div class="panel__head" style="margin-top:10px;">
              <h3 style="font-size:0.98rem;">Niveles del curso</h3>
              <button type="button" class="btn btn--ghost btn--sm" disabled>+ Agregar nivel</button>
            </div>
            <p class="field-hint">Próximamente: aquí se cargarán los videos y archivos de cada nivel, y el instructor decidirá cuáles son gratuitos.</p>

            <div class="level-editor level-editor--pendiente">
              <div class="level-editor__head">
                <strong>Nivel 1</strong>
                <label class="field-hint"><input type="checkbox" style="margin-right:5px;" disabled>Gratis</label>
              </div>
              <input type="text" placeholder="Título del nivel" disabled>
              <div style="display:flex; gap:10px;">
                <input type="text" placeholder="Video (obligatorio)" style="flex:1;" disabled>
                <input type="text" placeholder="Archivo PDF (opcional)" style="flex:1;" disabled>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel__head"><h3 style="font-size:0.98rem;">Vista previa</h3></div>
            <div id="vista-previa"></div>
            <p class="field-hint" style="margin-top:12px;">Así se verá tu curso en el catálogo.</p>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/_pie.php'; ?>