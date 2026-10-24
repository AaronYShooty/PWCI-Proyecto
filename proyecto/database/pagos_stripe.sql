-- Pagos con Stripe (Checkout). Ejecutar una vez sobre la BD existente.
USE walteredu;
CREATE TABLE IF NOT EXISTS pagos (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id         INT UNSIGNED NOT NULL,
  stripe_session_id      VARCHAR(255) NOT NULL UNIQUE,
  stripe_payment_intent  VARCHAR(255) NULL,
  monto                  DECIMAL(10,2) NOT NULL,
  moneda                 CHAR(3) NOT NULL DEFAULT 'mxn',
  fecha_pago             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
) ENGINE=InnoDB;