<?php
require_once dirname(__DIR__) . '/config/Autoload.php';

try {
    $recurso = is_string($_GET['recurso'] ?? null) ? $_GET['recurso'] : '';
    $roles = in_array($recurso, ['avance', 'resena'], true) ? 'estudiante' : 'estudiante,instructor,administrador';
    Middleware::ejecutar(['cors', 'acceso:' . $roles], true);
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
    $pdo = (new Conexion())->obtener();
    (new AprendizajeApiControlador(new Nivel($pdo), new Inscripcion($pdo), new Comentario($pdo), new Curso($pdo), new MediosCurso()))
        ->atender($recurso, $id ?: 0, $_SERVER['REQUEST_METHOD']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => 'Error interno del servidor.', 'datos' => [], 'errores' => []]);
    }
}
