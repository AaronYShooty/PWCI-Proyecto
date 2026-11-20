<?php
class Usuario
{
    public function __construct(private PDO $db) {}

    public function buscarPorEmail(string $email): ?array
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email');
        $st->execute(['email' => $email]);
        return $st->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public function registrarFallo(int $id): void
    {
        $this->db->prepare(
            'UPDATE usuarios SET activo = IF(intentos_fallidos + 1 >= 3, 0, activo),
                                 intentos_fallidos = intentos_fallidos + 1 WHERE id = :id'
        )->execute(['id' => $id]);
    }

    public function reiniciarIntentos(int $id): void
    {
        $this->db->prepare('UPDATE usuarios SET intentos_fallidos = 0 WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function existeEmail(string $email): bool
    {
        return $this->buscarPorEmail($email) !== null;
    }

    public function registrar(array $datos): int
    {
        $st = $this->db->prepare(
            'INSERT INTO usuarios (nombre_completo, genero, fecha_nacimiento, email, clave_hash, rol, foto)
             VALUES (:nombre, :genero, :nacimiento, :email, :clave_hash, :rol, :foto)'
        );
        $st->execute([
            'nombre'     => $datos['nombre'],
            'genero'     => $datos['genero'],
            'nacimiento' => $datos['nacimiento'],
            'email'      => $datos['email'],
            'clave_hash' => password_hash($datos['password'], PASSWORD_DEFAULT),
            'rol'        => $datos['rol'],
            'foto'       => $datos['foto'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $st = $this->db->prepare(
            'UPDATE usuarios SET nombre_completo = :nombre, genero = :genero, fecha_nacimiento = :nacimiento, email = :email, foto = :foto WHERE id = :id'
        );
        $st->execute([
            'id'        => $id,
            'nombre'    => $datos['nombre'],
            'genero'    => $datos['genero'],
            'nacimiento'=> $datos['nacimiento'],
            'email'     => $datos['email'],
            'foto'      => $datos['foto'] ?? null,
        ]);
    }

    // ---- Administración ----

    public function listar(?string $rol = null, ?string $busqueda = null): array
    {
        $where = ['1 = 1']; $p = [];
        if ($rol && in_array($rol, ['estudiante', 'instructor', 'administrador'], true)) { $where[] = 'rol = :rol'; $p['rol'] = $rol; }
        if ($busqueda !== null && $busqueda !== '') {
            $where[] = '(nombre_completo LIKE :q OR email LIKE :q2)';
            $p['q'] = $p['q2'] = '%' . $busqueda . '%';
        }
        $st = $this->db->prepare(
            'SELECT id, email, nombre_completo, rol, foto, activo, fecha_registro FROM usuarios
             WHERE ' . implode(' AND ', $where) . ' ORDER BY fecha_registro DESC, id DESC'
        );
        $st->execute($p);
        return $st->fetchAll();
    }

    public function conteos(): array
    {
        return $this->db->query(
            "SELECT SUM(activo = 1) AS activos, SUM(rol = 'instructor' AND activo = 1) AS instructores,
                    SUM(activo = 0) AS bloqueados FROM usuarios"
        )->fetch() ?: ['activos' => 0, 'instructores' => 0, 'bloqueados' => 0];
    }

    // Bloquear / desbloquear. Los administradores no se pueden bloquear.
    public function cambiarActivo(int $id, bool $activo): bool
    {
        $st = $this->db->prepare(
            "UPDATE usuarios SET activo = :a, intentos_fallidos = 0 WHERE id = :id AND rol <> 'administrador'"
        );
        $st->execute(['a' => (int) $activo, 'id' => $id]);
        return $st->rowCount() > 0;
    }

    // Reporte de instructores activos: cursos ofrecidos y total de ganancias.
    public function reporteInstructores(): array
    {
        return $this->db->query(
            "SELECT u.email, u.nombre_completo, u.fecha_registro,
                    COUNT(DISTINCT c.id) AS cursos, COALESCE(SUM(i.precio_pagado), 0) AS ganancias
             FROM usuarios u
             LEFT JOIN cursos c ON c.instructor_id = u.id
             LEFT JOIN inscripciones i ON i.curso_id = c.id
             WHERE u.rol = 'instructor' AND u.activo = 1
             GROUP BY u.id, u.email, u.nombre_completo, u.fecha_registro
             ORDER BY ganancias DESC, u.nombre_completo"
        )->fetchAll();
    }

    // Reporte de estudiantes activos: cursos inscritos y porcentaje terminado.
    public function reporteEstudiantes(): array
    {
        return $this->db->query(
            "SELECT u.email, u.nombre_completo, u.fecha_registro, COUNT(i.id) AS inscritos,
                    IF(COUNT(i.id) = 0, 0, ROUND(100 * SUM(i.completado) / COUNT(i.id))) AS pct_terminados
             FROM usuarios u LEFT JOIN inscripciones i ON i.usuario_id = u.id
             WHERE u.rol = 'estudiante' AND u.activo = 1
             GROUP BY u.id, u.email, u.nombre_completo, u.fecha_registro
             ORDER BY u.nombre_completo"
        )->fetchAll();
    }
}