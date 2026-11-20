<?php
// Paneles de estudiante, instructor y administrador, y el diploma: reúnen los datos de la base
// y los entregan a las vistas. También atiende las acciones de cada panel (todas con token CSRF).
class PanelControlador extends Controlador
{
    private const CAUSAS = ['Lenguaje ofensivo', 'Spam', 'Contenido irrelevante', 'Otra causa'];

    public function __construct(
        private Usuario $usuarios,
        private Curso $cursos,
        private Categoria $categorias,
        private Inscripcion $inscripciones,
        private Comentario $comentarios,
        private Mensaje $mensajes,
        private Nivel $niveles
    ) {}

    // ------------------------------------------------------------------ Estudiante

    public function estudiante(): void
    {
        $uid = $this->uid();
        $f = [
            'desde'      => $this->fecha($_GET['desde'] ?? ''),
            'hasta'      => $this->fecha($_GET['hasta'] ?? ''),
            'categoria'  => (int) ($_GET['categoria'] ?? 0) ?: null,
            'terminados' => ($_GET['estado'] ?? '') === 'terminado',
            'activos'    => ($_GET['cursos'] ?? '') === 'activos',
        ];
        $todos = $this->inscripciones->kardex($uid);
        $continuar = array_values(array_filter($todos, fn($k) => !$k['completado'] && $k['activo']));
        usort($continuar, fn($a, $b) => strcmp($b['ultima_fecha_acceso'] ?? $b['fecha_inscripcion'], $a['ultima_fecha_acceso'] ?? $a['fecha_inscripcion']));

        $this->vista('estudiante/panel_estudiante', [
            'aviso'      => $this->aviso(),
            'csrf'       => $this->csrf(),
            'resumen'    => $this->inscripciones->resumenAlumno($uid),
            'continuar'  => array_slice($continuar, 0, 5),
            'kardex'     => $this->inscripciones->kardex($uid, $f),
            'filtros'    => $_GET,
            'categorias' => $this->categorias->listar(),
            'perfil'     => $this->usuarios->buscarPorId($uid),
        ] + $this->mensajeria($uid, 'estudiante'));
    }

    // ------------------------------------------------------------------ Instructor

    public function instructor(): void
    {
        $uid = $this->uid();
        $f = [
            'desde'     => $this->fecha($_GET['desde'] ?? ''),
            'hasta'     => $this->fecha($_GET['hasta'] ?? ''),
            'categoria' => (int) ($_GET['categoria'] ?? 0) ?: null,
            'activos'   => ($_GET['cursos'] ?? '') === 'activos',
        ];
        $misCursos = $this->inscripciones->ventasPorCurso($uid);
        $ventas = $this->inscripciones->ventasPorCurso($uid, $f);
        $detalle = $this->inscripciones->ventasDetalle($uid, array_column($ventas, 'curso_id'));

        $porForma = [];
        $total = 0.0;
        foreach ($detalle as $alumnos) {
            foreach ($alumnos as $a) {
                $porForma[$a['forma_pago']] = ($porForma[$a['forma_pago']] ?? 0) + (float) $a['precio_pagado'];
                $total += (float) $a['precio_pagado'];
            }
        }
        $niveles = [];
        foreach ($misCursos as $c) $niveles[$c['curso_id']] = $this->niveles->porCurso((int) $c['curso_id']);

        $this->vista('instructor/panel_instructor', [
            'aviso'      => $this->aviso(),
            'csrf'       => $this->csrf(),
            'resumen'    => $this->inscripciones->resumenInstructor($uid),
            'misCursos'  => $misCursos,
            'niveles'    => $niveles,
            'ventas'     => $ventas,
            'detalle'    => $detalle,
            'porForma'   => $porForma,
            'totalVentas' => $total,
            'filtros'    => $_GET,
            'categorias' => $this->categorias->listar(),
            'perfil'     => $this->usuarios->buscarPorId($uid),
        ] + $this->mensajeria($uid, 'instructor'));
    }

    // Dar de baja (lógica) o reactivar un curso propio.
    public function cursoEstado(): void
    {
        $this->exigirPost('instructor&tab=mis-cursos');
        $curso = $this->cursos->obtenerPorId((int) ($_POST['curso'] ?? 0), false);
        if (!$curso || $curso['id_instructor'] !== $this->uid()) {
            $this->flash('error', 'No encontramos ese curso.', 'instructor&tab=mis-cursos');
        }
        $baja = ($_POST['accion'] ?? '') === 'baja';
        $this->cursos->actualizar($curso['id_curso'], ['activo' => !$baja]);
        $this->flash('ok', $baja
            ? 'Curso dado de baja. Ya no aparece en el catálogo, pero se conserva en tus reportes.'
            : 'Curso reactivado.', 'instructor&tab=mis-cursos');
    }

    // ------------------------------------------------------------------ Administrador

    public function administrador(): void
    {
        $tipo = $_GET['tipo'] ?? '';
        $rep = ($_GET['reporte'] ?? 'instructor') === 'estudiante' ? 'estudiante' : 'instructor';

        $this->vista('administrador/panel_administrador', [
            'aviso'       => $this->aviso(),
            'csrf'        => $this->csrf(),
            'conteos'     => $this->usuarios->conteos(),
            'totalCategorias' => count($this->categorias->listar()),
            'usuarios'    => $this->usuarios->listar($tipo ?: null, trim((string) ($_GET['q'] ?? ''))),
            'comentarios' => $this->comentarios->paraModeracion(),
            'causas'      => self::CAUSAS,
            'global'      => $this->inscripciones->resumenGlobal(),
            'actividad'   => $this->inscripciones->actividadCursos((int) ($_GET['categoria'] ?? 0) ?: null),
            'categorias'  => $this->categorias->listar(),
            'tipoReporte' => $rep,
            'reporte'     => $rep === 'estudiante' ? $this->usuarios->reporteEstudiantes() : $this->usuarios->reporteInstructores(),
            'filtros'     => $_GET,
            'perfil'      => $this->usuarios->buscarPorId($this->uid()),
        ]);
    }

    public function usuarioEstado(): void
    {
        $this->exigirPost('admin&tab=usuarios');
        $bloquear = ($_POST['accion'] ?? '') === 'bloquear';
        $ok = $this->usuarios->cambiarActivo((int) ($_POST['usuario'] ?? 0), !$bloquear);
        $this->flash($ok ? 'ok' : 'error',
            $ok ? ($bloquear ? 'Usuario bloqueado.' : 'Usuario desbloqueado.') : 'No se pudo cambiar el estado de esa cuenta.',
            'admin&tab=usuarios');
    }

    public function comentarioEliminar(): void
    {
        $this->exigirPost('admin&tab=comentarios');
        $causa = (string) ($_POST['causa'] ?? '');
        if (!in_array($causa, self::CAUSAS, true)) $this->flash('error', 'Elige la causa de la eliminación.', 'admin&tab=comentarios');
        $ok = $this->comentarios->eliminar((int) ($_POST['comentario'] ?? 0), $causa);
        $this->flash($ok ? 'ok' : 'error',
            $ok ? 'Comentario eliminado. En el curso se muestra la leyenda de comentario borrado.' : 'Ese comentario ya no se puede eliminar.',
            'admin&tab=comentarios');
    }

    // ------------------------------------------------------------------ Mensajes

    public function mensajeEnviar(): void
    {
        $rol = $_SESSION['usuario']['rol'];
        $panel = $rol === 'instructor' ? 'instructor' : 'estudiante';
        $volver = $panel . '&tab=mensajes';
        $this->exigirPost($volver);

        $para = (int) ($_POST['para'] ?? 0);
        $texto = trim((string) ($_POST['texto'] ?? ''));
        $permitido = array_filter($this->mensajes->contactos($this->uid(), $rol), fn($c) => (int) $c['id'] === $para);

        if (!$permitido || $texto === '' || $this->longitud($texto) > 1000) {
            $this->flash('error', 'Escribe un mensaje de hasta 1000 caracteres para una persona de tus cursos.', $volver);
        }
        $contacto = reset($permitido);
        $this->mensajes->enviar($this->uid(), $para, (int) $contacto['curso_id'], $texto);
        $this->redirigir($volver . '&con=' . $para);
    }

    // ------------------------------------------------------------------ Diploma

    public function diploma(): void
    {
        $cursoId = (int) ($_GET['curso'] ?? 0) ?: null;
        $diploma = $this->inscripciones->terminada($this->uid(), $cursoId);
        if ($diploma) $this->inscripciones->registrarCertificado((int) $diploma['inscripcion_id'], $diploma['instructor']);
        $this->vista('estudiante/diploma', ['diploma' => $diploma]);
    }

    // ------------------------------------------------------------------ Apoyo

    private function mensajeria(int $uid, string $rol): array
    {
        $contactos = $this->mensajes->contactos($uid, $rol);
        $conId = (int) ($_GET['con'] ?? 0);
        $actual = null;
        foreach ($contactos as $c) if ((int) $c['id'] === $conId) $actual = $c;
        $actual ??= $contactos[0] ?? null;
        return [
            'contactos' => $contactos,
            'conActual' => $actual,
            'hilo'      => $actual ? $this->mensajes->hilo($uid, (int) $actual['id']) : [],
        ];
    }

    // Cuenta caracteres (no bytes) sin depender de mbstring.
    private function longitud(string $texto): int { return (int) preg_match_all('/./su', $texto); }

    private function uid(): int { return (int) $_SESSION['usuario']['id']; }

    private function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }

    private function exigirPost(string $volver): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirigir($volver);
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            $this->flash('error', 'La sesión del formulario expiró. Intenta de nuevo.', $volver);
        }
    }

    private function flash(string $tipo, string $mensaje, string $volver): never
    {
        $_SESSION['aviso_panel'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        $this->redirigir($volver);
    }

    private function aviso(): ?array
    {
        $a = $_SESSION['aviso_panel'] ?? null;
        unset($_SESSION['aviso_panel']);
        return $a;
    }

    private function fecha(string $valor): ?string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $valor : null;
    }
}