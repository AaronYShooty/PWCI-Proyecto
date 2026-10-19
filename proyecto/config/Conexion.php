<?php
class Conexion
{
    private ?PDO $pdo = null;

    private function cargarEnv(): array
    {
        $ruta = dirname(__DIR__) . '/.env';
        $datos = [];
        if (is_file($ruta)) {
            foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                if (str_starts_with(trim($linea), '#') || !str_contains($linea, '=')) continue;
                [$k, $v] = explode('=', $linea, 2);
                $datos[trim($k)] = trim($v);
            }
        }
        return $datos;
    }

    public function obtener(): PDO
    {
        if ($this->pdo === null) {
            $e = $this->cargarEnv();
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $e['DB_HOST'] ?? 'localhost', $e['DB_PUERTO'] ?? '3306', $e['DB_NOMBRE'] ?? 'walteredu');
            try {
                $this->pdo = new PDO($dsn, $e['DB_USUARIO'] ?? 'root', $e['DB_CLAVE'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                throw new RuntimeException('No fue posible conectar con la base de datos.');
            }
        }
        return $this->pdo;
    }
}