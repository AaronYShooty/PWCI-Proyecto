<?php
require_once dirname(__DIR__) . '/config/Autoload.php';

session_start();

$middlewares = ['cors'];
Middleware::ejecutar($middlewares, true);

(new SesionApiControlador(new Conexion()))->atender($_SERVER['REQUEST_METHOD'], $_GET['id'] ?? null);