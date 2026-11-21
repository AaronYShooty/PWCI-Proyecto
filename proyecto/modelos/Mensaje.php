<?php
class Mensaje
{
    public function __construct(private PDO $db) {}

    /**
     * Personas con las que se puede conversar: el estudiante con los instructores de sus cursos,
     * el instructor con los estudiantes inscritos en sus cursos.
     */
    public function contactos(int $usuarioId, string $rol): array
    {
        $sql = $rol === 'instructor'
            ? 'SELECT u.id, u.nombre_completo AS nombre, u.foto, MIN(c.titulo) AS curso, MIN(c.id) AS curso_id
               FROM inscripciones i JOIN cursos c ON c.id = i.curso_id JOIN usuarios u ON u.id = i.usuario_id
               WHERE c.instructor_id = :id GROUP BY u.id, u.nombre_completo, u.foto'
            : 'SELECT u.id, u.nombre_completo AS nombre, u.foto, MIN(c.titulo) AS curso, MIN(c.id) AS curso_id
               FROM inscripciones i JOIN cursos c ON c.id = i.curso_id JOIN usuarios u ON u.id = c.instructor_id
               WHERE i.usuario_id = :id GROUP BY u.id, u.nombre_completo, u.foto';
        $st = $this->db->prepare($sql);
        $st->execute(['id' => $usuarioId]);
        $contactos = $st->fetchAll();

        $ultimo = $this->db->prepare(
            'SELECT texto, fecha_envio FROM mensajes
             WHERE (emisor_id = :a AND receptor_id = :b) OR (emisor_id = :b2 AND receptor_id = :a2)
             ORDER BY fecha_envio DESC, id DESC LIMIT 1'
        );
        foreach ($contactos as &$c) {
            $ultimo->execute(['a' => $usuarioId, 'b' => $c['id'], 'b2' => $c['id'], 'a2' => $usuarioId]);
            $m = $ultimo->fetch();
            $c['ultimo_texto'] = $m['texto'] ?? null;
            $c['ultima_fecha'] = $m['fecha_envio'] ?? null;
        }
        unset($c);

        usort($contactos, fn($x, $y) => strcmp($y['ultima_fecha'] ?? '', $x['ultima_fecha'] ?? ''));
        return $contactos;
    }

    public function hilo(int $a, int $b): array
    {
        $st = $this->db->prepare(
            'SELECT emisor_id, texto, fecha_envio FROM mensajes
             WHERE (emisor_id = :a AND receptor_id = :b) OR (emisor_id = :b2 AND receptor_id = :a2)
             ORDER BY fecha_envio, id'
        );
        $st->execute(['a' => $a, 'b' => $b, 'b2' => $b, 'a2' => $a]);
        return $st->fetchAll();
    }

    public function enviar(int $emisor, int $receptor, ?int $cursoId, string $texto): void
    {
        $this->db->prepare(
            'INSERT INTO mensajes (emisor_id, receptor_id, curso_id, texto) VALUES (:e, :r, :c, :t)'
        )->execute(['e' => $emisor, 'r' => $receptor, 'c' => $cursoId, 't' => $texto]);
    }
}