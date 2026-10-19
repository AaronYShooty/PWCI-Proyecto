<?php
class Categoria
{
    private const CONSULTA = 'SELECT g.id AS id_categoria, g.nombre, g.descripcion, u.nombre_completo AS creada_por,
        g.fecha_creacion,
        (SELECT COUNT(*) FROM curso_categoria cc JOIN cursos c ON c.id = cc.curso_id
          WHERE cc.categoria_id = g.id AND c.activo = 1) AS total_cursos
        FROM categorias g JOIN usuarios u ON u.id = g.creado_por';

    public function __construct(private PDO $db) {}

    public function listar(): array
    {
        $filas = $this->db->query(self::CONSULTA . ' ORDER BY g.nombre')->fetchAll();
        return array_map([$this, 'formatear'], $filas);
    }

    public function obtenerPorId(int $id): ?array
    {
        $st = $this->db->prepare(self::CONSULTA . ' WHERE g.id = :id');
        $st->execute(['id' => $id]);
        $fila = $st->fetch();
        return $fila ? $this->formatear($fila) : null;
    }

    public function existeNombre(string $nombre): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM categorias WHERE nombre = :nombre');
        $st->execute(['nombre' => $nombre]);
        return (bool) $st->fetchColumn();
    }

    public function crear(string $nombre, ?string $descripcion, int $creadoPor): int
    {
        $this->db->prepare('INSERT INTO categorias (nombre, descripcion, creado_por) VALUES (:nombre, :descripcion, :creado_por)')
            ->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'creado_por' => $creadoPor]);
        return (int) $this->db->lastInsertId();
    }

    public function contarExistentes(array $ids): int
    {
        if (!$ids) return 0;
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT COUNT(*) FROM categorias WHERE id IN ($marcas)");
        $st->execute(array_values($ids));
        return (int) $st->fetchColumn();
    }

    private function formatear(array $fila): array
    {
        return [
            'id_categoria'   => (int) $fila['id_categoria'],
            'nombre'         => $fila['nombre'],
            'descripcion'    => $fila['descripcion'],
            'creada_por'     => $fila['creada_por'],
            'fecha_creacion' => $fila['fecha_creacion'],
            'total_cursos'   => (int) $fila['total_cursos'],
        ];
    }
}
