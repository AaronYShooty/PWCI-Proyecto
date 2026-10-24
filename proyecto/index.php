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
    'curso'       => fn() => (new CursoControlador(new Curso($pdo()), new Inscripcion($pdo())))->detalle(),
    'crear-curso' => fn() => (new CursoControlador(new Curso($pdo())))->crear(),
    'diploma'     => fn() => (new CursoControlador(new Curso($pdo())))->diploma(),
    'login'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->login(),
    'registro'    => fn() => (new UsuarioControlador(new Usuario($pdo())))->registro(),
    'estudiante'  => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelEstudiante(),
    'instructor'  => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelInstructor(),
    'admin'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->panelAdministrador(),
    'salir'       => fn() => (new UsuarioControlador(new Usuario($pdo())))->salir(),
    'perfil'      => fn() => (new UsuarioControlador(new Usuario($pdo())))->perfil(),
    'comprar'        => fn() => (new PagoControlador(new Curso($pdo()), new Inscripcion($pdo()), new StripeServicio()))->iniciar(),
    'pago-exito'     => fn() => (new PagoControlador(new Curso($pdo()), new Inscripcion($pdo()), new StripeServicio()))->exito(),
    'pago-cancelado' => fn() => (new PagoControlador(new Curso($pdo()), new Inscripcion($pdo()), new StripeServicio()))->cancelado(),
];

$middlewares = [
    'curso'       => ['acceso:estudiante,instructor,administrador'],
    'diploma'     => ['acceso:estudiante'],
    'estudiante'  => ['acceso:estudiante'],
    'crear-curso' => ['acceso:instructor'],
    'instructor'  => ['acceso:instructor'],
    'admin'       => ['acceso:administrador'],
    'perfil'      => ['acceso:estudiante,instructor,administrador'],
    'comprar'        => ['acceso:estudiante'],
    'pago-exito'     => ['acceso:estudiante'],
    'pago-cancelado' => ['acceso:estudiante'],
];

if (!isset($rutas[$ruta])) { http_response_code(404); exit('Página no encontrada.'); }

try {
    Middleware::ejecutar($middlewares[$ruta] ?? []);
    $rutas[$ruta]();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    if (Entorno::obtener('APP_DEBUG') === '1') {
        $causa = $e->getPrevious();
        exit('<pre>' . htmlspecialchars(
            get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
            . ($causa ? "\nCausa: " . $causa->getMessage() : '')
        ) . '</pre>');
    }
    exit('Ocurrió un error en el servidor. Revisa que MySQL esté encendido y la base de datos importada.');
}