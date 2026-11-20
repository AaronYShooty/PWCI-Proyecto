<?php
class Nivel
{
    public function __construct(private PDO $db) {}

    public function porCurso(int $cursoId): array
    {
        $st = $this->db->prepare(
            'SELECT n.id, n.orden, n.titulo, n.precio,
                    (SELECT COUNT(*) FROM archivos_nivel a WHERE a.nivel_id = n.id) AS adjuntos
             FROM niveles n WHERE n.curso_id = :c ORDER BY n.orden'
        );
        $st->execute(['c' => $cursoId]);
        return $st->fetchAll();
    }

    /** Ids de los niveles que el alumno ya vio en una inscripción. */
    public function vistos(int $inscripcionId): array
    {
        $st = $this->db->prepare('SELECT nivel_id FROM avance_niveles WHERE inscripcion_id = :i');
        $st->execute(['i' => $inscripcionId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
}