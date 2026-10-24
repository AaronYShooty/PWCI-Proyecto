<?php
class UsuarioControlador extends Controlador
{
    private const TIPOS_FOTO = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    private const MAX_FOTO = 2 * 1024 * 1024;

    public function __construct(private Usuario $usuarios) {}

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $aviso = $_SESSION['aviso'] ?? null;
            unset($_SESSION['aviso']);
            $this->vista('publico/login', ['aviso' => $aviso]);
            return;
        }

        $email = trim($_POST['correo'] ?? '');
        $clave = $_POST['password'] ?? '';
        $error = 'Correo o contraseña incorrectos.';

        $u = $this->usuarios->buscarPorEmail($email);

        if ($u && !$u['activo']) {
            $error = 'Cuenta bloqueada. Contacta al administrador.';
        } elseif ($u && password_verify($clave, $u['clave_hash'])) {
            $this->usuarios->reiniciarIntentos((int) $u['id']);
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int) $u['id'], 'nombre' => $u['nombre_completo'], 'rol' => $u['rol'], 'foto' => $u['foto']];
            $paneles = ['estudiante' => 'estudiante', 'instructor' => 'instructor', 'administrador' => 'admin'];
            $this->redirigir($paneles[$u['rol']]);
        } elseif ($u) {
            $this->usuarios->registrarFallo((int) $u['id']);
        }

        $this->vista('publico/login', ['error' => $error, 'email' => $email]);
    }

    public function registro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->vista('publico/registro'); return; }

        $datos = [
            'nombre'     => preg_replace('/\s+/', ' ', trim($_POST['nombre'] ?? '')),
            'email'      => strtolower(trim($_POST['correo'] ?? '')),
            'genero'     => $_POST['genero'] ?? '',
            'nacimiento' => $_POST['nacimiento'] ?? '',
            'password'   => $_POST['password'] ?? '',
            'password2'  => $_POST['password2'] ?? '',
            'rol'        => $_POST['role'] ?? '',
        ];

        $errores = $this->validarRegistro($datos);
        $errorFoto = $this->validarFoto($_FILES['foto'] ?? null);
        if ($errorFoto) $errores['foto'] = $errorFoto;

        if (!$errores) {
            $datos['foto'] = $this->guardarFoto($_FILES['foto']);
            try {
                $this->usuarios->registrar($datos);
            } catch (Throwable $e) {
                unlink(dirname(__DIR__) . '/' . $datos['foto']);
                throw $e;
            }
            $_SESSION['aviso'] = 'Cuenta creada. Ya puedes iniciar sesión.';
            $this->redirigir('login');
        }

        unset($datos['password'], $datos['password2']);
        $this->vista('publico/registro', ['errores' => $errores, 'datos' => $datos]);
    }

    public function perfil(): void
    {
        $id = (int) $_SESSION['usuario']['id'];
        $u = $this->usuarios->buscarPorId($id);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->vista('perfil', ['usuario' => $u]);
            return;
        }

        $datos = [
            'nombre'     => preg_replace('/\s+/', ' ', trim($_POST['nombre'] ?? '')),
            'email'      => strtolower(trim($_POST['correo'] ?? '')),
            'genero'     => $_POST['genero'] ?? '',
            'nacimiento' => $_POST['nacimiento'] ?? '',
        ];

        $errores = $this->validarPerfil($datos, $u['email']);
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorFoto = $this->validarFoto($_FILES['foto']);
            if ($errorFoto) $errores['foto'] = $errorFoto;
        }

        if ($errores) {
            $this->vista('perfil', ['usuario' => $u, 'errores' => $errores, 'datos' => $datos]);
            return;
        }

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $datos['foto'] = $this->guardarFoto($_FILES['foto']);
        } else {
            $datos['foto'] = $u['foto'];
        }
        $this->usuarios->actualizar($id, $datos);
        if ($u['foto'] && $u['foto'] !== $datos['foto']) {
            unlink(dirname(__DIR__) . '/' . $u['foto']);
        }
        $_SESSION['usuario']['nombre'] = $datos['nombre'];
        $_SESSION['usuario']['foto'] = $datos['foto'];
        $u = $this->usuarios->buscarPorId($id);
        $this->vista('perfil', ['usuario' => $u, 'aviso' => 'Perfil actualizado correctamente.']);
    }

    private function validarFoto(?array $f): ?string
    {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return 'La foto de perfil es obligatoria.';
        if ($f['error'] !== UPLOAD_ERR_OK) return 'No se pudo subir la foto. Intenta de nuevo.';
        if ($f['size'] > self::MAX_FOTO) return 'La foto no puede pesar más de 2 MB.';
        if (!isset(self::TIPOS_FOTO[(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])])) {
            return 'La foto debe ser JPG o PNG.';
        }
        return null;
    }

    private function guardarFoto(array $f): string
    {
        $carpeta = dirname(__DIR__) . '/publico/uploads/avatares';
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $extension = self::TIPOS_FOTO[(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])];
        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($f['tmp_name'], "$carpeta/$nombre")) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }
        return "publico/uploads/avatares/$nombre";
    }

    private function validarRegistro(array $d): array
    {
        $errores = [];

        $largoNombre = mb_strlen($d['nombre']);
        if ($largoNombre < 5 || $largoNombre > 80 || !preg_match("/^[a-záéíóúüñ]+(?:[ '\-][a-záéíóúüñ]+)+$/iu", $d['nombre'])) {
            $errores['nombre'] = 'Escribe nombre y apellidos válidos (5 a 80 caracteres, solo letras).';
        }

        if (strlen($d['email']) > 254 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El correo no es válido.';
        } elseif ($this->usuarios->existeEmail($d['email'])) {
            $errores['email'] = 'Ese correo ya está registrado.';
        }

        if (!in_array($d['genero'], ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'], true)) {
            $errores['genero'] = 'Selecciona una opción.';
        }

        $hoy = new DateTime('today');
        $fecha = DateTime::createFromFormat('Y-m-d', $d['nacimiento']);
        if (!$fecha || $fecha->format('Y-m-d') !== $d['nacimiento']) {
            $errores['nacimiento'] = 'La fecha no es válida.';
        } elseif ($fecha > $hoy || $fecha->diff($hoy)->y < 13 || $fecha->diff($hoy)->y > 120) {
            $errores['nacimiento'] = 'Debes tener entre 13 y 120 años.';
        }

        $clave = $d['password'];
        $largoClave = mb_strlen($clave);
        if ($largoClave < 8 || $largoClave > 64 || !preg_match('/\p{Lu}/u', $clave)
            || !preg_match('/\d/', $clave) || !preg_match('/[^\p{L}\p{N}\s]/u', $clave)) {
            $errores['password'] = 'La contraseña debe tener de 8 a 64 caracteres, una mayúscula, un número y un símbolo.';
        }
        if ($clave !== $d['password2']) {
            $errores['password2'] = 'Las contraseñas no coinciden.';
        }

        if (!in_array($d['rol'], ['estudiante', 'instructor'], true)) {
            $errores['rol'] = 'Selecciona un rol válido.';
        }

        return $errores;
    }

    private function validarPerfil(array $d, string $emailActual): array
    {
        $errores = [];

        $largoNombre = mb_strlen($d['nombre']);
        if ($largoNombre < 5 || $largoNombre > 80 || !preg_match("/^[a-záéíóúüñ]+(?:[ '\-][a-záéíóúüñ]+)+$/iu", $d['nombre'])) {
            $errores['nombre'] = 'Escribe nombre y apellidos válidos (5 a 80 caracteres, solo letras).';
        }

        if (strlen($d['email']) > 254 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El correo no es válido.';
        }
        elseif ($this->usuarios->existeEmail($d['email']) && $d['email'] !== $emailActual) {
            $errores['email'] = 'Ese correo ya está registrado.';
        }

        if (!in_array($d['genero'], ['Femenino', 'Masculino', 'Otro', 'Prefiero no decir'], true)) {
            $errores['genero'] = 'Selecciona una opción.';
        }

        $hoy = new DateTime('today');
        $fecha = DateTime::createFromFormat('Y-m-d', $d['nacimiento']);
        if (!$fecha || $fecha->format('Y-m-d') !== $d['nacimiento']) {
            $errores['nacimiento'] = 'La fecha no es válida.';
        } elseif ($fecha > $hoy || $fecha->diff($hoy)->y < 13 || $fecha->diff($hoy)->y > 120) {
            $errores['nacimiento'] = 'Debes tener entre 13 y 120 años.';
        }

        return $errores;
    }

    public function panelEstudiante(): void      { $this->vista('estudiante/panel_estudiante'); }
    public function panelInstructor(): void      { $this->vista('instructor/panel_instructor'); }
    public function panelAdministrador(): void   { $this->vista('administrador/panel_administrador'); }

    public function salir(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirigir('inicio');
    }
}