<?php
class Nivel
{
    public function __construct(private PDO $db) {}

    public function porCurso(int $cursoId): array
    {
        $st = $this->db->prepare(
            'SELECT n.id, n.orden, n.titulo, n.gratis, n.video_url, n.contenido,
                    (SELECT COUNT(*) FROM archivos_nivel a WHERE a.nivel_id = n.id) AS adjuntos
             FROM niveles n WHERE n.curso_id = :c ORDER BY n.orden'
        );
        $st->execute(['c' => $cursoId]);
        return $st->fetchAll();
    }

    public function obtener(int $id): ?array
    {
        $st = $this->db->prepare(
            'SELECT n.*, c.instructor_id, c.activo, c.precio AS precio_curso
             FROM niveles n JOIN cursos c ON c.id = n.curso_id WHERE n.id = ?'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function adjuntos(int $nivelId): array
    {
        $st = $this->db->prepare('SELECT id, nombre FROM archivos_nivel WHERE nivel_id = ? ORDER BY id');
        $st->execute([$nivelId]);
        return $st->fetchAll();
    }

    public function obtenerAdjunto(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM archivos_nivel WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function guardar(int $cursoId, ?int $id, array $datos, ?array $adjunto): ?string
    {
        $this->db->beginTransaction();
        try {
            $this->bloquearCurso($cursoId);
            $reemplazado = null;
            if ($id === null) {
                $orden = $this->db->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM niveles WHERE curso_id = ?');
                $orden->execute([$cursoId]);
                $siguiente = (int) $orden->fetchColumn();
                if ($siguiente > 255) throw new DomainException('El curso alcanzó el límite de 255 niveles.');
                $this->db->prepare(
                    'INSERT INTO niveles (curso_id, orden, titulo, contenido, gratis, video_url) VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$cursoId, $siguiente, $datos['titulo'], $datos['contenido'], $datos['gratis'], $datos['video']]);
                $id = (int) $this->db->lastInsertId();
            } else {
                $actual = $this->obtener($id);
                if (!$actual || (int) $actual['curso_id'] !== $cursoId) throw new DomainException('El nivel ya no existe.');
                if ($datos['video'] !== null) $reemplazado = $actual['video_url'];
                $this->db->prepare(
                    'UPDATE niveles SET titulo = ?, contenido = ?, gratis = ?, video_url = ? WHERE id = ?'
                )->execute([$datos['titulo'], $datos['contenido'], $datos['gratis'], $datos['video'] ?? $actual['video_url'], $id]);
            }
            if ($adjunto !== null) {
                $this->db->prepare(
                    "INSERT INTO archivos_nivel (nivel_id, tipo, nombre, ruta) VALUES (?, 'pdf', ?, ?)"
                )->execute([$id, $adjunto['nombre'], $adjunto['ruta']]);
            }
            $this->db->commit();
            return $reemplazado;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public static function puedeVer(array $nivel, array $usuario, bool $inscrito): bool
    {
        return (int) $nivel['instructor_id'] === (int) $usuario['id']
            || $usuario['rol'] === 'administrador'
            || $inscrito
            || ($nivel['activo'] && $nivel['gratis']);
    }

    public function eliminar(int $cursoId, int $id): array
    {
        $this->db->beginTransaction();
        try {
            $activo = $this->bloquearCurso($cursoId);
            $nivel = $this->obtener($id);
            if (!$nivel || (int) $nivel['curso_id'] !== $cursoId) throw new DomainException('El nivel ya no existe.');
            if ($activo && count($this->porCurso($cursoId)) <= 1) {
                throw new DomainException('Da de baja el curso antes de quitar su último nivel.');
            }
            $avance = $this->db->prepare('SELECT 1 FROM avance_niveles WHERE nivel_id = ? LIMIT 1');
            $avance->execute([$id]);
            if ($avance->fetchColumn()) throw new DomainException('Este nivel ya tiene avance registrado de alumnos.');

            $archivos = $this->db->prepare('SELECT ruta FROM archivos_nivel WHERE nivel_id = ?');
            $archivos->execute([$id]);
            $rutas = [$nivel['video_url'], ...$archivos->fetchAll(PDO::FETCH_COLUMN)];
            $this->db->prepare('DELETE FROM niveles WHERE id = ?')->execute([$id]);
            $this->db->commit();
            return $rutas;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function bloquearCurso(int $cursoId): bool
    {
        $st = $this->db->prepare('SELECT activo FROM cursos WHERE id = ? FOR UPDATE');
        $st->execute([$cursoId]);
        $activo = $st->fetchColumn();
        if ($activo === false) throw new DomainException('El curso ya no existe.');
        return (bool) $activo;
    }

    /** Ids de los niveles que el alumno ya vio en una inscripción. */
    public function vistos(int $inscripcionId): array
    {
        $st = $this->db->prepare('SELECT nivel_id FROM avance_niveles WHERE inscripcion_id = :i');
        $st->execute(['i' => $inscripcionId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
}
