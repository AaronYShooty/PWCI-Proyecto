<?php
// Pago dentro de nuestra propia página. El servidor consume la API de Stripe (PaymentIntents);
// los datos de la tarjeta los captura Stripe Elements en el navegador y nunca pasan por nuestro servidor.
class PagoControlador extends Controlador
{
    public function __construct(
        private Curso $cursos,
        private Inscripcion $inscripciones,
        private PasarelaPago $pasarela
    ) {}

    // POST desde el detalle del curso: gratis → inscribe directo; con costo → página de pago.
    public function comprar(): void
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
        $this->redirigir('pago&curso=' . $cursoId);
    }

    // GET: nuestra página de pago.
    public function formulario(): void
    {
        $cursoId = (int) ($_GET['curso'] ?? 0);
        $usuarioId = (int) $_SESSION['usuario']['id'];
        $curso = $cursoId > 0 ? $this->cursos->obtenerPorId($cursoId) : null;
        if (!$curso) $this->aviso('error', 'El curso no existe o ya no está disponible.', 0);
        if ($this->inscripciones->obtener($usuarioId, $cursoId)) $this->aviso('ok', 'Ya estás inscrito en este curso.', $cursoId);
        if ((float) $curso['precio'] <= 0) $this->aviso('error', 'Este curso es gratis, no requiere pago.', $cursoId);

        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $llavePublica = Entorno::obtener('STRIPE_PUBLISHABLE_KEY');
        $error = null;
        $secreto = null;

        if ($llavePublica === '') {
            $error = 'Falta STRIPE_PUBLISHABLE_KEY en el archivo .env.';
        } else {
            try {
                $secreto = $this->pasarela->crearIntento($curso, $usuarioId)['secreto'];
            } catch (Throwable $e) {
                error_log('Stripe: ' . $e->getMessage());
                $error = 'No pudimos preparar el pago. Intenta de nuevo en unos minutos.' . $this->detalleDepuracion($e);
            }
        }

        $this->vista('estudiante/pago', [
            'curso'        => $curso,
            'llavePublica' => $llavePublica,
            'secreto'      => $secreto,
            'error'        => $error,
            'csrf'         => $_SESSION['csrf'],
            'urlRetorno'   => $this->urlBase() . '?ruta=pago-retorno',
            'urlConfirmar' => 'index.php?ruta=pago-confirmar',
        ]);
    }

    // POST (fetch, JSON): el navegador avisa que Stripe cobró; el servidor lo verifica con Stripe.
    public function confirmar(): void
    {
        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
            $this->respuesta(false, 'La sesión del formulario expiró. Recarga la página.', 403);
        }
        $intento = is_array($cuerpo) ? (string) ($cuerpo['payment_intent'] ?? '') : '';

        $r = $this->verificarYRegistrar($intento);
        if ($r['ok']) $_SESSION['aviso_pago'] = ['tipo' => 'ok', 'mensaje' => $r['mensaje']];
        $this->respuesta($r['ok'], $r['mensaje'], $r['ok'] ? 200 : 400,
            ['redirigir' => 'index.php?ruta=curso&id=' . $r['curso_id']]);
    }

    // GET: regreso de Stripe cuando un método de pago necesitó redirección.
    public function retorno(): void
    {
        $r = $this->verificarYRegistrar((string) ($_GET['payment_intent'] ?? ''));
        $this->aviso($r['ok'] ? 'ok' : 'error', $r['mensaje'], $r['curso_id']);
    }

    // Confirma con Stripe que el cobro se completó y, solo entonces, inscribe al estudiante.
    private function verificarYRegistrar(string $intento): array
    {
        $fallo = fn(string $m, int $curso = 0) => ['ok' => false, 'mensaje' => $m, 'curso_id' => $curso];
        if (!preg_match('/^pi_[A-Za-z0-9_]{10,200}$/', $intento)) return $fallo('Pago no reconocido.');

        $usuarioId = (int) $_SESSION['usuario']['id'];
        try {
            $previo = $this->inscripciones->compraRegistrada($intento);
            $cobro = $this->pasarela->obtenerIntento($intento);
        } catch (Throwable $e) {
            error_log('Stripe: ' . $e->getMessage());
            return $fallo('No pudimos confirmar tu pago. Si te lo cobraron, contáctanos.' . $this->detalleDepuracion($e));
        }

        if ($cobro['usuario_id'] !== $usuarioId) return $fallo('Pago no reconocido.');          // cobro de otro usuario
        if (!$cobro['pagado']) return $fallo('El pago no se completó. No se te cobró.', $cobro['curso_id']);
        if (!$this->cursos->obtenerPorId($cobro['curso_id'], false)) return $fallo('Pago no reconocido.');

        if (!$previo) {
            $this->inscripciones->registrarCompra($usuarioId, $cobro['curso_id'], $cobro['id'], $cobro['monto'], $cobro['moneda']);
        }
        return ['ok' => true, 'mensaje' => '¡Pago confirmado! Ya tienes acceso al curso.', 'curso_id' => $cobro['curso_id']];
    }

    // Responde JSON y TERMINA la petición: ninguna validación fallida puede seguir ejecutando código.
    private function respuesta(bool $ok, string $mensaje, int $codigo, array $datos = []): never
    {
        $this->json(['exito' => $ok, 'mensaje' => $mensaje, 'datos' => $ok ? $datos : [], 'errores' => []], $codigo);
        exit;
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