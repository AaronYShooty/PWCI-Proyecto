<?php
// Valida sesión y rol ANTES de ejecutar la acción del controlador.
class ControlAcceso
{
    // Para la API: responde JSON con 401 / 403.
    public static function exigir(array $roles = []): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['usuario'])) self::rechazar(401, 'Debes iniciar sesión.');
        if ($roles && !in_array($_SESSION['usuario']['rol'] ?? '', $roles, true)) {
            self::rechazar(403, 'No tienes permiso para esta operación.');
        }
    }

    // Para las pantallas: sin sesión manda al login; con rol incorrecto, al inicio.
    public static function exigirVista(array $roles): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $rol = $_SESSION['usuario']['rol'] ?? null;
        if ($rol !== null && in_array($rol, $roles, true)) return;
        header('Location: index.php?ruta=' . ($rol === null ? 'login' : 'inicio'));
        exit;
    }

    private static function rechazar(int $codigo, string $mensaje): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => $mensaje, 'datos' => [], 'errores' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
}