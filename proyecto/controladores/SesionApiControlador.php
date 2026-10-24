<?php

class SesionApiControlador extends ApiControlador
{
    public function __construct(private Conexion $conexion) {}
    public function atender(string $metodo, ?string $id): void
    {
        try {
            if ($metodo === 'POST') {
                $this->crear();
                return;
            }
            if ($metodo === 'DELETE') {
                $this->eliminar();
                return;
            }
            header('Allow: POST, DELETE');
            $this->fallo('Método no permitido.', 405);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fallo('Error interno del servidor.', 500);
        }
    }

    private function crear(): void
    {
        $cuerpo = $this->leerJson();
        if ($cuerpo === null) return;

        $errores = [];

        $email = $cuerpo['correo'] ?? null;
        if (!$this->textoValido($email, 254) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Obligatorio: correo electrónico válido de hasta 254 caracteres.';
        }
        $clave = $cuerpo['password'] ?? null;
        if (!$this->textoValido($clave, 64)) {
            $errores['password'] = 'Obligatorio: contraseña de hasta 64 caracteres.';
        }
        if ($errores) {
            $this->fallo('Los datos enviados no son válidos.', 400, $errores);
            return;
        }

        $email = $email === null || trim($email) === '' ? null : trim($email);
        $error = 'Correo o contraseña incorrectos.';
        $u = (new Usuario($this->conexion->obtener()))->buscarPorEmail($email);

        if ($u && !$u['activo']) {
            $error = 'Cuenta bloqueada. Contacta al administrador.';
        } elseif ($u && password_verify($clave, $u['clave_hash'])) {
            (new Usuario($this->conexion->obtener()))->reiniciarIntentos((int) $u['id']);
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int) $u['id'], 'nombre' => $u['nombre_completo'], 'rol' => $u['rol'], 'foto' => $u['foto']];
            $this->exito('Inicio de sesión exitoso', $_SESSION['usuario']);
            return;
        } elseif ($u) {
            (new Usuario($this->conexion->obtener()))->registrarFallo((int) $u['id']);
        }

        $this->fallo($error, 401);
    }

    private function eliminar(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->exito('Sesión cerrada correctamente');
    }
}