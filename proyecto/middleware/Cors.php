<?php
// CORS explícito: solo permite los orígenes listados en CORS_ORIGENES (.env).
class Cors
{
    public static function manejar(): void
    {
        $permitidos = [];
        $env = dirname(__DIR__) . '/.env';
        if (is_file($env) && preg_match('/^CORS_ORIGENES=(.*)$/m', file_get_contents($env), $m)) {
            $permitidos = array_map('trim', explode(',', $m[1]));
        }
        $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origen !== '' && !in_array($origen, $permitidos, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'Origen no autorizado.', 'datos' => [], 'errores' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($origen !== '') {
            header("Access-Control-Allow-Origin: $origen");
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
    }
}
