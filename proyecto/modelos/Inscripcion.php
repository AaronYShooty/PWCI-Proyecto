<?php
class Inscripcion
{
    public function __construct(private PDO $db) {}

    public function obtener(int $usuarioId, int $cursoId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM inscripciones WHERE usuario_id = :u AND curso_id = :c');
        $st->execute(['u' => $usuarioId, 'c' => $cursoId]);
        return $st->fetch() ?: null;
    }

    // Curso gratis: inscripción sin pago.
    public function inscribirGratis(int $usuarioId, int $cursoId): int
    {
        $this->db->prepare(
            'INSERT IGNORE INTO inscripciones (usuario_id, curso_id, forma_pago_id, precio_pagado)
             VALUES (:u, :c, NULL, 0)'
        )->execute(['u' => $usuarioId, 'c' => $cursoId]);
        return (int) $this->obtener($usuarioId, $cursoId)['id'];
    }

    // ¿Ya se registró este cobro de Stripe? (hace seguro confirmar dos veces el mismo pago)
    public function compraRegistrada(string $intento): ?array
    {
        $st = $this->db->prepare('SELECT * FROM pagos WHERE stripe_payment_intent = :i');
        $st->execute(['i' => $intento]);
        return $st->fetch() ?: null;
    }

    // Registra la inscripción y el pago en una sola transacción.
    public function registrarCompra(int $usuarioId, int $cursoId, string $intento, float $monto, string $moneda): int
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT id FROM inscripciones WHERE usuario_id = :u AND curso_id = :c FOR UPDATE');
            $st->execute(['u' => $usuarioId, 'c' => $cursoId]);
            $id = $st->fetchColumn();

            if ($id === false) {
                $this->db->prepare(
                    "INSERT INTO inscripciones (usuario_id, curso_id, forma_pago_id, precio_pagado)
                     VALUES (:u, :c, (SELECT id FROM formas_pago WHERE nombre = 'Tarjeta (Stripe)'), :p)"
                )->execute(['u' => $usuarioId, 'c' => $cursoId, 'p' => $monto]);
                $id = $this->db->lastInsertId();
            }

            $this->db->prepare(
                'INSERT INTO pagos (inscripcion_id, stripe_payment_intent, monto, moneda) VALUES (:i, :pi, :m, :mo)'
            )->execute(['i' => $id, 'pi' => $intento, 'm' => $monto, 'mo' => $moneda]);

            $this->db->commit();
            return (int) $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            if ($e->getCode() === '23000') {                 // el pago ya estaba registrado (doble clic / recarga)
                $previa = $this->compraRegistrada($intento);
                if ($previa) return (int) $previa['inscripcion_id'];
            }
            throw $e;
        }
    }

    // ---------------- Estudiante: Kardex ----------------

    public function kardex(int $usuarioId, array $f = []): array
    {
        $where = ['i.usuario_id = :u']; $p = ['u' => $usuarioId];
        if (!empty($f['desde']))     { $where[] = 'DATE(i.fecha_inscripcion) >= :desde'; $p['desde'] = $f['desde']; }
        if (!empty($f['hasta']))     { $where[] = 'DATE(i.fecha_inscripcion) <= :hasta'; $p['hasta'] = $f['hasta']; }
        if (!empty($f['categoria'])) {
            $where[] = 'EXISTS (SELECT 1 FROM curso_categoria x WHERE x.curso_id = c.id AND x.categoria_id = :cat)';
            $p['cat'] = (int) $f['categoria'];
        }
        if (!empty($f['terminados'])) $where[] = 'i.completado = 1';
        if (!empty($f['activos']))    $where[] = 'c.activo = 1';

        $st = $this->db->prepare(
            "SELECT i.id AS inscripcion_id, c.id AS curso_id, c.titulo, c.activo, u.nombre_completo AS instructor,
                    i.fecha_inscripcion, i.ultima_fecha_acceso, i.fecha_terminacion, i.completado,
                    (SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ')
                       FROM curso_categoria cc JOIN categorias g ON g.id = cc.categoria_id WHERE cc.curso_id = c.id) AS categorias,
                    (SELECT COUNT(*) FROM niveles n WHERE n.curso_id = c.id) AS total_niveles,
                    (SELECT COUNT(*) FROM avance_niveles a WHERE a.inscripcion_id = i.id) AS niveles_vistos
             FROM inscripciones i
             JOIN cursos c ON c.id = i.curso_id
             JOIN usuarios u ON u.id = c.instructor_id
             WHERE " . implode(' AND ', $where) . ' ORDER BY i.fecha_inscripcion DESC, i.id DESC'
        );
        $st->execute($p);

        return array_map(function ($f) {
            $total = (int) $f['total_niveles'];
            $f['avance'] = $f['completado'] ? 100 : ($total > 0 ? (int) round(100 * $f['niveles_vistos'] / $total) : 0);
            return $f;
        }, $st->fetchAll());
    }

    public function resumenAlumno(int $usuarioId): array
    {
        $st = $this->db->prepare('SELECT COUNT(*) AS inscritos, COALESCE(SUM(completado), 0) AS terminados FROM inscripciones WHERE usuario_id = :u');
        $st->execute(['u' => $usuarioId]);
        $r = $st->fetch();
        return ['inscritos' => (int) $r['inscritos'], 'terminados' => (int) $r['terminados'], 'en_progreso' => (int) $r['inscritos'] - (int) $r['terminados']];
    }

    // ---------------- Diploma ----------------

    // Inscripción terminada de un alumno (la de un curso concreto o la más reciente).
    public function terminada(int $usuarioId, ?int $cursoId = null): ?array
    {
        $st = $this->db->prepare(
            'SELECT i.id AS inscripcion_id, c.id AS curso_id, c.titulo, u.nombre_completo AS instructor, i.fecha_terminacion
             FROM inscripciones i JOIN cursos c ON c.id = i.curso_id JOIN usuarios u ON u.id = c.instructor_id
             WHERE i.usuario_id = :u AND i.completado = 1' . ($cursoId ? ' AND c.id = :c' : '') . '
             ORDER BY i.fecha_terminacion DESC, i.id DESC LIMIT 1'
        );
        $st->execute($cursoId ? ['u' => $usuarioId, 'c' => $cursoId] : ['u' => $usuarioId]);
        return $st->fetch() ?: null;
    }

    // Registra el certificado la primera vez que se genera.
    public function registrarCertificado(int $inscripcionId, string $certificadoPor): void
    {
        $this->db->prepare('INSERT IGNORE INTO certificados (inscripcion_id, certificado_por) VALUES (:i, :c)')
                 ->execute(['i' => $inscripcionId, 'c' => $certificadoPor]);
    }

    // ---------------- Instructor: ventas ----------------

    public function resumenInstructor(int $instructorId): array
    {
        $st = $this->db->prepare(
            "SELECT
               (SELECT COUNT(*) FROM cursos WHERE instructor_id = :a AND activo = 1) AS cursos_activos,
               (SELECT COUNT(*) FROM inscripciones i JOIN cursos c ON c.id = i.curso_id WHERE c.instructor_id = :b) AS alumnos,
               (SELECT COALESCE(SUM(i.precio_pagado), 0) FROM inscripciones i JOIN cursos c ON c.id = i.curso_id
                 WHERE c.instructor_id = :c AND i.fecha_inscripcion >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS ingresos_mes,
               (SELECT AVG(m.calificacion) FROM comentarios m JOIN cursos c ON c.id = m.curso_id
                 WHERE c.instructor_id = :d AND m.eliminado = 0) AS calificacion"
        );
        $st->execute(['a' => $instructorId, 'b' => $instructorId, 'c' => $instructorId, 'd' => $instructorId]);
        return $st->fetch();
    }

    public function ventasPorCurso(int $instructorId, array $f = []): array
    {
        $where = ['c.instructor_id = :ins']; $p = ['ins' => $instructorId];
        if (!empty($f['desde'])) { $where[] = 'DATE(c.fecha_publicacion) >= :desde'; $p['desde'] = $f['desde']; }
        if (!empty($f['hasta'])) { $where[] = 'DATE(c.fecha_publicacion) <= :hasta'; $p['hasta'] = $f['hasta']; }
        if (!empty($f['categoria'])) {
            $where[] = 'EXISTS (SELECT 1 FROM curso_categoria x WHERE x.curso_id = c.id AND x.categoria_id = :cat)';
            $p['cat'] = (int) $f['categoria'];
        }
        if (!empty($f['activos'])) $where[] = 'c.activo = 1';

        $st = $this->db->prepare(
            'SELECT c.id AS curso_id, c.titulo, c.activo, c.fecha_publicacion,
                    (SELECT COUNT(*) FROM niveles n WHERE n.curso_id = c.id) AS total_niveles,
                    COUNT(i.id) AS alumnos,
                    COALESCE(SUM(i.precio_pagado), 0) AS ingresos,
                    COALESCE(AVG(COALESCE(av.vistos, 0)), 0) AS nivel_promedio
             FROM cursos c
             LEFT JOIN inscripciones i ON i.curso_id = c.id
             LEFT JOIN (SELECT inscripcion_id, COUNT(*) AS vistos FROM avance_niveles GROUP BY inscripcion_id) av
                    ON av.inscripcion_id = i.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY c.id, c.titulo, c.activo, c.fecha_publicacion
             ORDER BY c.fecha_publicacion DESC'
        );
        $st->execute($p);
        return $st->fetchAll();
    }

    /** Detalle de cada alumno inscrito en los cursos indicados (del instructor), agrupado por curso. */
    public function ventasDetalle(int $instructorId, array $cursoIds): array
    {
        if (!$cursoIds) return [];
        $marcas = implode(',', array_fill(0, count($cursoIds), '?'));
        $st = $this->db->prepare(
            "SELECT i.curso_id, u.nombre_completo AS alumno, i.fecha_inscripcion, i.precio_pagado,
                    COALESCE(f.nombre, 'Gratis') AS forma_pago, COALESCE(av.vistos, 0) AS vistos
             FROM inscripciones i
             JOIN cursos c ON c.id = i.curso_id AND c.instructor_id = ?
             JOIN usuarios u ON u.id = i.usuario_id
             LEFT JOIN formas_pago f ON f.id = i.forma_pago_id
             LEFT JOIN (SELECT inscripcion_id, COUNT(*) AS vistos FROM avance_niveles GROUP BY inscripcion_id) av
                    ON av.inscripcion_id = i.id
             WHERE i.curso_id IN ($marcas)
             ORDER BY i.fecha_inscripcion DESC"
        );
        $st->execute([$instructorId, ...array_map('intval', $cursoIds)]);
        $porCurso = [];
        foreach ($st->fetchAll() as $fila) $porCurso[$fila['curso_id']][] = $fila;
        return $porCurso;
    }

    // ---------------- Administrador: reportes ----------------

    public function resumenGlobal(): array
    {
        return $this->db->query(
            'SELECT (SELECT COUNT(*) FROM cursos WHERE activo = 1) AS cursos_activos,
                    (SELECT COUNT(*) FROM inscripciones) AS inscripciones,
                    (SELECT COUNT(*) FROM inscripciones WHERE completado = 1) AS terminados,
                    (SELECT COALESCE(SUM(precio_pagado), 0) FROM inscripciones) AS ingresos'
        )->fetch();
    }

    public function actividadCursos(?int $categoriaId = null): array
    {
        $st = $this->db->prepare(
            "SELECT c.id AS curso_id, c.titulo, c.activo, u.nombre_completo AS instructor,
                    (SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ')
                       FROM curso_categoria cc JOIN categorias g ON g.id = cc.categoria_id WHERE cc.curso_id = c.id) AS categorias,
                    COUNT(i.id) AS inscritos,
                    COALESCE(SUM(i.completado), 0) AS terminaron,
                    COALESCE(SUM(i.precio_pagado), 0) AS ingresos,
                    (SELECT AVG(m.calificacion) FROM comentarios m WHERE m.curso_id = c.id AND m.eliminado = 0) AS calificacion
             FROM cursos c
             JOIN usuarios u ON u.id = c.instructor_id
             LEFT JOIN inscripciones i ON i.curso_id = c.id
             WHERE (:cat1 IS NULL OR EXISTS (SELECT 1 FROM curso_categoria x WHERE x.curso_id = c.id AND x.categoria_id = :cat2))
             GROUP BY c.id, c.titulo, c.activo, u.nombre_completo
             ORDER BY ingresos DESC, c.titulo"
        );
        $st->execute(['cat1' => $categoriaId, 'cat2' => $categoriaId]);
        return $st->fetchAll();
    }
}