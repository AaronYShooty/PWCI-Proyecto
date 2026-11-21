<?php
class Comentario
{
    public function __construct(private PDO $db) {}

    // Comentarios de un curso. Los eliminados se devuelven sin su texto original.
    public function porCurso(int $cursoId): array
    {
        $st = $this->db->prepare(
            'SELECT m.id, m.calificacion, m.fecha_creacion, m.eliminado,
                    IF(m.eliminado = 1, NULL, m.texto) AS texto,
                    m.usuario_id, u.nombre_completo AS autor, u.foto
             FROM comentarios m JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.curso_id = :c ORDER BY m.fecha_creacion DESC'
        );
        $st->execute(['c' => $cursoId]);
        return $st->fetchAll();
    }

    public function publicar(int $cursoId, int $usuarioId, string $texto, int $calificacion): bool
    {
        $st = $this->db->prepare(
            'INSERT INTO comentarios (curso_id, usuario_id, texto, calificacion) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id'
        );
        $st->execute([$cursoId, $usuarioId, $texto, $calificacion]);
        return $st->rowCount() === 1;
    }

    // Lista completa para moderación (administrador).
    public function paraModeracion(): array
    {
        return $this->db->query(
            'SELECT m.id, m.texto, m.calificacion, m.fecha_creacion, m.eliminado, m.fecha_eliminacion, m.causa_eliminacion,
                    u.nombre_completo AS autor, u.foto, c.titulo AS curso
             FROM comentarios m
             JOIN usuarios u ON u.id = m.usuario_id
             JOIN cursos c ON c.id = m.curso_id
             ORDER BY m.eliminado ASC, m.fecha_creacion DESC'
        )->fetchAll();
    }

    public function totalEliminados(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM comentarios WHERE eliminado = 1')->fetchColumn();
    }

    // Eliminación lógica: se conserva la fecha y la causa.
    public function eliminar(int $id, string $causa): bool
    {
        $st = $this->db->prepare(
            'UPDATE comentarios SET eliminado = 1, fecha_eliminacion = NOW(), causa_eliminacion = :causa
             WHERE id = :id AND eliminado = 0'
        );
        $st->execute(['id' => $id, 'causa' => $causa]);
        return $st->rowCount() > 0;
    }
}
