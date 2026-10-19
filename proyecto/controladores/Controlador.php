<?php
// Base común: renderiza vistas y responde JSON con código de estado.
abstract class Controlador
{
    protected function vista(string $ruta, array $datos = []): void
    {
        extract($datos);
        require dirname(__DIR__) . "/vistas/$ruta.php";
    }

    protected function redirigir(string $ruta): never
    {
        header('Location: index.php?ruta=' . $ruta);
        exit;
    }

    protected function json(array $cuerpo, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }
}
