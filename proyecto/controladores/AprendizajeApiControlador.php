<?php
class AprendizajeApiControlador extends ApiControlador
{
    public function __construct(
        private Nivel $niveles,
        private Inscripcion $inscripciones,
        private Comentario $comentarios,
        private Curso $cursos,
        private MediosCurso $medios
    ) {}

    public function atender(string $recurso, int $id, string $metodo): void
    {
        $permitidos = match ($recurso) {
            'video', 'adjunto' => ['GET', 'HEAD'],
            'avance' => ['PUT'],
            'resena' => ['POST'],
            default => [],
        };
        if (!$permitidos || $id <= 0) {
            $this->fallo('Recurso no encontrado.', 404);
            return;
        }
        if (!in_array($metodo, $permitidos, true)) {
            header('Allow: ' . implode(', ', $permitidos));
            $this->fallo('Método no permitido.', 405);
            return;
        }
        if ($recurso === 'resena') {
            $this->resena($id);
            return;
        }

        $adjunto = $recurso === 'adjunto' ? $this->niveles->obtenerAdjunto($id) : null;
        $nivelId = $recurso === 'adjunto' ? (int) ($adjunto['nivel_id'] ?? 0) : $id;
        $nivel = $nivelId ? $this->niveles->obtener($nivelId) : null;
        if (!$nivel) {
            $this->fallo('Recurso no encontrado.', 404);
            return;
        }
        $usuario = $_SESSION['usuario'];
        $cursoId = (int) $nivel['curso_id'];
        $inscripcion = $usuario['rol'] === 'estudiante'
            ? $this->inscripciones->obtener((int) $usuario['id'], $cursoId) : null;
        if (!Nivel::puedeVer($nivel, $usuario, $inscripcion !== null) || ($recurso === 'avance' && !$inscripcion)) {
            $this->fallo('Necesitas acceso al curso para continuar.', 403);
            return;
        }

        if ($recurso === 'avance') {
            $cuerpo = $this->leerJson();
            if ($cuerpo === null) return;
            if (($cuerpo['completado'] ?? null) !== true) {
                $this->fallo('Envía completado con valor verdadero.', 400);
                return;
            }
            if (!$this->medios->disponible($nivel['video_url'])) {
                $this->fallo('El video del nivel aún no está disponible.', 404);
                return;
            }
            $avance = $this->inscripciones->completarNivel((int) $usuario['id'], $cursoId, $nivelId);
            if ($avance === null) {
                $this->fallo('El nivel ya no está disponible.', 404);
                return;
            }
            $this->exito('Avance guardado.', $avance + ['curso_id' => $cursoId, 'nivel_id' => $nivelId]);
            return;
        }

        if ($recurso === 'video' && $metodo === 'GET' && $inscripcion) {
            $this->inscripciones->registrarAcceso((int) $inscripcion['id']);
        }
        $ruta = $adjunto ? $adjunto['ruta'] : $nivel['video_url'];
        if (!$this->medios->enviar($ruta, $adjunto['nombre'] ?? null)) {
            $this->fallo('El archivo no está disponible.', 404);
        }
    }

    private function resena(int $cursoId): void
    {
        $cuerpo = $this->leerJson();
        if ($cuerpo === null) return;
        $texto = $cuerpo['texto'] ?? null;
        $calificacion = $cuerpo['calificacion'] ?? null;
        if (!$this->textoValido($texto, 2000) || !is_int($calificacion) || $calificacion < 1 || $calificacion > 5) {
            $this->fallo('Escribe una reseña de hasta 2,000 caracteres y elige de 1 a 5 estrellas.', 400);
            return;
        }
        $curso = $this->cursos->obtenerPorId($cursoId, false);
        if (!$curso) {
            $this->fallo('Curso no encontrado.', 404);
            return;
        }
        $usuario = (int) $_SESSION['usuario']['id'];
        $inscripcion = $this->inscripciones->obtener($usuario, $cursoId);
        if (!$inscripcion || !$inscripcion['completado']) {
            $this->fallo('Completa el curso para poder publicar una reseña.', 403);
            return;
        }
        if (!$this->comentarios->publicar($cursoId, $usuario, trim($texto), $calificacion)) {
            $this->fallo('Ya publicaste una reseña de este curso.', 409);
            return;
        }
        $this->exito('Reseña publicada correctamente.', ['curso_id' => $cursoId], 201);
    }
}
