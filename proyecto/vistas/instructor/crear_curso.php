<?php
$editando = $curso !== null;
$etiqueta = $editando ? 'Editar curso' : 'Crear curso';
$titulo = $etiqueta . ' | Walter';
$activa = 'crear-curso';
require dirname(__DIR__) . '/_encabezado.php';
?>

<section class="window is-active" id="win-crear-curso">
  <div class="shell">
    <div class="dash">
      <aside class="dash-nav">
        <div class="dash-nav__user">
          <div class="dash-nav__avatar" style="<?= Formato::avatar($usuario['foto'] ?? null, 'background:linear-gradient(135deg, var(--teal), var(--primary));') ?>"></div>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <span>Instructor</span>
          </div>
        </div>
        <a href="index.php?ruta=instructor&amp;tab=mis-cursos">Mis cursos</a>
        <a href="index.php?ruta=instructor&amp;tab=ventas">Ventas</a>
        <a href="index.php?ruta=instructor&amp;tab=mensajes">Mensajes</a>
        <a class="is-active" href="index.php?ruta=crear-curso">Crear curso</a>
        <a href="index.php?ruta=salir">Cerrar sesión</a>
      </aside>

      <form class="dash-main" id="form-curso" data-instructor="<?= htmlspecialchars($usuario['nombre']) ?>"
            data-curso="<?= (int) ($curso['id_curso'] ?? 0) ?>" data-categorias="<?= implode(',', $curso['ids_categorias'] ?? []) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ??= bin2hex(random_bytes(32))) ?>">
        <div class="panel__head" style="margin-bottom:-6px;">
          <h2 style="font-size:1.3rem;"><?= $etiqueta ?></h2>
          <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn--primary btn--sm"><?= $editando ? 'Guardar cambios' : 'Guardar y agregar niveles' ?></button>
          </div>
        </div>

        <div class="form-alert" role="status" aria-live="polite"></div>

        <div class="two-col">
          <div class="panel" style="display:flex; flex-direction:column; gap:14px;">
            <div class="field">
              <label for="cc-titulo">Título del curso</label>
              <input type="text" id="cc-titulo" name="titulo" value="<?= htmlspecialchars($curso['titulo'] ?? '') ?>" placeholder="Ej. Fundamentos de PHP orientado a objetos" maxlength="150" required>
            </div>
            <div class="field">
              <label for="cc-descripcion">Descripción</label>
              <textarea id="cc-descripcion" name="descripcion" rows="4" placeholder="¿Qué aprenderá el alumno en este curso?" maxlength="5000" required><?= htmlspecialchars($curso['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label id="cc-categorias-etiqueta">Categorías</label>
              <details class="categoria-desplegable" id="cc-categorias">
                <summary id="cc-categorias-resumen">Selecciona categorías</summary>
                <input type="search" id="cc-categorias-busqueda" placeholder="Buscar categoría" aria-label="Buscar categoría">
                <div class="categoria-lista" id="cc-categorias-opciones" role="group" aria-labelledby="cc-categorias-etiqueta"></div>
              </details>
              <span class="field-hint">Puedes elegir más de una categoría.</span>
            </div>
            <div style="display:flex; gap:14px;">
              <div class="field" style="flex:1;">
                <label for="cc-nivel">Nivel del catálogo</label>
                <select id="cc-nivel" name="nivel" required>
                  <option value="">Selecciona…</option>
                  <?php for ($n = 1; $n <= 5; ++$n): ?>
                    <option value="<?= $n ?>" <?= ($curso['nivel'] ?? null) === $n ? 'selected' : '' ?>>Nivel <?= $n ?></option>
                  <?php endfor; ?>
                </select>
                <span class="field-hint">El nivel al que pertenece el curso.</span>
              </div>
              <div class="field" style="flex:1;">
                <label for="cc-precio">Precio del curso completo</label>
                <input type="text" id="cc-precio" name="precio" value="<?= $editando ? number_format($curso['precio'], 2, '.', '') : '' ?>" inputmode="decimal" placeholder="0.00" required>
                <span class="field-hint">Escribe 0 si es gratis. Para aceptar OXXO, el precio debe ser de $10.00 a $10,000.00 MXN.</span>
              </div>
            </div>
            <div class="field">
              <label for="cc-portada">Banner del curso</label>
              <input type="file" id="cc-portada" name="portada" accept="image/jpeg,image/png,image/webp" aria-describedby="cc-formato-portada">
              <span class="field-hint" id="cc-formato-portada">JPG, PNG o WebP. Hasta 5 MB.</span>
              <button class="btn btn--ghost btn--sm" id="cc-quitar-portada" type="button" hidden>Quitar banner</button>
            </div>
            <details class="categoria-desplegable">
              <summary>Usar un enlace de imagen</summary>
              <div class="field">
                <label for="cc-imagen">Ruta o enlace</label>
                <input type="text" id="cc-imagen" name="imagen" value="<?= htmlspecialchars($curso['imagen'] ?? '') ?>" placeholder="https://…" maxlength="255">
              </div>
            </details>

            <p class="field-hint">Agrega los niveles con sus videos antes de publicar.</p>
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
