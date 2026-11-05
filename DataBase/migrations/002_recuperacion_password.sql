-- =============================================================================
-- SEVA - Migración 002: tabla de recuperación de contraseña
-- =============================================================================
-- Fase 5 (D13): flujo de restablecimiento con tokens de un solo uso.
-- Idempotente: puede ejecutarse varias veces sin error.
--
-- Uso:
--   mysql -u root -h 127.0.0.1 sevaeducativo < DataBase/migrations/002_recuperacion_password.sql
--   mysql -u root -h 127.0.0.1 seva_educativo_test < DataBase/migrations/002_recuperacion_password.sql
--
-- Seguridad:
--   * Nunca se almacena el token en claro: solo su hash SHA-256 (64 hex).
--   * Un token caduca a los 30 minutos (lo fija la aplicación) y es de un solo uso.
--   * Al eliminar el usuario, sus tokens se eliminan en cascada.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `recuperacion_password` (
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
