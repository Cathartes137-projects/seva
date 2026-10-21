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
-- Vocabulario de señas por lección (Fase 6 / D16)
-- Antes, leccion.php mostraba siempre el vocabulario de "Saludos".
-- Los contenidos_id coinciden con el orden de inserción anterior (1..12).
-- Descripciones demostrativas: revisar con un instructor de LSE antes de
-- usar el material en un curso real.
-- -----------------------------------------------------------------------------
INSERT INTO `vocabulario_senas` (`contenido_id`, `palabra`, `descripcion`, `orden`) VALUES
-- 1. Alfabeto dactilológico
(1, 'A', 'Puño cerrado con el pulgar pegado al costado del índice, como la letra A del alfabeto manual.', 1),
(1, 'B', 'Mano abierta con los cuatro dedos juntos y el pulgar recogido sobre la palma.', 2),
(1, 'C', 'Forma una curva con los dedos y el pulgar enfrentados, dibujando una C.', 3),
(1, 'D', 'Índice extendido hacia arriba; los demás dedos se unen al pulgar formando un círculo.', 4),
-- 2. Saludos y presentaciones
(2, 'Hola', 'Con la palma abierta orientada hacia el frente, realiza un movimiento suave de lado a lado cerca de la sien.', 1),
(2, 'Buenos días', 'Junta las yemas de los dedos de una mano sobre el pecho y asciende el brazo suavemente simulando la salida del sol.', 2),
(2, 'Mucho gusto', 'Con los pulgares hacia arriba, toca primero suavemente tu pecho y luego extiende ambas manos hacia tu interlocutor.', 3),
(2, '¿Cómo estás?', 'Mueve ambas manos con pulgares arriba alternadamente hacia adelante, acompañando con una expresión facial interrogativa.', 4),
(2, 'Mi nombre es', 'Toca dos veces los dedos índice y medio entre ambas manos de forma cruzada y procede a deletrear tu nombre.', 5),
(2, 'Adiós', 'Abre y cierra suavemente los cuatro dedos con la palma extendida hacia la otra persona en gesto de despedida.', 6),
-- 3. Números del 1 al 10
(3, 'Uno', 'Levanta solo el dedo índice con la palma orientada hacia el frente.', 1),
(3, 'Dos', 'Extiende el índice y el dedo medio formando una V.', 2),
(3, 'Cinco', 'Muestra la mano abierta con los cinco dedos separados.', 3),
(3, 'Diez', 'Cierra el puño y ábrelo con los dedos extendidos en un movimiento rápido.', 4),
-- 4. Colores
(4, 'Rojo', 'Pasa el índice sobre los labios y bájalo en línea recta; acompaña con la expresión facial.', 1),
(4, 'Azul', 'Gira la mano abierta junto al hombro con un pequeño movimiento de muñeca.', 2),
(4, 'Verde', 'Con la mano en forma de V, gira la muñeca suavemente hacia el frente.', 3),
(4, 'Amarillo', 'Abre y cierra la mano cerca del hombro con el pulgar y el meñique extendidos.', 4),
-- 5. La familia
(5, 'Mamá', 'Toca suavemente la mejilla con la punta de los dedos de la mano abierta.', 1),
(5, 'Papá', 'Toca la frente con la punta de los dedos de la mano abierta.', 2),
(5, 'Hermano', 'Con los índices extendidos, junta ambas manos y sepáralas hacia el frente.', 3),
(5, 'Abuela', 'Toca la mejilla y traza un pequeño arco hacia atrás con la mano abierta.', 4),
-- 6. Días de la semana
(6, 'Lunes', 'Con la mano en forma de L, dibuja un pequeño círculo en el aire.', 1),
(6, 'Martes', 'Con la mano abierta, traza un movimiento corto hacia el frente.', 2),
(6, 'Sábado', 'Cierra el puño y dibuja un círculo con el pulgar hacia arriba.', 3),
(6, 'Domingo', 'Con ambas manos abiertas, traza dos arcos hacia los lados.', 4),
-- 7. Emociones y sentimientos
(7, 'Feliz', 'Pasa ambas manos abiertas en círculos sobre el pecho, con expresión sonriente.', 1),
(7, 'Triste', 'Desliza los dedos hacia abajo por el rostro, acompañando con expresión de tristeza.', 2),
(7, 'Enojado', 'Con las manos en garra frente al pecho, separa y cierra los dedos con tensión.', 3),
(7, 'Sorprendido', 'Eleva ambas manos abiertas a la altura del rostro y abre bien los ojos.', 4),
-- 8. Comida y bebida
(8, 'Agua', 'Con la mano en forma de C, toca el mentón con el índice y el pulgar.', 1),
(8, 'Pan', 'Con los dedos juntos, corta suavemente el aire sobre la palma de la otra mano.', 2),
(8, 'Fruta', 'Sostén una fruta imaginaria y llévala a la boca con un pequeño giro.', 3),
(8, 'Café', 'Con el puño cerrado, simula moler café sobre la palma de la otra mano.', 4),
-- 9. Preguntas básicas
(9, '¿Qué?', 'Mueve el índice extendido de un lado a otro con expresión interrogativa.', 1),
(9, '¿Quién?', 'Toca el pulgar con el índice cerca del mentón y dibuja un pequeño círculo.', 2),
(9, '¿Dónde?', 'Con el índice extendido, dibuja pequeños círculos en el aire hacia el frente.', 3),
(9, '¿Cómo?', 'Con ambas manos en forma de C enfrentadas, gíralas hacia arriba.', 4),
-- 10. Lugares y direcciones
(10, 'Casa', 'Junta las yemas de los dedos de ambas manos formando un tejado.', 1),
(10, 'Escuela', 'Da dos palmadas suaves con la palma abierta sobre la otra mano.', 2),
(10, 'Hospital', 'Dibuja una cruz con el índice sobre el brazo contrario.', 3),
(10, 'Izquierda', 'Señala hacia la izquierda con el índice extendido.', 4),
-- 11. El clima
(11, 'Sol', 'Dibuja un círculo con el índice y extiende los dedos como rayos.', 1),
(11, 'Lluvia', 'Baja los dedos de ambas manos abiertas simulando gotas.', 2),
(11, 'Frío', 'Cruza los brazos sobre el pecho y frota los brazos con las manos.', 3),
(11, 'Calor', 'Abanica el rostro con la mano abierta.', 4),
-- 12. Verbos comunes
(12, 'Comer', 'Lleva repetidamente los dedos juntos a la boca.', 1),
(12, 'Dormir', 'Apoya la mejilla sobre la palma de la mano y cierra los ojos.', 2),
(12, 'Trabajar', 'Golpea suavemente el dorso de la mano cerrada sobre la muñeca contraria.', 3),
(12, 'Estudiar', 'Abre y cierra la mano frente a la frente, como pasando páginas.', 4);

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
