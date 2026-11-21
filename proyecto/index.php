<?php
// Punto de entrada y enrutador: toda petición pasa por un controlador.
require_once __DIR__ . '/config/Autoload.php';
session_start();

$ruta = is_string($_GET['ruta'] ?? 'inicio') ? ($_GET['ruta'] ?? 'inicio') : '';

// Conexión perezosa: solo se abre cuando una ruta la necesita.
$pdo = static function (): PDO {
    static $conexion = null;
    return $conexion ??= (new Conexion())->obtener();
};

// Controladores armados con sus dependencias (inyección por constructor).
$curso   = fn() => new CursoControlador(new Curso($pdo()), new Inscripcion($pdo()), new Nivel($pdo()), new Comentario($pdo()), new MediosCurso());
$nivel   = fn() => new NivelControlador(new Curso($pdo()), new Nivel($pdo()), new MediosCurso());
$usuario = fn() => new UsuarioControlador(new Usuario($pdo()));
$pago    = fn() => new PagoControlador(new Curso($pdo()), new Inscripcion($pdo()), new StripeServicio());
$panel   = fn() => new PanelControlador(
    new Usuario($pdo()), new Curso($pdo()), new Categoria($pdo()), new Inscripcion($pdo()),
    new Comentario($pdo()), new Mensaje($pdo()), new Nivel($pdo())
);

$rutas = [
    'inicio'      => fn() => $curso()->inicio(),
    'curso'       => fn() => $curso()->detalle(),
    'crear-curso' => fn() => $curso()->crear(),
    'editar-niveles' => fn() => $nivel()->editar(),
    'login'       => fn() => $usuario()->login(),
    'registro'    => fn() => $usuario()->registro(),
    'salir'       => fn() => $usuario()->salir(),
    'perfil'      => fn() => $usuario()->perfil(),

    'estudiante'  => fn() => $panel()->estudiante(),
    'instructor'  => fn() => $panel()->instructor(),
    'admin'       => fn() => $panel()->administrador(),
    'diploma'     => fn() => $panel()->diploma(),
    'mensaje-enviar'      => fn() => $panel()->mensajeEnviar(),
    'curso-estado'        => fn() => $panel()->cursoEstado(),
    'usuario-estado'      => fn() => $panel()->usuarioEstado(),
    'comentario-eliminar' => fn() => $panel()->comentarioEliminar(),

    'comprar'        => fn() => $pago()->comprar(),
    'pago'           => fn() => $pago()->formulario(),
    'pago-confirmar' => fn() => $pago()->confirmar(),
    'pago-retorno'   => fn() => $pago()->retorno(),
    'stripe-webhook' => fn() => $pago()->webhook(),
];

$middlewares = [
    'curso'       => ['acceso:estudiante,instructor,administrador'],
    'perfil'      => ['acceso:estudiante,instructor,administrador'],
    'crear-curso' => ['acceso:instructor'],
    'editar-niveles' => ['acceso:instructor'],
    'instructor'  => ['acceso:instructor'],
    'curso-estado' => ['acceso:instructor'],
    'estudiante'  => ['acceso:estudiante'],
    'diploma'     => ['acceso:estudiante'],
    'admin'       => ['acceso:administrador'],
    'usuario-estado'      => ['acceso:administrador'],
    'comentario-eliminar' => ['acceso:administrador'],
    'mensaje-enviar'      => ['acceso:estudiante,instructor'],
    'comprar'        => ['acceso:estudiante'],
    'pago'           => ['acceso:estudiante'],
    'pago-confirmar' => ['acceso:estudiante'],
    'pago-retorno'   => ['acceso:estudiante'],
];
// Rutas que responden JSON: el middleware contesta 401/403 en JSON en lugar de redirigir.
$rutasJson = ['pago-confirmar'];

if (!isset($rutas[$ruta])) { http_response_code(404); exit('Página no encontrada.'); }

try {
    Middleware::ejecutar($middlewares[$ruta] ?? [], in_array($ruta, $rutasJson, true));
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
