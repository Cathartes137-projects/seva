-- =============================================================================
-- SEVA - Migración 004: token de "Recordarme" por usuario
-- =============================================================================
-- Añade la columna donde se persiste el hash SHA-256 del token de recordatorio
-- generado en el login (Opción A). Solo se guarda el hash, nunca el token en claro.

ALTER TABLE `usuarios` ADD COLUMN `remember_token` varchar(255) NULL DEFAULT NULL AFTER `password`;

CREATE INDEX `idx_remember_token` ON `usuarios` (`remember_token`);
