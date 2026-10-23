<?php
require_once dirname(__DIR__) . '/config/Autoload.php';

$middlewares = ['cors'];
if ($_SERVER['REQUEST_METHOD'] !== 'GET') $middlewares[] = 'acceso:administrador';
Middleware::ejecutar($middlewares, true);

(new CategoriaApiControlador(new Conexion()))->atender($_SERVER['REQUEST_METHOD'], $_GET['id'] ?? null);