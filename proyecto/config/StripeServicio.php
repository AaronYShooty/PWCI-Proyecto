<?php
// Integración con la API de Stripe (PaymentIntents) por cURL, sin SDK ni Composer.
// La llave secreta se lee de .env (STRIPE_SECRET_KEY) y nunca se sube al repositorio.
class StripeServicio implements PasarelaPago
{
    private const MONEDA = 'mxn';
    // Los métodos de pago los define el Dashboard de Stripe (Settings → Payment methods); la API ya no acepta payment_method_types.

    public function __construct(
        private ?string $llave = null,
        private string $urlBase = 'https://api.stripe.com'
    ) {
        $this->llave = $llave ?? Entorno::obtener('STRIPE_SECRET_KEY');
    }

    // Crea un PaymentIntent: el cobro se completa en NUESTRA página con Stripe Elements,
    // los datos de la tarjeta los captura Stripe en sus campos seguros y nunca pasan por nuestro servidor.
    public function crearIntento(array $curso, int $usuarioId): array
    {
        $r = $this->peticion('POST', '/v1/payment_intents', [
            'amount'      => (int) round($curso['precio'] * 100),
            'currency'    => self::MONEDA,
            'description' => $curso['titulo'],
            'automatic_payment_methods' => ['enabled' => 'true', 'allow_redirects' => 'never'],
            'metadata'    => ['curso_id' => (string) $curso['id_curso'], 'usuario_id' => (string) $usuarioId],
        ]);
        if (empty($r['id']) || empty($r['client_secret'])) throw new RuntimeException('Respuesta inesperada de Stripe.');
        return ['id' => $r['id'], 'secreto' => $r['client_secret']];
    }

    public function obtenerIntento(string $id): array
    {
        $r = $this->peticion('GET', '/v1/payment_intents/' . rawurlencode($id));
        return [
            'id'         => $r['id'] ?? '',
            'pagado'     => ($r['status'] ?? '') === 'succeeded',
            'monto'      => round(($r['amount_received'] ?? 0) / 100, 2),
            'moneda'     => strtolower($r['currency'] ?? ''),
            'curso_id'   => (int) ($r['metadata']['curso_id'] ?? 0),
            'usuario_id' => (int) ($r['metadata']['usuario_id'] ?? 0),
        ];
    }

    private function peticion(string $metodo, string $ruta, array $datos = []): array
    {
        if ($this->llave === '' || $this->llave === null) {
            throw new RuntimeException('Falta STRIPE_SECRET_KEY en el archivo .env.');
        }
        $ch = curl_init($this->urlBase . $ruta);
        $opciones = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERPWD        => $this->llave . ':',
            CURLOPT_CUSTOMREQUEST  => $metodo,
        ];
        if ($metodo === 'POST') $opciones[CURLOPT_POSTFIELDS] = http_build_query($datos);
        if (($bundle = $this->certificados()) !== null) $opciones[CURLOPT_CAINFO] = $bundle;
        curl_setopt_array($ch, $opciones);

        $cuerpo = curl_exec($ch);
        if ($cuerpo === false) {
            $detalle = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('No se pudo contactar a Stripe: ' . $detalle);
        }
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($cuerpo, true);
        if (!is_array($json)) throw new RuntimeException("Stripe devolvió una respuesta no válida (HTTP $codigo).");
        if ($codigo >= 400) {
            throw new RuntimeException('Stripe rechazó la petición: ' . ($json['error']['message'] ?? "HTTP $codigo"));
        }
        return $json;
    }

    // En XAMPP/Windows cURL suele no traer certificados raíz: se busca un bundle sin desactivar la verificación SSL.
    private function certificados(): ?string
    {
        $candidatos = [
            Entorno::obtener('CURL_CA_BUNDLE'),
            (string) ini_get('curl.cainfo'),
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
        ];
        foreach ($candidatos as $ruta) {
            if ($ruta !== '' && is_file($ruta)) return $ruta;
        }
        return null;
    }
}