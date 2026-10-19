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

    // TODO: actualizarPerfil()
}