<?php
class CursoControlador extends Controlador
{
    public function __construct(private Curso $cursos, private ?Inscripcion $inscripciones = null) {}

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
        if (($_SESSION['usuario']['rol'] ?? '') === 'estudiante' && $this->inscripciones) {
            $inscrito = $this->inscripciones->obtener((int) $_SESSION['usuario']['id'], (int) $curso['id_curso']) !== null;
        }

        $this->vista('publico/curso', ['curso' => $curso, 'inscrito' => $inscrito, 'aviso' => $aviso, 'csrf' => $_SESSION['csrf']]);
    }

    public function crear(): void         { $this->vista('instructor/crear_curso'); }
    public function diploma(): void       { $this->vista('estudiante/diploma'); }
}