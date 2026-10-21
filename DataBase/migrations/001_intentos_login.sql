-- =============================================================================
-- SEVA - Migración 001: tabla de control de intentos de inicio de sesión
-- =============================================================================
-- Fase 3 (D09): anti fuerza bruta.
-- Idempotente: puede ejecutarse varias veces sin error.
--
-- Uso:
--   mysql -u root -h 127.0.0.1 seva_educativo < DataBase/migrations/001_intentos_login.sql
-- =============================================================================

CREATE TABLE IF NOT EXISTS `intentos_login` (
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
