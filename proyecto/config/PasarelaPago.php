<?php
// Contrato de la pasarela de pago: el controlador depende de esta interfaz,
// así se puede sustituir Stripe por otra implementación sin tocar el controlador.
interface PasarelaPago
{
    /** Crea una sesión de cobro y devuelve ['id' => ..., 'url' => ...]. */
    public function crearSesion(array $curso, int $usuarioId, string $urlExito, string $urlCancelado): array;

    /** Consulta una sesión ya creada: ['id','pagado','monto','moneda','intento','curso_id','usuario_id']. */
    public function obtenerSesion(string $id): array;
}