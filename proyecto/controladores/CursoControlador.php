<?php
class CursoControlador extends Controlador
{
    public function __construct(
        private Curso $cursos,
        private Inscripcion $inscripciones,
        private Nivel $niveles,
        private Comentario $comentarios,
        private MediosCurso $medios
    ) {}

    public function inicio(): void
    {
        $this->vista('publico/inicio');
    }

    public function detalle(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $curso = $id ? $this->cursos->obtenerPorId($id, false) : null;
        if (!$curso) $this->redirigir('inicio');

        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $aviso = $_SESSION['aviso_pago'] ?? null;
        unset($_SESSION['aviso_pago']);

        $usuario = $_SESSION['usuario'];
        $insc = $usuario['rol'] === 'estudiante' ? $this->inscripciones->obtener($usuario['id'], $id) : null;
        $gestiona = $usuario['rol'] === 'administrador' || $usuario['id'] === $curso['id_instructor'];
        if (!$curso['activo'] && !$gestiona && !$insc) $this->redirigir('inicio');

        $vistos = $insc ? $this->niveles->vistos((int) $insc['id']) : [];
        $niveles = $this->niveles->porCurso($id);
        $nivelSolicitado = filter_var($_GET['nivel'] ?? null, FILTER_VALIDATE_INT);
        $seleccion = $siguiente = $primero = null;
        foreach ($niveles as &$nivel) {
            $nivel['disponible'] = Nivel::puedeVer($nivel + [
                'instructor_id' => $curso['id_instructor'], 'activo' => $curso['activo'],
            ], $usuario, $insc !== null) && $this->medios->disponible($nivel['video_url']);
            if (!$nivel['disponible']) continue;
            $primero ??= $nivel;
            if (!in_array((int) $nivel['id'], $vistos, true)) $siguiente ??= $nivel;
            if ((int) $nivel['id'] === $nivelSolicitado) $seleccion = $nivel;
        }
        unset($nivel);
        $seleccion ??= $siguiente ?? $primero;
        $comentarios = $this->comentarios->porCurso($id);
        $puedeResenar = $insc && $insc['completado']
            && !in_array($usuario['id'], array_map('intval', array_column($comentarios, 'usuario_id')), true);

        $this->vista('publico/curso', [
            'curso'       => $curso,
            'inscrito'    => $insc !== null,
            'completado'  => (bool) ($insc['completado'] ?? false),
            'aviso'       => $aviso,
            'csrf'        => $_SESSION['csrf'],
            'niveles'     => $niveles,
            'seleccion'   => $seleccion,
            'adjuntos'    => $seleccion ? $this->niveles->adjuntos((int) $seleccion['id']) : [],
            'vistos'      => $vistos,
            'comentarios' => $comentarios,
            'puedeResenar' => $puedeResenar,
            'gestiona' => $gestiona,
        ]);
    }

    public function crear(): void
    {
        $curso = null;
        if (isset($_GET['curso'])) {
            $id = filter_var($_GET['curso'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $curso = $id ? $this->cursos->obtenerPorId($id, false) : null;
            if (!$curso || $curso['id_instructor'] !== $_SESSION['usuario']['id']) $this->redirigir('instructor');
        }
        $this->vista('instructor/crear_curso', ['curso' => $curso]);
    }
}
