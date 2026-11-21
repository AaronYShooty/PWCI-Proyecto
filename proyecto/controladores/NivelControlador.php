<?php
class NivelControlador extends Controlador
{
    public function __construct(private Curso $cursos, private Nivel $niveles, private MediosCurso $medios) {}

    public function editar(): void
    {
        $id = $this->id($_GET['curso'] ?? null);
        $curso = $id ? $this->cursos->obtenerPorId($id, false) : null;
        if (!$curso || $curso['id_instructor'] !== (int) $_SESSION['usuario']['id']) {
            http_response_code(404);
            exit('No encontramos ese curso.');
        }

        $csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $error = '';
        $nivelId = $this->id($_GET['nivel'] ?? null);
        $nivel = $nivelId ? $this->niveles->obtener($nivelId) : null;
        if ($nivel && (int) $nivel['curso_id'] !== $id) $nivel = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
                    throw new DomainException('El formulario expiró o los archivos superan el tamaño de carga del servidor.');
                }
                if (isset($_GET['nivel']) && !$nivel) throw new DomainException('El nivel ya no existe.');
                $accion = $_POST['accion'] ?? null;
                switch ($accion) {
                    case 'guardar':
                        $this->guardar($id, $nivel);
                        $mensaje = 'Nivel guardado correctamente.';
                        break;
                    case 'eliminar':
                        if (!$nivel) throw new DomainException('Elige el nivel que quieres eliminar.');
                        foreach ($this->niveles->eliminar($id, $nivelId) as $archivo) $this->medios->quitar($archivo);
                        $mensaje = 'Nivel eliminado correctamente.';
                        break;
                    case 'publicar':
                        $niveles = $this->niveles->porCurso($id);
                        if (!$niveles) throw new DomainException('Agrega un nivel con video antes de publicar.');
                        foreach ($niveles as $n) {
                            if (!$this->medios->disponible($n['video_url'])) {
                                throw new DomainException('Sube un video para cada nivel antes de publicar.');
                            }
                        }
                        $this->cursos->actualizar($id, ['activo' => true]);
                        $mensaje = 'Curso publicado correctamente.';
                        break;
                    default:
                        throw new DomainException('Selecciona una acción válida.');
                }
                $_SESSION['aviso_niveles'] = $mensaje;
                $this->redirigir('editar-niveles&curso=' . $id);
            } catch (DomainException $e) {
                $error = $e->getMessage();
                http_response_code(400);
            }
        }

        $aviso = $_SESSION['aviso_niveles'] ?? (($_GET['creado'] ?? '') === '1' ? 'Borrador guardado correctamente.' : '');
        unset($_SESSION['aviso_niveles']);
        $this->vista('instructor/editar_niveles', [
            'curso' => $curso, 'niveles' => $this->niveles->porCurso($id), 'nivel' => $nivel,
            'adjuntos' => $nivel ? $this->niveles->adjuntos($nivelId) : [],
            'videoDisponible' => $nivel && $this->medios->disponible($nivel['video_url']),
            'csrf' => $csrf, 'error' => $error, 'aviso' => $aviso,
        ]);
    }

    private function guardar(int $cursoId, ?array $nivel): void
    {
        $titulo = is_string($_POST['titulo'] ?? null) ? trim($_POST['titulo']) : '';
        $contenido = $_POST['contenido'] ?? '';
        if ($titulo === '' || mb_strlen($titulo) > 150 || !is_string($contenido) || mb_strlen($contenido) > 5000
            || !in_array($_POST['gratis'] ?? '0', ['0', '1'], true)) {
            throw new DomainException('Escribe un título de hasta 150 caracteres y contenido de hasta 5,000.');
        }

        $video = $pdf = null;
        try {
            $video = $this->medios->guardar($_FILES['video'] ?? null, true);
            if ($video === null && (!$nivel || !$this->medios->disponible($nivel['video_url']))) {
                throw new DomainException('Sube un video MP4 o WebM para el nivel.');
            }
            $pdf = $this->medios->guardar($_FILES['pdf'] ?? null, false);
            $adjunto = null;
            if ($pdf !== null) {
                $nombre = basename(str_replace('\\', '/', (string) $_FILES['pdf']['name']));
                $adjunto = ['nombre' => mb_substr($nombre, 0, 120) ?: 'Material.pdf', 'ruta' => $pdf];
            }
            $anterior = $this->niveles->guardar($cursoId, $nivel ? (int) $nivel['id'] : null, [
                'titulo' => $titulo, 'contenido' => trim($contenido),
                'gratis' => (int) ($_POST['gratis'] ?? 0), 'video' => $video,
            ], $adjunto);
        } catch (Throwable $e) {
            $this->medios->quitar($video);
            $this->medios->quitar($pdf);
            throw $e;
        }
        $this->medios->quitar($anterior);
    }

    private function id(mixed $valor): ?int
    {
        return filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]) ?: null;
    }
}
