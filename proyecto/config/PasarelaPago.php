<?php
// Contrato de la pasarela de pago: el controlador depende de esta interfaz,
// así se puede sustituir Stripe por otra implementación sin tocar el controlador.
interface PasarelaPago
{
    /** Crea el cobro del curso y devuelve ['id' => ..., 'secreto' => ...] para pagar en nuestra página. */
    public function crearIntento(array $curso, int $usuarioId): array;

    /** Consulta un cobro: ['id','pagado','monto','moneda','curso_id','usuario_id']. */
    public function obtenerIntento(string $id): array;
}