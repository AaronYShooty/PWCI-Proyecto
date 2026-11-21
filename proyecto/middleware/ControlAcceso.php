<?php
// Valida sesión y rol ANTES de ejecutar la acción del controlador.
class ControlAcceso
{
    // Para la API: responde JSON con 401 / 403.
    public static function exigir(array $roles = []): void
    {
        if (!self::sesionVigente(true)) self::rechazar(401, 'Debes iniciar sesión con una cuenta activa.');
        if ($roles && !in_array($_SESSION['usuario']['rol'], $roles, true)) {
            self::rechazar(403, 'No tienes permiso para esta operación.');
        }
    }

    // Para las pantallas: sin sesión manda al login; con rol incorrecto, al inicio.
    public static function exigirVista(array $roles): void
    {
        $rol = self::sesionVigente(false) ? $_SESSION['usuario']['rol'] : null;
        if ($rol !== null && in_array($rol, $roles, true)) return;
        header('Location: index.php?ruta=' . ($rol === null ? 'login' : 'inicio'));
        exit;
    }

    private static function sesionVigente(bool $api): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['usuario']['id'])) return false;
        try {
            $actual = (new Usuario((new Conexion())->obtener()))->buscarPorId((int) $_SESSION['usuario']['id']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            if ($api) self::rechazar(503, 'El servicio de cuentas no está disponible.');
            http_response_code(503);
            exit('El servicio de cuentas no está disponible.');
        }
        if (!$actual || !$actual['activo']) {
            $_SESSION = [];
            session_destroy();
            return false;
        }
        $_SESSION['usuario'] = [
            'id' => (int) $actual['id'], 'nombre' => $actual['nombre_completo'],
            'rol' => $actual['rol'], 'foto' => $actual['foto'],
        ];
        return true;
    }

    private static function rechazar(int $codigo, string $mensaje): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => $mensaje, 'datos' => [], 'errores' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
