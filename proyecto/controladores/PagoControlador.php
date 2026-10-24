<?php
class PagoControlador extends Controlador
{
    public function __construct(
        private Curso $cursos,
        private Inscripcion $inscripciones,
        private PasarelaPago $pasarela
    ) {}

    // POST: inicia la compra. Gratis → inscribe directo; con costo → manda a Stripe Checkout.
    public function iniciar(): void
    {
        $cursoId = (int) ($_POST['curso'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirigir('curso&id=' . $cursoId);
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            $this->aviso('error', 'La sesión del formulario expiró. Intenta de nuevo.', $cursoId);
        }

        $usuarioId = (int) $_SESSION['usuario']['id'];
        $curso = $cursoId > 0 ? $this->cursos->obtenerPorId($cursoId) : null;   // solo cursos activos
        if (!$curso) $this->aviso('error', 'El curso no existe o ya no está disponible.', 0);

        if ($this->inscripciones->obtener($usuarioId, $cursoId)) {
            $this->aviso('ok', 'Ya estás inscrito en este curso.', $cursoId);
        }

        if ((float) $curso['precio'] <= 0) {
            $this->inscripciones->inscribirGratis($usuarioId, $cursoId);
            $this->aviso('ok', '¡Listo! Te inscribiste al curso gratis.', $cursoId);
        }

        try {
            $base = $this->urlBase();
            $sesion = $this->pasarela->crearSesion(
                $curso, $usuarioId,
                $base . '?ruta=pago-exito&session_id={CHECKOUT_SESSION_ID}',
                $base . '?ruta=pago-cancelado&curso=' . $cursoId
            );
        } catch (Throwable $e) {
            error_log('Stripe: ' . $e->getMessage());
            $this->aviso('error', 'No pudimos iniciar el pago. Intenta de nuevo en unos minutos.' . $this->detalleDepuracion($e), $cursoId);
        }

        header('Location: ' . $sesion['url'], true, 303);
        exit;
    }

    // Stripe regresa aquí: se confirma el cobro en el servidor antes de dar acceso.
    public function exito(): void
    {
        $sesionId = (string) ($_GET['session_id'] ?? '');
        if (!preg_match('/^cs_[A-Za-z0-9_]{10,200}$/', $sesionId)) $this->redirigir('inicio');

        $usuarioId = (int) $_SESSION['usuario']['id'];

        try {
            $previa = $this->inscripciones->compraRegistrada($sesionId);
            $sesion = $this->pasarela->obtenerSesion($sesionId);
        } catch (Throwable $e) {
            error_log('Stripe: ' . $e->getMessage());
            $this->aviso('error', 'No pudimos confirmar tu pago. Si te lo cobraron, contáctanos.' . $this->detalleDepuracion($e), 0);
        }

        if ($sesion['usuario_id'] !== $usuarioId) $this->redirigir('inicio');      // la sesión es de otro usuario
        if (!$sesion['pagado']) $this->aviso('error', 'El pago no se completó. No se te cobró.', $sesion['curso_id']);
        if (!$this->cursos->obtenerPorId($sesion['curso_id'], false)) $this->redirigir('inicio');

        if (!$previa) {
            $this->inscripciones->registrarCompra(
                $usuarioId, $sesion['curso_id'], $sesion['id'], $sesion['intento'], $sesion['monto'], $sesion['moneda']
            );
        }
        $this->aviso('ok', '¡Pago confirmado! Ya tienes acceso al curso.', $sesion['curso_id']);
    }

    public function cancelado(): void
    {
        $this->aviso('error', 'Cancelaste el pago. No se te cobró nada.', (int) ($_GET['curso'] ?? 0));
    }

    private function aviso(string $tipo, string $mensaje, int $cursoId): never
    {
        $_SESSION['aviso_pago'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        $this->redirigir($cursoId > 0 ? 'curso&id=' . $cursoId : 'inicio');
    }

    // Con APP_DEBUG=1 en .env se muestra el motivo real del fallo (quitar al entregar).
    private function detalleDepuracion(Throwable $e): string
    {
        return Entorno::obtener('APP_DEBUG') === '1' ? ' [Detalle: ' . $e->getMessage() . ']' : '';
    }

    private function urlBase(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $https . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    }
}