<?php
class CursoControlador extends Controlador
{
    public function __construct(
        private Curso $cursos,
        private ?Inscripcion $inscripciones = null,
        private ?Nivel $niveles = null,
        private ?Comentario $comentarios = null
    ) {}

    public function inicio(): void
    {
        $this->vista('publico/inicio');
    }

    public function detalle(): void
    {
        $curso = $this->cursos->obtenerPorId((int) ($_GET['id'] ?? 0));
        if (!$curso) $this->redirigir('inicio');

        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $aviso = $_SESSION['aviso_pago'] ?? null;
        unset($_SESSION['aviso_pago']);

        $inscrito = false;
        $vistos = [];
        if (($_SESSION['usuario']['rol'] ?? '') === 'estudiante' && $this->inscripciones) {
            $insc = $this->inscripciones->obtener((int) $_SESSION['usuario']['id'], (int) $curso['id_curso']);
            $inscrito = $insc !== null;
            if ($insc && $this->niveles) $vistos = $this->niveles->vistos((int) $insc['id']);
        }

        $this->vista('publico/curso', [
            'curso'       => $curso,
            'inscrito'    => $inscrito,
            'aviso'       => $aviso,
            'csrf'        => $_SESSION['csrf'],
            'niveles'     => $this->niveles ? $this->niveles->porCurso((int) $curso['id_curso']) : [],
            'vistos'      => $vistos,
            'comentarios' => $this->comentarios ? $this->comentarios->porCurso((int) $curso['id_curso']) : [],
        ]);
    }

    public function crear(): void { $this->vista('instructor/crear_curso'); }
}