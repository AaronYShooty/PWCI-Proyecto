<?php
class Curso
{
    private const COLUMNAS = 'c.id AS id_curso, c.titulo, c.descripcion, c.nivel, c.precio, c.imagen,
        c.instructor_id AS id_instructor, u.nombre_completo AS instructor, c.fecha_publicacion, c.activo,
        (SELECT ROUND(AVG(m.calificacion), 1) FROM comentarios m
          WHERE m.curso_id = c.id AND m.eliminado = 0) AS promedio_calificacion';

    private const ORDENES = [
        'recientes'    => 'c.fecha_publicacion DESC, c.id DESC',
        'calificacion' => 'promedio_calificacion IS NULL, promedio_calificacion DESC, c.fecha_publicacion DESC, c.id DESC',
        'ventas'       => '(SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) DESC, c.fecha_publicacion DESC, c.id DESC',
    ];

    public function __construct(private PDO $db) {}

    public function buscar(array $filtros, int $pagina, int $porPagina, string $orden = 'recientes'): array
    {
        $donde = ['c.activo = 1'];
        $params = [];

        if (isset($filtros['categoria'])) {
            $donde[] = 'EXISTS (SELECT 1 FROM curso_categoria cc WHERE cc.curso_id = c.id AND cc.categoria_id = :categoria)';
            $params['categoria'] = $filtros['categoria'];
        }
        if (isset($filtros['titulo'])) {
            $donde[] = 'c.titulo LIKE :titulo';
            $params['titulo'] = '%' . addcslashes($filtros['titulo'], '%_\\') . '%';
        }
        if (isset($filtros['nivel'])) {
            $donde[] = 'c.nivel = :nivel';
            $params['nivel'] = $filtros['nivel'];
        }
        if (isset($filtros['publicador'])) {
            $donde[] = 'c.instructor_id = :publicador';
            $params['publicador'] = $filtros['publicador'];
        }
        if (isset($filtros['fecha_inicio'])) {
            $donde[] = 'c.fecha_publicacion >= :fecha_inicio';
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (isset($filtros['fecha_fin'])) {
            $donde[] = 'c.fecha_publicacion < DATE_ADD(:fecha_fin, INTERVAL 1 DAY)';
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }

        $st = $this->db->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM cursos c JOIN usuarios u ON u.id = c.instructor_id WHERE ' . implode(' AND ', $donde) .
            ' ORDER BY ' . (self::ORDENES[$orden] ?? self::ORDENES['recientes']) . ' LIMIT :limite OFFSET :desplazamiento'
        );
        foreach ($params as $nombre => $valor) {
            $st->bindValue(":$nombre", $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $st->bindValue(':desplazamiento', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
        $st->execute();

        return $this->completar($st->fetchAll());
    }

    public function obtenerPorId(int $id, bool $soloActivos = true): ?array
    {
        $st = $this->db->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM cursos c JOIN usuarios u ON u.id = c.instructor_id WHERE c.id = :id'
            . ($soloActivos ? ' AND c.activo = 1' : '')
        );
        $st->execute(['id' => $id]);
        $fila = $st->fetch();
        return $fila ? $this->completar([$fila])[0] : null;
    }

    public function crear(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO cursos (instructor_id, titulo, descripcion, nivel, precio, imagen, activo)
                 VALUES (:instructor, :titulo, :descripcion, :nivel, :precio, :imagen, :activo)'
            )->execute([
                'instructor'  => $d['instructor_id'],
                'titulo'      => $d['titulo'],
                'descripcion' => $d['descripcion'],
                'nivel'       => $d['nivel'],
                'precio'      => $d['precio'],
                'imagen'      => $d['imagen'] ?? null,
                'activo'      => $d['activo'] ? 1 : 0,
            ]);
            $id = (int) $this->db->lastInsertId();

            $st = $this->db->prepare('INSERT INTO curso_categoria (curso_id, categoria_id) VALUES (:curso, :categoria)');
            foreach ($d['categorias'] as $categoria) {
                $st->execute(['curso' => $id, 'categoria' => $categoria]);
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $d): void
    {
        $sets = [];
        $params = ['id' => $id];
        foreach (['titulo', 'descripcion', 'nivel', 'precio', 'imagen', 'activo'] as $columna) {
            if (array_key_exists($columna, $d)) {
                $sets[] = "$columna = :$columna";
                $params[$columna] = $columna === 'activo' ? (int) $d[$columna] : $d[$columna];
            }
        }

        $this->db->beginTransaction();
        try {
            if ($sets) {
                $this->db->prepare('UPDATE cursos SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
            }
            if (isset($d['categorias'])) {
                $this->db->prepare('DELETE FROM curso_categoria WHERE curso_id = :id')->execute(['id' => $id]);
                $st = $this->db->prepare('INSERT INTO curso_categoria (curso_id, categoria_id) VALUES (:curso, :categoria)');
                foreach ($d['categorias'] as $categoria) {
                    $st->execute(['curso' => $id, 'categoria' => $categoria]);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function darDeBaja(int $id): void
    {
        $this->db->prepare('UPDATE cursos SET activo = 0 WHERE id = :id')->execute(['id' => $id]);
    }

    private function completar(array $cursos): array
    {
        if (!$cursos) return [];

        $ids = array_column($cursos, 'id_curso');
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare(
            "SELECT cc.curso_id, g.nombre FROM curso_categoria cc
             JOIN categorias g ON g.id = cc.categoria_id
             WHERE cc.curso_id IN ($marcas) ORDER BY g.nombre"
        );
        $st->execute($ids);

        $categorias = [];
        foreach ($st->fetchAll() as $fila) {
            $categorias[$fila['curso_id']][] = $fila['nombre'];
        }

        return array_map(fn($c) => [
            'id_curso'              => (int) $c['id_curso'],
            'titulo'                => $c['titulo'],
            'descripcion'           => $c['descripcion'],
            'nivel'                 => (int) $c['nivel'],
            'precio'                => (float) $c['precio'],
            'imagen'                => $c['imagen'],
            'id_instructor'         => (int) $c['id_instructor'],
            'instructor'            => $c['instructor'],
            'fecha_publicacion'     => $c['fecha_publicacion'],
            'categorias'            => $categorias[$c['id_curso']] ?? [],
            'promedio_calificacion' => $c['promedio_calificacion'] === null ? null : (float) $c['promedio_calificacion'],
            'activo'                => (bool) $c['activo'],
        ], $cursos);
    }

}
