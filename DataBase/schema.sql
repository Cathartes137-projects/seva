-- =============================================================================
-- SEVA - Sistema Educativo de Lengua de Señas
-- Esquema de base de datos (estructura únicamente, sin datos)
-- Motor: InnoDB · Codificación: utf8mb4
-- Compatible con MariaDB 10.4+ / MySQL 8.0+
-- =============================================================================
-- Uso:
--   mysql -u root -h 127.0.0.1 -e "CREATE DATABASE sevaeducativo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--   mysql -u root -h 127.0.0.1 sevaeducativo < DataBase/schema.sql
--   mysql -u root -h 127.0.0.1 sevaeducativo < DataBase/seed_demo.sql   -- datos de demostración
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) NULL DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: contenidos_educativos
-- -----------------------------------------------------------------------------
CREATE TABLE `contenidos_educativos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  `ruta_video` varchar(255) DEFAULT NULL,
  `nivel` enum('basico','intermedio','avanzado') NOT NULL DEFAULT 'basico',
  `orden` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: progreso_usuario
-- -----------------------------------------------------------------------------
CREATE TABLE `progreso_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `contenido_id` int(11) NOT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_completado` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_contenido` (`usuario_id`,`contenido_id`),
  KEY `fk_progreso_usuario_usuario` (`usuario_id`),
  KEY `fk_progreso_usuario_contenido` (`contenido_id`),
  CONSTRAINT `fk_progreso_usuario_contenido` FOREIGN KEY (`contenido_id`) REFERENCES `contenidos_educativos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progreso_usuario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: palabras_repasadas
-- -----------------------------------------------------------------------------
CREATE TABLE `palabras_repasadas` (
  `id_palabra` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `palabra` varchar(100) NOT NULL,
  `frecuencia` int(11) DEFAULT 0,
  `repeticion_espaciada` date DEFAULT NULL,
  PRIMARY KEY (`id_palabra`),
  KEY `fk_palabras_usuario` (`id_usuario`),
  CONSTRAINT `fk_palabras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: cursos
-- -----------------------------------------------------------------------------
CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_curso` varchar(100) NOT NULL,
  `idioma_origen` varchar(50) DEFAULT NULL,
  `idioma_destino` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: intentos_login
-- Control de fuerza bruta: 5 fallos por (IP + correo) → bloqueo temporal.
-- Sin FK a usuarios: debe registrar también correos inexistentes.
-- -----------------------------------------------------------------------------
CREATE TABLE `intentos_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `email` varchar(150) NOT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `actualizado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip_email` (`ip`,`email`),
  KEY `idx_actualizado` (`actualizado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: recuperacion_password
-- Tokens de restablecimiento de contraseña (Fase 5 / D13).
-- Solo se guarda el hash SHA-256 del token; expira a los 30 minutos y es de un
-- solo uso. ON DELETE CASCADE: al borrar el usuario desaparecen sus tokens.
-- -----------------------------------------------------------------------------
CREATE TABLE `recuperacion_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token_hash` (`token_hash`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_expira` (`expira`),
  CONSTRAINT `fk_recuperacion_usuario` FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: vocabulario_senas
-- Vocabulario propio de cada lección (Fase 6 / D16). Antes estaba hardcodeado
-- en leccion.php y todas las lecciones mostraban las señas de "Saludos".
-- ON DELETE CASCADE: al borrar la lección desaparece su vocabulario.
-- -----------------------------------------------------------------------------
CREATE TABLE `vocabulario_senas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contenido_id` int(11) NOT NULL,
  `palabra` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contenido_palabra` (`contenido_id`, `palabra`),
  KEY `idx_contenido_orden` (`contenido_id`, `orden`),
  CONSTRAINT `fk_vocabulario_contenido` FOREIGN KEY (`contenido_id`)
    REFERENCES `contenidos_educativos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
