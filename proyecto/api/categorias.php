<?php
require_once dirname(__DIR__) . '/config/Autoload.php';

Cors::manejar();

(new CategoriaApiControlador(new Conexion()))->atender($_SERVER['REQUEST_METHOD'], $_GET['id'] ?? null);
