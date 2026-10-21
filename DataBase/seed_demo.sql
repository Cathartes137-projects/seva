-- =============================================================================
-- SEVA - Sistema Educativo de Lengua de Señas
-- Datos de demostración (NO usar en producción)
-- =============================================================================
-- IMPORTANTE: este archivo contiene únicamente datos ficticios.
--   · Todos los correos usan el dominio reservado example.com
--   · Todas las contraseñas están hasheadas con password_hash() / bcrypt
--   · Los identificadores son secuenciales (1..n), sin valores forzados
--
-- Credenciales de demostración (cambiar en cualquier despliegue real):
--   admin@example.com   / Seva.Admin.2026
--   demo@example.com    / Seva.Demo.2026
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Contenidos educativos (ruta de aprendizaje)
-- -----------------------------------------------------------------------------
INSERT INTO `contenidos_educativos` (`titulo`, `descripcion`, `ruta_imagen`, `ruta_video`, `nivel`, `orden`) VALUES
('Alfabeto dactilológico', 'Aprende a deletrear cada letra del abecedario con las manos.', 'img/lecciones/alfabeto.jpg', 'video/alfabeto.mp4', 'basico', 1),
('Saludos y presentaciones', 'Hola, buenos días, ¿cómo estás?, mucho gusto.', 'img/lecciones/saludos.jpg', 'video/saludos.mp4', 'basico', 2),
('Números del 1 al 10', 'Cómo representar los números básicos con señas.', 'img/lecciones/numeros.jpg', 'video/numeros.mp4', 'basico', 3),
('Colores', 'Vocabulario de colores primarios y secundarios.', 'img/lecciones/colores.jpg', 'video/colores.mp4', 'basico', 4),
('La familia', 'Mamá, papá, hermano, hermana, abuelo, abuela.', 'img/lecciones/familia.jpg', 'video/familia.mp4', 'basico', 5),
('Días de la semana', 'Lunes a domingo, y expresiones de tiempo básicas.', 'img/lecciones/dias.jpg', 'video/dias.mp4', 'intermedio', 6),
('Emociones y sentimientos', 'Feliz, triste, enojado, sorprendido, cansado.', 'img/lecciones/emociones.jpg', 'video/emociones.mp4', 'intermedio', 7),
('Comida y bebida', 'Vocabulario esencial para pedir y hablar de alimentos.', 'img/lecciones/comida.jpg', 'video/comida.mp4', 'intermedio', 8),
('Preguntas básicas', 'Qué, quién, dónde, cuándo, por qué, cómo.', 'img/lecciones/preguntas.jpg', 'video/preguntas.mp4', 'intermedio', 9),
('Lugares y direcciones', 'Casa, escuela, hospital, izquierda, derecha, cerca, lejos.', 'img/lecciones/lugares.jpg', 'video/lugares.mp4', 'avanzado', 10),
('El clima', 'Sol, lluvia, frío, calor, viento.', 'img/lecciones/clima.jpg', 'video/clima.mp4', 'avanzado', 11),
('Verbos comunes', 'Comer, dormir, trabajar, estudiar, jugar, viajar.', 'img/lecciones/verbos.jpg', 'video/verbos.mp4', 'avanzado', 12);

-- -----------------------------------------------------------------------------
-- Usuarios de demostración
-- Los hashes se generaron con password_hash($clave, PASSWORD_DEFAULT) en PHP 8.2
-- -----------------------------------------------------------------------------
INSERT INTO `usuarios` (`nombre`, `email`, `password`) VALUES
('Administrador de demostración', 'admin@example.com', '$2y$10$Xi6lOATOnQ2gMjtQWlacuesfH.cFC4rY5b.LkIpLl4b4zTPONrQcu'),
('Estudiante de demostración', 'demo@example.com', '$2y$10$OfvxghkJNXQfRz/crjKEaunno5ie4JK.AewHa1wPlCpNa9u7LFzTu');

-- -----------------------------------------------------------------------------
-- Progreso de demostración (primeras lecciones del estudiante demo, id 2)
-- -----------------------------------------------------------------------------
INSERT INTO `progreso_usuario` (`usuario_id`, `contenido_id`, `completado`, `fecha_completado`) VALUES
(2, 1, 1, current_timestamp()),
(2, 2, 1, current_timestamp()),
(2, 3, 1, current_timestamp());
