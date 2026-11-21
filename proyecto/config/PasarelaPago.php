<?php
// Contrato de la pasarela de pago: el controlador depende de esta interfaz,
// así se puede sustituir Stripe por otra implementación sin tocar el controlador.
interface PasarelaPago
{
    /** Devuelve los datos del intento y permite repetir la creación con la misma clave. */
    public function crearIntento(array $curso, int $usuarioId, string $clave): array;

    /** Devuelve el estado, método, importe, moneda y referencias del cobro. */
    public function obtenerIntento(string $id): array;
}
