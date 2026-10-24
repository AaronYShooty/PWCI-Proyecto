<?php
// Variables que define cada vista antes de incluir este archivo.
$titulo = $titulo ?? 'Walter';
$activa = $activa ?? '';
$usuario = $_SESSION['usuario'] ?? null;
$rol = $usuario['rol'] ?? 'visitante';
$menus = [
  'visitante'     => ['inicio' => 'Inicio', 'login' => 'Iniciar sesión', 'registro' => 'Registro'],
  'estudiante'    => ['inicio' => 'Inicio', 'estudiante' => 'Mi panel', 'curso' => 'Detalle de curso', 'diploma' => 'Diploma', 'perfil' => 'Mi perfil'],
  'instructor'    => ['inicio' => 'Inicio', 'instructor' => 'Mi panel', 'crear-curso' => 'Crear curso', 'perfil' => 'Mi perfil'],
  'administrador' => ['inicio' => 'Inicio', 'admin' => 'Panel de administración', 'perfil' => 'Mi perfil'],
];
$panelDe = ['estudiante' => 'estudiante', 'instructor' => 'instructor', 'administrador' => 'admin'];
$etiquetaRol = ['estudiante' => 'Estudiante', 'instructor' => 'Instructor', 'administrador' => 'Administrador'];
$avatarEstilo = [
  'instructor'    => 'background:linear-gradient(135deg, var(--teal), var(--primary));',
  'administrador' => 'background:linear-gradient(135deg, var(--ink), var(--primary));',
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?></title>
<link rel="icon" href="publico/img/Walter.ico">
<link rel="stylesheet" href="publico/css/style.css">
</head>
<body>

<header class="app-topbar">
  <div class="app-topbar__row">
    <a href="index.php?ruta=inicio" class="app-topbar__brand">
      <img src="publico/img/Logo_Walter.png" alt="Logo de Walter">
      Walter
    </a>
    <div class="app-topbar__auth">
<?php if ($usuario): ?>
      <a class="topbar-user" href="index.php?ruta=<?= $panelDe[$rol] ?>" style="text-decoration:none; color:inherit;">
        <div class="topbar-user__avatar"<?= isset($avatarEstilo[$rol]) ? ' style="' . $avatarEstilo[$rol] . '"' : '' ?>></div>
        <div class="topbar-user__info">
          <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
          <span><?= $etiquetaRol[$rol] ?></span>
        </div>
      </a>
      <a href="index.php?ruta=salir" class="btn btn--outline btn--sm">Cerrar sesión</a>
<?php else: ?>
      <a href="index.php?ruta=login" class="btn btn--outline btn--sm">Iniciar sesión</a>
      <a href="index.php?ruta=registro" class="btn btn--primary btn--sm">Registrarse</a>
<?php endif; ?>
    </div>
  </div>
  <nav class="window-switcher">
<?php foreach ($menus[$rol] as $clave => $texto): ?>
    <a href="index.php?ruta=<?= $clave ?>"<?= $clave === $activa ? ' class="is-active"' : '' ?>><?= $texto ?></a>
<?php endforeach; ?>
  </nav>
</header>