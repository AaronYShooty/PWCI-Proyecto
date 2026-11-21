<?php
// CORS explícito: solo permite los orígenes listados en CORS_ORIGENES (.env).
class Cors
{
    public static function manejar(): void
    {
        $permitidos = array_map('trim', explode(',', Entorno::obtener('CORS_ORIGENES')));
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
            header('Access-Control-Allow-Methods: GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, Range');
            header('Access-Control-Expose-Headers: Accept-Ranges, Content-Range, Content-Disposition');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
    }
}
