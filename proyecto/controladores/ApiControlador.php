<?php
abstract class ApiControlador extends Controlador
{
    protected function exito(string $mensaje, array $datos = [], int $codigo = 200): void
    {
        $this->json(['exito' => true, 'mensaje' => $mensaje, 'datos' => $datos, 'errores' => []], $codigo);
    }

    protected function fallo(string $mensaje, int $codigo, array $errores = []): void
    {
        $lista = [];
        foreach ($errores as $campo => $descripcion) {
            $lista[] = ['campo' => $campo, 'descripcion' => $descripcion];
        }
        $this->json(['exito' => false, 'mensaje' => $mensaje, 'datos' => [], 'errores' => $lista], $codigo);
    }

    protected function leerJson(): ?array
    {
        $tipo = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($tipo !== 'application/json') {
            $this->fallo('El Content-Type debe ser application/json.', 415);
            return null;
        }

        return $this->leerObjetoJson((string) file_get_contents('php://input'));
    }

    protected function leerObjetoJson(string $contenido): ?array
    {
        $cuerpo = json_decode($contenido);
        if (!$cuerpo instanceof stdClass) {
            $this->fallo('El cuerpo debe ser un objeto JSON válido.', 400);
            return null;
        }
        return (array) $cuerpo;
    }

    protected function textoValido(mixed $valor, int $max): bool
    {
        return is_string($valor) && trim($valor) !== '' && mb_strlen(trim($valor)) <= $max;
    }
}
