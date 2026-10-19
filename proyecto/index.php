<?php
// Punto de entrada y enrutador: toda petición pasa por un controlador.
require_once __DIR__ . '/config/Autoload.php';
session_start();

$ruta = $_GET['ruta'] ?? 'inicio';

// Conexión perezosa: solo se abre cuando una ruta la necesita.
$pdo = static function (): PDO {
    static $conexion = null;
    return $conexion ??= (new Conexion())->obtener();
};

$rutas = [
    'inicio'      => fn() => (new CursoControlador(new Curso($pdo())))->inicio(),
    'curso'       => fn() => (new CursoControlador(new Curso($pdo())))->detalle(),
    'crear-curso' => fn() => (new CursoControlador(new Curso($pdo())))->crear(),
    'diploma'     => fn() => (new CursoControlador(new Curso($pdo())))->diploma(),
    'login'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->login(),
    'registro'    => fn() => (new UsuarioControlador(new Usuario($pdo())))->registro(),
    'estudiante'  => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelEstudiante(),
    'instructor'  => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelInstructor(),
    'admin'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelAdministrador(),
    'salir'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->salir(),
];

// Rol(es) que pueden entrar a cada pantalla protegida (middleware ControlAcceso).
$protegidas = [
    'curso'       => ['estudiante', 'instructor', 'administrador'],
    'diploma'     => ['estudiante'],
    'estudiante'  => ['estudiante'],
    'crear-curso' => ['instructor'],
    'instructor'  => ['instructor'],
    'admin'       => ['administrador'],
];

if (!isset($rutas[$ruta])) { http_response_code(404); exit('Página no encontrada.'); }

try {
    if (isset($protegidas[$ruta])) ControlAcceso::exigirVista($protegidas[$ruta]);
    $rutas[$ruta]();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error en el servidor. Revisa que MySQL esté encendido y la base de datos importada.');
}