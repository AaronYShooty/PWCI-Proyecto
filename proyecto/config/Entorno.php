<?php
// Lectura de valores del archivo .env (credenciales fuera del código versionado).
class Entorno
{
    private static ?array $valores = null;

    public static function obtener(string $clave, string $defecto = ''): string
    {
        if (self::$valores === null) {
            self::$valores = [];
            $ruta = dirname(__DIR__) . '/.env';
            if (is_file($ruta)) {
                foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                    $linea = trim($linea);
                    if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
                    [$k, $v] = explode('=', $linea, 2);
                    self::$valores[trim($k)] = trim($v);
                }
            }
        }
        return self::$valores[$clave] ?? $defecto;
    }
}