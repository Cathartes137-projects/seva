-- =============================================================================
-- SEVA - Migración 004: recursos visuales del vocabulario LSC
-- =============================================================================
-- Añade a `vocabulario_senas` los datos necesarios para mostrar la imagen real
-- de cada seña, si requiere movimiento y cómo ejecutarlo.
-- Idempotente: puede ejecutarse varias veces sin error (MariaDB 10.4+).
--
-- Uso:
--   mysql -u root -h 127.0.0.1 -P 3306 sevaeducativo < DataBase/migrations/004_vocabulario_lsc.sql
-- =============================================================================

ALTER TABLE `vocabulario_senas`
    ADD COLUMN IF NOT EXISTS `imagen` varchar(255) DEFAULT NULL AFTER `descripcion`,
    ADD COLUMN IF NOT EXISTS `con_movimiento` tinyint(1) NOT NULL DEFAULT 0 AFTER `imagen`,
    ADD COLUMN IF NOT EXISTS `instruccion_movimiento` text DEFAULT NULL AFTER `con_movimiento`;
