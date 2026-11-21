<?php
class CategoriaApiControlador extends ApiControlador
{
    private ?Categoria $categorias = null;

    public function __construct(private Conexion $conexion) {}

    public function atender(string $metodo, ?string $id): void
    {
        try {
            $numero = $id === null ? null : filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $categoria = $numero ? $this->categorias()->obtenerPorId($numero) : null;
            if ($id !== null && !$categoria) {
                $this->fallo('Categoría no encontrada.', 404);
                return;
            }
            if ($metodo === 'GET') {
                $this->exito('Consulta realizada correctamente', $categoria ?? $this->categorias()->listar());
                return;
            }
            if (($metodo === 'POST' && $numero === null) || ($metodo === 'PUT' && $numero)) {
                $this->guardar($numero);
                return;
            }
            if ($metodo === 'DELETE' && $numero) {
                if ($this->categorias()->eliminar($numero)) {
                    $this->exito('Categoría eliminada correctamente.', ['id_categoria' => $numero]);
                } else {
                    $this->fallo('Categoría no encontrada.', 404);
                }
                return;
            }
            header($id === null ? 'Allow: GET, POST' : 'Allow: GET, PUT, DELETE');
            $this->fallo('Método no permitido.', 405);
        } catch (DomainException $e) {
            $this->fallo($e->getMessage(), 409);
        } catch (Throwable $e) {
            if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                $this->fallo('Ya existe una categoría con ese nombre.', 409);
                return;
            }
            error_log($e->getMessage());
            $this->fallo('Error interno del servidor.', 500);
        }
    }

    private function guardar(?int $id): void
    {
        $cuerpo = $this->leerJson();
        if ($cuerpo === null) return;

        $errores = [];
        $nombre = $cuerpo['nombre'] ?? null;
        if (!$this->textoValido($nombre, 80)) {
            $errores['nombre'] = 'Obligatorio: texto de 1 a 80 caracteres.';
        } elseif ($this->categorias()->existeNombre(trim($nombre), $id)) {
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
        if ($id === null) {
            $id = $this->categorias()->crear(trim($nombre), $descripcion, (int) $_SESSION['usuario']['id']);
            $this->exito('Categoría creada correctamente.', $this->categorias()->obtenerPorId($id), 201);
        } else {
            $this->categorias()->actualizar($id, trim($nombre), $descripcion);
            $this->exito('Categoría actualizada correctamente.', $this->categorias()->obtenerPorId($id));
        }
    }

    private function categorias(): Categoria
    {
        return $this->categorias ??= new Categoria($this->conexion->obtener());
    }
}
