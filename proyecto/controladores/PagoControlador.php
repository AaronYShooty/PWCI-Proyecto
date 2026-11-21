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
        $cursoId = filter_var($_POST['curso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirigir('curso&id=' . $cursoId);
        if (!$this->csrfValido($_POST['csrf'] ?? null)) {
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
        $cursoId = filter_var($_GET['curso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        $usuarioId = (int) $_SESSION['usuario']['id'];
        $curso = $cursoId > 0 ? $this->cursos->obtenerPorId($cursoId) : null;
        if (!$curso) $this->aviso('error', 'El curso no existe o ya no está disponible.', 0);
        if ($this->inscripciones->obtener($usuarioId, $cursoId)) $this->aviso('ok', 'Ya estás inscrito en este curso.', $cursoId);
        if ((float) $curso['precio'] <= 0) $this->aviso('error', 'Este curso es gratis, no requiere pago.', $cursoId);

        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $llavePublica = Entorno::obtener('STRIPE_PUBLISHABLE_KEY');
        $error = null;
        $secreto = null;
        $pendiente = false;
        $vale = null;

        if ($llavePublica === '') {
            $error = 'El sistema de pagos todavía no está configurado.';
        } else {
            try {
                $orden = $this->inscripciones->prepararOrden($usuarioId, $cursoId, (float) $curso['precio']);
                $curso['precio'] = (float) $orden['monto'];
                $cobro = $orden['stripe_intent'] ? $this->pasarela->obtenerIntento($orden['stripe_intent']) : null;
                if (($cobro['estado'] ?? '') === 'canceled') {
                    $this->inscripciones->reiniciarOrden($orden);
                    $this->redirigir('pago&curso=' . $cursoId);
                }
                if ($cobro === null) {
                    $cobro = $this->pasarela->crearIntento($curso, $usuarioId, $orden['clave']);
                    $this->inscripciones->asociarOrden($orden, $cobro['id']);
                }
                if ($cobro['estado'] === 'succeeded') {
                    $orden['stripe_intent'] = $cobro['id'];
                    $this->registrar($orden, $cobro);
                    $this->aviso('ok', '¡Pago confirmado! Ya tienes acceso al curso.', $cursoId);
                }
                $secreto = $cobro['secreto'];
                $pendiente = $this->pagoPendiente($cobro);
                $vale = $cobro['vale'];
            } catch (DomainException $e) {
                $error = $e->getMessage();
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
            'pendiente'    => $pendiente,
            'vale'         => $vale,
            'csrf'         => $_SESSION['csrf'],
            'urlRetorno'   => $this->urlBase() . '?ruta=pago-retorno',
            'urlConfirmar' => 'index.php?ruta=pago-confirmar',
        ]);
    }

    // POST (fetch, JSON): el navegador avisa que Stripe cobró; el servidor lo verifica con Stripe.
    public function confirmar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            $this->respuesta(false, 'Método no permitido.', 405);
        }
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            $this->respuesta(false, 'Envía un cuerpo en formato JSON.', 415);
        }
        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->csrfValido($token)) {
            $this->respuesta(false, 'La sesión del formulario expiró. Recarga la página.', 403);
        }
        $r = $this->verificarYRegistrar($cuerpo['payment_intent'] ?? null);
        if ($r['codigo'] === 200) $_SESSION['aviso_pago'] = ['tipo' => 'ok', 'mensaje' => $r['mensaje']];
        $this->respuesta($r['codigo'] < 400, $r['mensaje'], $r['codigo'], [
            'redirigir' => 'index.php?ruta=curso&id=' . $r['curso_id'],
            'pendiente' => $r['codigo'] === 202, 'vale' => $r['vale'],
        ]);
    }

    // GET: regreso de Stripe cuando un método de pago necesitó redirección.
    public function retorno(): void
    {
        $r = $this->verificarYRegistrar($_GET['payment_intent'] ?? null);
        if ($r['codigo'] === 202) $this->redirigir('pago&curso=' . $r['curso_id']);
        $this->aviso($r['codigo'] === 200 ? 'ok' : 'error', $r['mensaje'], $r['curso_id']);
    }

    public function webhook(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            $this->respuesta(false, 'Método no permitido.', 405);
        }
        $secreto = Entorno::obtener('STRIPE_WEBHOOK_SECRET');
        if ($secreto === '') $this->respuesta(false, 'Webhook sin configurar.', 503);

        $cuerpo = (string) file_get_contents('php://input');
        $cabecera = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $marca = 0;
        $firmas = [];
        foreach (explode(',', $cabecera) as $parte) {
            [$nombre, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');
            if ($nombre === 't' && ctype_digit($valor)) $marca = (int) $valor;
            if ($nombre === 'v1') $firmas[] = $valor;
        }
        $esperada = hash_hmac('sha256', $marca . '.' . $cuerpo, $secreto);
        $valida = false;
        foreach ($firmas as $firma) if (hash_equals($esperada, $firma)) $valida = true;
        if (!$valida || abs(time() - $marca) > 300) $this->respuesta(false, 'Firma no válida.', 400);

        $evento = json_decode($cuerpo, true);
        if (!is_array($evento)) $this->respuesta(false, 'Evento no válido.', 400);
        $id = $evento['data']['object']['id'] ?? null;
        if (($evento['type'] ?? null) === 'payment_intent.succeeded' && $this->intentoValido($id)) {
            try {
                $orden = $this->inscripciones->ordenPorIntento($id);
                if ($orden !== null) $this->registrar($orden, $this->pasarela->obtenerIntento($id));
            } catch (Throwable $e) {
                error_log('Stripe webhook: ' . $e->getMessage());
                $this->respuesta(false, 'No se pudo registrar el pago.', 500);
            }
        }
        $this->respuesta(true, 'Evento recibido.', 200);
    }

    private function verificarYRegistrar(mixed $intento): array
    {
        $resultado = fn(int $codigo, string $mensaje, int $curso = 0, ?string $vale = null) => [
            'codigo' => $codigo, 'mensaje' => $mensaje, 'curso_id' => $curso, 'vale' => $vale,
        ];
        if (!$this->intentoValido($intento)) return $resultado(400, 'Pago no reconocido.');

        try {
            $orden = $this->inscripciones->ordenPorIntento($intento);
            if (!$orden || (int) $orden['usuario_id'] !== (int) $_SESSION['usuario']['id']) {
                return $resultado(404, 'Pago no reconocido.');
            }
            $cursoId = (int) $orden['curso_id'];
            $cobro = $this->pasarela->obtenerIntento($intento);
            if ($this->pagoPendiente($cobro)) {
                return $resultado(202, 'El pago sigue pendiente. Consulta su estado cuando hayas pagado.', $cursoId, $cobro['vale']);
            }
            if ($cobro['estado'] !== 'succeeded') return $resultado(400, 'El pago no se completó.', $cursoId);
            $this->registrar($orden, $cobro);
            return $resultado(200, '¡Pago confirmado! Ya tienes acceso al curso.', $cursoId);
        } catch (Throwable $e) {
            error_log('Stripe: ' . $e->getMessage());
            return $resultado(502, 'No pudimos confirmar tu pago. Consulta el estado antes de volver a pagar.');
        }
    }

    private function registrar(array $orden, array $cobro): void
    {
        $formas = ['card' => 'Tarjeta (Stripe)', 'oxxo' => 'Efectivo en OXXO (Stripe)'];
        $forma = $formas[$cobro['metodo']] ?? null;
        if ($cobro['estado'] !== 'succeeded' || !$forma || $cobro['id'] !== $orden['stripe_intent']
            || $cobro['usuario_id'] !== (int) $orden['usuario_id'] || $cobro['curso_id'] !== (int) $orden['curso_id']
            || $cobro['moneda'] !== 'mxn' || (int) round($cobro['monto'] * 100) !== (int) round($orden['monto'] * 100)) {
            throw new RuntimeException('Los datos del pago no coinciden con la orden.');
        }
        if (!$this->inscripciones->compraRegistrada($cobro['id'])) {
            $this->inscripciones->registrarCompra($cobro['usuario_id'], $cobro['curso_id'], $cobro['id'], $cobro['monto'], $cobro['moneda'], $forma);
        }
    }

    private function intentoValido(mixed $intento): bool
    {
        return is_string($intento) && preg_match('/^pi_[A-Za-z0-9_]{10,200}$/D', $intento);
    }

    private function pagoPendiente(array $cobro): bool
    {
        return $cobro['estado'] === 'processing' || ($cobro['estado'] === 'requires_action' && $cobro['metodo'] === 'oxxo');
    }

    private function csrfValido(mixed $token): bool
    {
        return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
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
