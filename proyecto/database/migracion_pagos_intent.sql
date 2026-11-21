-- Migración: el pago ya no usa Checkout hospedado, usa PaymentIntent en nuestra página.
-- Ejecutar UNA VEZ sobre la base walteredu ya existente (no borra datos).
USE walteredu;
ALTER TABLE pagos MODIFY stripe_session_id VARCHAR(255) NULL;
ALTER TABLE pagos ADD UNIQUE KEY uq_pagos_intent (stripe_payment_intent);