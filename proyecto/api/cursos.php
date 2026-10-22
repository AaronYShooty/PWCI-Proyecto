<?php
require_once dirname(__DIR__) . '/config/Autoload.php';

// Middlewares en orden: CORS siempre; consultar es público, lo demás exige sesión y rol.
$middlewares = ['cors'];
if ($_SERVER['REQUEST_METHOD'] !== 'GET') $middlewares[] = 'acceso:instructor,administrador';
Middleware::ejecutar($middlewares, true);

(new CursoApiControlador(new Conexion()))->atender($_SERVER['REQUEST_METHOD'], $_GET['id'] ?? null);
