<?php
class Inscripcion
{
    public function __construct(private PDO $db) {}

    public function obtener(int $usuarioId, int $cursoId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM inscripciones WHERE usuario_id = :u AND curso_id = :c');
        $st->execute(['u' => $usuarioId, 'c' => $cursoId]);
        return $st->fetch() ?: null;
    }

    // Curso gratis: inscripción sin pago.
    public function inscribirGratis(int $usuarioId, int $cursoId): int
    {
        $this->db->prepare(
            'INSERT IGNORE INTO inscripciones (usuario_id, curso_id, forma_pago_id, precio_pagado)
             VALUES (:u, :c, NULL, 0)'
        )->execute(['u' => $usuarioId, 'c' => $cursoId]);
        return (int) $this->obtener($usuarioId, $cursoId)['id'];
    }

    // ¿Ya se registró este cobro de Stripe? (hace seguro recargar la página de éxito)
    public function compraRegistrada(string $sesionStripe): ?array
    {
        $st = $this->db->prepare('SELECT * FROM pagos WHERE stripe_session_id = :s');
        $st->execute(['s' => $sesionStripe]);
        return $st->fetch() ?: null;
    }

    // Registra la inscripción y el pago en una sola transacción.
    public function registrarCompra(int $usuarioId, int $cursoId, string $sesionStripe, ?string $intento, float $monto, string $moneda): int
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT id FROM inscripciones WHERE usuario_id = :u AND curso_id = :c FOR UPDATE');
            $st->execute(['u' => $usuarioId, 'c' => $cursoId]);
            $id = $st->fetchColumn();

            if ($id === false) {
                $this->db->prepare(
                    "INSERT INTO inscripciones (usuario_id, curso_id, forma_pago_id, precio_pagado)
                     VALUES (:u, :c, (SELECT id FROM formas_pago WHERE nombre = 'Tarjeta (Stripe)'), :p)"
                )->execute(['u' => $usuarioId, 'c' => $cursoId, 'p' => $monto]);
                $id = $this->db->lastInsertId();
            }

            $this->db->prepare(
                'INSERT INTO pagos (inscripcion_id, stripe_session_id, stripe_payment_intent, monto, moneda)
                 VALUES (:i, :s, :pi, :m, :mo)'
            )->execute(['i' => $id, 's' => $sesionStripe, 'pi' => $intento, 'm' => $monto, 'mo' => $moneda]);

            $this->db->commit();
            return (int) $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            if ($e->getCode() === '23000') {                 // la sesión ya estaba registrada (doble clic / recarga)
                $previa = $this->compraRegistrada($sesionStripe);
                if ($previa) return (int) $previa['inscripcion_id'];
            }
            throw $e;
        }
    }
}