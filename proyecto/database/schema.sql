DROP DATABASE IF EXISTS walteredu;
CREATE DATABASE walteredu CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE walteredu;

CREATE TABLE usuarios (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo      VARCHAR(120) NOT NULL,
  genero               ENUM('Femenino','Masculino','Otro','Prefiero no decir') NOT NULL,
  fecha_nacimiento     DATE NOT NULL,
  foto                 VARCHAR(255) NULL,
  email                VARCHAR(150) NOT NULL UNIQUE,
  clave_hash           VARCHAR(255) NOT NULL,
  rol                  ENUM('estudiante','instructor','administrador') NOT NULL,
  activo               TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fecha_registro       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(80) NOT NULL UNIQUE,
  descripcion     TEXT NULL,
  creado_por      INT UNSIGNED NOT NULL,
  fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE cursos (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  instructor_id      INT UNSIGNED NOT NULL,
  titulo             VARCHAR(150) NOT NULL,
  descripcion        TEXT NOT NULL,
  nivel              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  imagen             VARCHAR(255) NULL,
  precio             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo             TINYINT(1) NOT NULL DEFAULT 1,
  fecha_publicacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_nivel CHECK (nivel BETWEEN 1 AND 5),
  FOREIGN KEY (instructor_id) REFERENCES usuarios(id),
  INDEX (titulo), INDEX (fecha_publicacion)
) ENGINE=InnoDB;

CREATE TABLE curso_categoria (
  curso_id      INT UNSIGNED NOT NULL,
  categoria_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (curso_id, categoria_id),
  FOREIGN KEY (curso_id)     REFERENCES cursos(id)     ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE niveles (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curso_id     INT UNSIGNED NOT NULL,
  orden        TINYINT UNSIGNED NOT NULL,
  titulo       VARCHAR(150) NOT NULL,
  contenido    TEXT NULL,
  video_url    VARCHAR(255) NOT NULL,
  precio       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  UNIQUE (curso_id, orden)
) ENGINE=InnoDB;

CREATE TABLE archivos_nivel (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nivel_id  INT UNSIGNED NOT NULL,
  tipo      ENUM('pdf','imagen','link') NOT NULL,
  nombre    VARCHAR(120) NOT NULL,
  ruta      VARCHAR(255) NOT NULL,
  FOREIGN KEY (nivel_id) REFERENCES niveles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE formas_pago (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE inscripciones (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id          INT UNSIGNED NOT NULL,
  curso_id            INT UNSIGNED NOT NULL,
  forma_pago_id       INT UNSIGNED NULL,
  precio_pagado       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  fecha_inscripcion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultima_fecha_acceso DATETIME NULL,
  fecha_terminacion   DATETIME NULL,
  completado          TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (usuario_id)    REFERENCES usuarios(id),
  FOREIGN KEY (curso_id)      REFERENCES cursos(id),
  FOREIGN KEY (forma_pago_id) REFERENCES formas_pago(id),
  UNIQUE (usuario_id, curso_id)
) ENGINE=InnoDB;

CREATE TABLE avance_niveles (
  inscripcion_id  INT UNSIGNED NOT NULL,
  nivel_id        INT UNSIGNED NOT NULL,
  fecha_visto     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (inscripcion_id, nivel_id),
  FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id) ON DELETE CASCADE,
  FOREIGN KEY (nivel_id)       REFERENCES niveles(id)
) ENGINE=InnoDB;

CREATE TABLE comentarios (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curso_id            INT UNSIGNED NOT NULL,
  usuario_id          INT UNSIGNED NOT NULL,
  texto               TEXT NOT NULL,
  calificacion        TINYINT UNSIGNED NOT NULL,
  fecha_creacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  eliminado           TINYINT(1) NOT NULL DEFAULT 0,
  fecha_eliminacion   DATETIME NULL,
  causa_eliminacion   VARCHAR(255) NULL,
  CONSTRAINT chk_calificacion CHECK (calificacion BETWEEN 1 AND 5),
  FOREIGN KEY (curso_id)   REFERENCES cursos(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  UNIQUE (curso_id, usuario_id)
) ENGINE=InnoDB;

CREATE TABLE mensajes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  emisor_id    INT UNSIGNED NOT NULL,
  receptor_id  INT UNSIGNED NOT NULL,
  curso_id     INT UNSIGNED NULL,
  texto        TEXT NOT NULL,
  fecha_envio  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (emisor_id)   REFERENCES usuarios(id),
  FOREIGN KEY (receptor_id) REFERENCES usuarios(id),
  FOREIGN KEY (curso_id)    REFERENCES cursos(id)
) ENGINE=InnoDB;

CREATE TABLE certificados (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id  INT UNSIGNED NOT NULL UNIQUE,
  certificado_por VARCHAR(120) NOT NULL,
  fecha_emision   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
) ENGINE=InnoDB;

INSERT INTO formas_pago (nombre) VALUES ('Tarjeta (Stripe)'), ('PayPal');