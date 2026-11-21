-- Actualiza una base existente de la rama pago-propio-y-paneles sin borrar datos.
USE walteredu;

SET @agregar_gratis = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'niveles' AND COLUMN_NAME = 'gratis'),
  'SELECT 1',
  'ALTER TABLE niveles ADD COLUMN gratis TINYINT(1) NULL AFTER precio'
);
PREPARE migracion FROM @agregar_gratis;
EXECUTE migracion;
DEALLOCATE PREPARE migracion;
UPDATE niveles SET gratis = (precio = 0) WHERE gratis IS NULL;
ALTER TABLE niveles MODIFY COLUMN gratis TINYINT(1) NOT NULL DEFAULT 0;

SET @agregar_fecha_creacion = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'cursos' AND COLUMN_NAME = 'fecha_creacion'),
  'SELECT 1',
  'ALTER TABLE cursos ADD COLUMN fecha_creacion DATETIME NULL'
);
PREPARE migracion FROM @agregar_fecha_creacion;
EXECUTE migracion;
DEALLOCATE PREPARE migracion;
UPDATE cursos SET fecha_creacion = COALESCE(fecha_publicacion, NOW()) WHERE fecha_creacion IS NULL;
ALTER TABLE cursos MODIFY COLUMN fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   MODIFY COLUMN fecha_publicacion DATETIME NULL DEFAULT NULL;

INSERT IGNORE INTO formas_pago (nombre) VALUES ('Efectivo en OXXO (Stripe)');

CREATE TABLE IF NOT EXISTS ordenes_pago (
  usuario_id INT UNSIGNED NOT NULL,
  curso_id INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  clave CHAR(48) NOT NULL,
  stripe_intent VARCHAR(255) NULL UNIQUE,
  PRIMARY KEY (usuario_id, curso_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (curso_id) REFERENCES cursos(id)
) ENGINE=InnoDB;
