-- =============================================================================
-- SEVA - Migración 003: vocabulario de señas por lección
-- =============================================================================
-- Fase 6 (D16): sustituye el vocabulario hardcodeado (siempre "Saludos") por
-- contenido propio de cada lección.
-- Idempotente: puede ejecutarse varias veces sin error.
--
-- Uso:
--   mysql -u root -h 127.0.0.1 sevaeducativo < DataBase/migrations/003_vocabulario_senas.sql
--   mysql -u root -h 127.0.0.1 seva_educativo_test < DataBase/migrations/003_vocabulario_senas.sql
--
-- Nota: esta migración solo crea la estructura. Los datos de demostración se
-- cargan con DataBase/seed_demo.sql (sección "Vocabulario de señas").
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vocabulario_senas` (
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
