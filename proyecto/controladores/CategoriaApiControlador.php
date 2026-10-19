<?php
class CategoriaApiControlador extends ApiControlador
{
    private ?Categoria $categorias = null;

    public function __construct(private Conexion $conexion) {}

    public function atender(string $metodo, ?string $id): void
    {
        try {
            if ($id !== null) {
                $this->fallo('Categoría no encontrada.', 404);
                return;
            }
            if ($metodo === 'GET') {
                $this->exito('Consulta realizada correctamente', $this->categorias()->listar());
                return;
            }
            if ($metodo === 'POST') {
                $this->crear();
                return;
            }
            header('Allow: GET, POST');
            $this->fallo('Método no permitido.', 405);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fallo('Error interno del servidor.', 500);
        }
    }

    private function crear(): void
    {
        ControlAcceso::exigir(['administrador']);

        $cuerpo = $this->leerJson();
        if ($cuerpo === null) return;

        $errores = [];
        $nombre = $cuerpo['nombre'] ?? null;
        if (!$this->textoValido($nombre, 80)) {
            $errores['nombre'] = 'Obligatorio: texto de 1 a 80 caracteres.';
        } elseif ($this->categorias()->existeNombre(trim($nombre))) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }

        $descripcion = $cuerpo['descripcion'] ?? null;
        if ($descripcion !== null && (!is_string($descripcion) || mb_strlen($descripcion) > 1000)) {
            $errores['descripcion'] = 'Debe ser texto de hasta 1000 caracteres.';
        }

        if ($errores) {
            $this->fallo('Los datos enviados no son válidos.', 400, $errores);
            return;
        }

        $descripcion = $descripcion === null || trim($descripcion) === '' ? null : trim($descripcion);
        $id = $this->categorias()->crear(trim($nombre), $descripcion, (int) $_SESSION['usuario']['id']);

        $this->exito('Categoría creada correctamente', $this->categorias()->obtenerPorId($id), 201);
    }

    private function categorias(): Categoria
    {
        return $this->categorias ??= new Categoria($this->conexion->obtener());
    }
}
