-- =====================================================================
--  Proyecto Login - Script de base de datos
--  Motor: MySQL 8 / MariaDB 10.4+ (XAMPP) - Puerto 3306
--  Importar desde phpMyAdmin (pestaña "Importar") o por consola:
--      mysql -u root -P 3306 < database/proyecto_login.sql
-- =====================================================================

DROP DATABASE IF EXISTS proyecto_login;

CREATE DATABASE proyecto_login
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE proyecto_login;

-- ---------------------------------------------------------------------
-- Tabla USUARIOS: cuentas creadas desde el formulario "Crear Cuenta".
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(50)      NOT NULL,
    apellido            VARCHAR(50)      NOT NULL,
    email               VARCHAR(100)     NOT NULL,
    password_hash       VARCHAR(255)     NOT NULL COMMENT 'Contraseña encriptada con password_hash() (bcrypt)',
    intentos_fallidos   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta     DATETIME         NULL     DEFAULT NULL,
    fecha_alta          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME         NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuarios_email UNIQUE (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Usuarios registrados en el sistema';

-- ---------------------------------------------------------------------
-- Tabla ACCESOS: historial de inicios de sesión (correctos y fallidos).
-- Relación: un usuario registra 0..N accesos.
-- ---------------------------------------------------------------------
CREATE TABLE accesos (
    id_acceso    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario   INT UNSIGNED NOT NULL,
    fecha_acceso DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip           VARCHAR(45)  NOT NULL COMMENT 'Admite IPv4 e IPv6',
    navegador    VARCHAR(255) NULL     DEFAULT NULL,
    exitoso      TINYINT(1)   NOT NULL COMMENT '1 = correcto, 0 = fallido',
    CONSTRAINT pk_accesos PRIMARY KEY (id_acceso),
    CONSTRAINT fk_accesos_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_accesos_usuario_fecha (id_usuario, fecha_acceso)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Historial de inicios de sesion';

-- ---------------------------------------------------------------------
-- Tabla RECUPERACIONES: enlaces para restablecer la contraseña.
-- Relación: un usuario solicita 0..N recuperaciones.
-- Se guarda el hash SHA-256 del token, nunca el token en texto plano.
-- ---------------------------------------------------------------------
CREATE TABLE recuperaciones (
    id_recuperacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario       INT UNSIGNED NOT NULL,
    token_hash       CHAR(64)     NOT NULL,
    fecha_creacion   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME     NOT NULL,
    usado            TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = ya utilizado o anulado',
    CONSTRAINT pk_recuperaciones PRIMARY KEY (id_recuperacion),
    CONSTRAINT uq_recuperaciones_token UNIQUE (token_hash),
    CONSTRAINT fk_recuperaciones_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_recuperaciones_usuario (id_usuario, usado)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Solicitudes de recuperacion de contraseña';
