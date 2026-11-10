-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 06-11-2026 a las 02:26:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sevaeducativo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenidos_educativos`
--

CREATE TABLE `contenidos_educativos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  `ruta_video` varchar(255) DEFAULT NULL,
  `nivel` enum('basico','intermedio','avanzado') NOT NULL DEFAULT 'basico',
  `orden` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenidos_educativos`
--

INSERT INTO `contenidos_educativos` (`id`, `titulo`, `descripcion`, `ruta_imagen`, `ruta_video`, `nivel`, `orden`, `fecha_creacion`) VALUES
(1, 'Alfabeto dactilológico · LSC', 'Aprende las 27 letras del alfabeto dactilológico de la Lengua de Señas Colombiana (LSC) con imágenes guía, descripción gestual e instrucciones de movimiento.', 'img/lecciones/alfabeto.jpg', 'video/alfabeto.mp4', 'basico', 1, '2026-10-23 05:02:09'),
(2, 'Saludos y presentaciones · LSC', 'Aprende 24 saludos, presentaciones y expresiones de cortesía en Lengua de Señas Colombiana (LSC), con imagen guía, descripción gestual e instrucciones de movimiento.', 'img/lecciones/saludos.jpg', 'media/saludos-presentacion.mp4', 'basico', 2, '2026-10-23 05:02:09'),
(3, 'Números del 1 al 10', 'Cómo representar los números básicos con señas.', 'img/lecciones/numeros.jpg', 'media/numeros.mp4', 'basico', 3, '2026-10-23 05:02:09'),
(4, 'Colores', 'Vocabulario de colores primarios y secundarios.', 'img/lecciones/colores.jpg', 'media/colores.mp4', 'basico', 4, '2026-10-23 05:02:09'),
(5, 'La familia', 'Mamá, papá, hermano, hermana, abuelo, abuela.', 'img/lecciones/familia.jpg', 'video/familia.mp4', 'basico', 5, '2026-10-23 05:02:09'),
(6, 'Días de la semana', 'Lunes a domingo, y expresiones de tiempo básicas.', 'img/lecciones/dias.jpg', 'video/dias.mp4', 'intermedio', 6, '2026-10-23 05:02:09'),
(7, 'Emociones y sentimientos', 'Feliz, triste, enojado, sorprendido, cansado.', 'img/lecciones/emociones.jpg', 'video/emociones.mp4', 'intermedio', 7, '2026-10-23 05:02:09'),
(8, 'Comida y bebida', 'Vocabulario esencial para pedir y hablar de alimentos.', 'img/lecciones/comida.jpg', 'video/comida.mp4', 'intermedio', 8, '2026-10-23 05:02:09'),
(9, 'Preguntas básicas', 'Qué, quién, dónde, cuándo, por qué, cómo.', 'img/lecciones/preguntas.jpg', 'video/preguntas.mp4', 'intermedio', 9, '2026-10-23 05:02:09'),
(10, 'Lugares y direcciones', 'Casa, escuela, hospital, izquierda, derecha, cerca, lejos.', 'img/lecciones/lugares.jpg', 'video/lugares.mp4', 'avanzado', 10, '2026-10-23 05:02:09'),
(11, 'El clima', 'Sol, lluvia, frío, calor, viento.', 'img/lecciones/clima.jpg', 'video/clima.mp4', 'avanzado', 11, '2026-10-23 05:02:09'),
(12, 'Verbos comunes', 'Comer, dormir, trabajar, estudiar, jugar, viajar.', 'img/lecciones/verbos.jpg', 'video/verbos.mp4', 'avanzado', 12, '2026-10-23 05:02:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(100) NOT NULL,
  `idioma_origen` varchar(50) DEFAULT NULL,
  `idioma_destino` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_login`
--

CREATE TABLE `intentos_login` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `email` varchar(150) NOT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `actualizado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `intentos_login`
--

INSERT INTO `intentos_login` (`id`, `ip`, `email`, `intentos`, `bloqueado_hasta`, `actualizado`) VALUES
(1, '::1', 'edwin1234@gmail.com', 1, NULL, '2026-10-23 05:04:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palabras_repasadas`
--

CREATE TABLE `palabras_repasadas` (
  `id_palabra` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `palabra` varchar(100) NOT NULL,
  `frecuencia` int(11) DEFAULT 0,
  `repeticion_espaciada` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_usuario`
--

CREATE TABLE `progreso_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contenido_id` int(11) NOT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_completado` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `progreso_usuario`
--

INSERT INTO `progreso_usuario` (`id`, `usuario_id`, `contenido_id`, `completado`, `fecha_completado`) VALUES
(1, 2, 1, 1, '2026-10-23 08:16:48'),
(2, 2, 2, 1, '2026-10-23 07:51:50'),
(3, 2, 3, 1, '2026-10-23 05:02:09'),
(14, 3, 1, 1, '2026-10-29 00:57:09'),
(16, 4, 1, 1, '2026-10-30 23:25:07'),
(17, 5, 1, 1, '2026-10-23 15:18:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion_password`
--

CREATE TABLE `recuperacion_password` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `password`, `fecha_registro`) VALUES
(1, 'Administrador de demostración', 'admin@example.com', '$2y$10$Xi6lOATOnQ2gMjtQWlacuesfH.cFC4rY5b.LkIpLl4b4zTPONrQcu', '2026-10-23 05:02:09'),
(2, 'Estudiante de demostración', 'demo@example.com', '$2y$10$OfvxghkJNXQfRz/crjKEaunno5ie4JK.AewHa1wPlCpNa9u7LFzTu', '2026-10-23 05:02:09'),
(3, 'Testeo1910', 'hola@gmail.com', '$2y$10$Zv0iF/5aVvSjNW2dHJZLa.JBnBzc4xgKka9.rrqNWGgOM24tzT5Qe', '2026-10-23 05:05:31'),
(4, 'Carlos alejandro', 'carlos@gmail.com', '$2y$10$bi.RDlaakYJ1l.vUWNciROqzLQbhrBGu2VBg2uCBqJEx5NB2pESdm', '2026-10-23 14:51:37'),
(5, 'nayibe cossio correa', 'emacossio10@gmail.com', '$2y$10$OSHM/xkxebqLFFExeNK9quH.T6RLHZAUcQneTDw5EhrIcHJbPVWYi', '2026-10-23 15:13:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vocabulario_senas`
--

CREATE TABLE `vocabulario_senas` (
  `id` int(11) NOT NULL,
  `contenido_id` int(11) NOT NULL,
  `palabra` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `con_movimiento` tinyint(1) NOT NULL DEFAULT 0,
  `instruccion_movimiento` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vocabulario_senas`
--

INSERT INTO `vocabulario_senas` (`id`, `contenido_id`, `palabra`, `descripcion`, `imagen`, `con_movimiento`, `instruccion_movimiento`, `orden`) VALUES
(11, 3, 'Uno', 'Levanta solo el dedo índice con la palma orientada hacia el frente.', NULL, 0, NULL, 1),
(12, 3, 'Dos', 'Extiende el índice y el dedo medio formando una V.', NULL, 0, NULL, 2),
(13, 3, 'Cinco', 'Muestra la mano abierta con los cinco dedos separados.', NULL, 0, NULL, 3),
(14, 3, 'Diez', 'Cierra el puño y ábrelo con los dedos extendidos en un movimiento rápido.', NULL, 0, NULL, 4),
(15, 4, 'Rojo', 'Pasa el índice sobre los labios y bájalo en línea recta; acompaña con la expresión facial.', NULL, 0, NULL, 1),
(16, 4, 'Azul', 'Gira la mano abierta junto al hombro con un pequeño movimiento de muñeca.', NULL, 0, NULL, 2),
(17, 4, 'Verde', 'Con la mano en forma de V, gira la muñeca suavemente hacia el frente.', NULL, 0, NULL, 3),
(18, 4, 'Amarillo', 'Abre y cierra la mano cerca del hombro con el pulgar y el meñique extendidos.', NULL, 0, NULL, 4),
(19, 5, 'Mamá', 'Toca suavemente la mejilla con la punta de los dedos de la mano abierta.', NULL, 0, NULL, 1),
(20, 5, 'Papá', 'Toca la frente con la punta de los dedos de la mano abierta.', NULL, 0, NULL, 2),
(21, 5, 'Hermano', 'Con los índices extendidos, junta ambas manos y sepáralas hacia el frente.', NULL, 0, NULL, 3),
(22, 5, 'Abuela', 'Toca la mejilla y traza un pequeño arco hacia atrás con la mano abierta.', NULL, 0, NULL, 4),
(23, 6, 'Lunes', 'Con la mano en forma de L, dibuja un pequeño círculo en el aire.', NULL, 0, NULL, 1),
(24, 6, 'Martes', 'Con la mano abierta, traza un movimiento corto hacia el frente.', NULL, 0, NULL, 2),
(25, 6, 'Sábado', 'Cierra el puño y dibuja un círculo con el pulgar hacia arriba.', NULL, 0, NULL, 3),
(26, 6, 'Domingo', 'Con ambas manos abiertas, traza dos arcos hacia los lados.', NULL, 0, NULL, 4),
(27, 7, 'Feliz', 'Pasa ambas manos abiertas en círculos sobre el pecho, con expresión sonriente.', NULL, 0, NULL, 1),
(28, 7, 'Triste', 'Desliza los dedos hacia abajo por el rostro, acompañando con expresión de tristeza.', NULL, 0, NULL, 2),
(29, 7, 'Enojado', 'Con las manos en garra frente al pecho, separa y cierra los dedos con tensión.', NULL, 0, NULL, 3),
(30, 7, 'Sorprendido', 'Eleva ambas manos abiertas a la altura del rostro y abre bien los ojos.', NULL, 0, NULL, 4),
(31, 8, 'Agua', 'Con la mano en forma de C, toca el mentón con el índice y el pulgar.', NULL, 0, NULL, 1),
(32, 8, 'Pan', 'Con los dedos juntos, corta suavemente el aire sobre la palma de la otra mano.', NULL, 0, NULL, 2),
(33, 8, 'Fruta', 'Sostén una fruta imaginaria y llévala a la boca con un pequeño giro.', NULL, 0, NULL, 3),
(34, 8, 'Café', 'Con el puño cerrado, simula moler café sobre la palma de la otra mano.', NULL, 0, NULL, 4),
(35, 9, '¿Qué?', 'Mueve el índice extendido de un lado a otro con expresión interrogativa.', NULL, 0, NULL, 1),
(36, 9, '¿Quién?', 'Toca el pulgar con el índice cerca del mentón y dibuja un pequeño círculo.', NULL, 0, NULL, 2),
(37, 9, '¿Dónde?', 'Con el índice extendido, dibuja pequeños círculos en el aire hacia el frente.', NULL, 0, NULL, 3),
(38, 9, '¿Cómo?', 'Con ambas manos en forma de C enfrentadas, gíralas hacia arriba.', NULL, 0, NULL, 4),
(39, 10, 'Casa', 'Junta las yemas de los dedos de ambas manos formando un tejado.', NULL, 0, NULL, 1),
(40, 10, 'Escuela', 'Da dos palmadas suaves con la palma abierta sobre la otra mano.', NULL, 0, NULL, 2),
(41, 10, 'Hospital', 'Dibuja una cruz con el índice sobre el brazo contrario.', NULL, 0, NULL, 3),
(42, 10, 'Izquierda', 'Señala hacia la izquierda con el índice extendido.', NULL, 0, NULL, 4),
(43, 11, 'Sol', 'Dibuja un círculo con el índice y extiende los dedos como rayos.', NULL, 0, NULL, 1),
(44, 11, 'Lluvia', 'Baja los dedos de ambas manos abiertas simulando gotas.', NULL, 0, NULL, 2),
(45, 11, 'Frío', 'Cruza los brazos sobre el pecho y frota los brazos con las manos.', NULL, 0, NULL, 3),
(46, 11, 'Calor', 'Abanica el rostro con la mano abierta.', NULL, 0, NULL, 4),
(47, 12, 'Comer', 'Lleva repetidamente los dedos juntos a la boca.', NULL, 0, NULL, 1),
(48, 12, 'Dormir', 'Apoya la mejilla sobre la palma de la mano y cierra los ojos.', NULL, 0, NULL, 2),
(49, 12, 'Trabajar', 'Golpea suavemente el dorso de la mano cerrada sobre la muñeca contraria.', NULL, 0, NULL, 3),
(50, 12, 'Estudiar', 'Abre y cierra la mano frente a la frente, como pasando páginas.', NULL, 0, NULL, 4),
(66, 1, 'A', 'Cierra la mano formando un puño y extiende el pulgar hacia un costado, pegado al lado de los demás dedos (no hacia arriba).', 'img/lsc/alfabeto/letra_A.png', 0, NULL, 1),
(67, 1, 'B', 'Extiende los cuatro dedos juntos y rectos hacia arriba, con la palma hacia el frente, y dobla el pulgar cruzándolo por delante de la palma.', 'img/lsc/alfabeto/letra_B.png', 0, NULL, 2),
(68, 1, 'C', 'Curva todos los dedos y el pulgar formando la silueta de la letra C, dejando un espacio abierto entre el pulgar y el índice.', 'img/lsc/alfabeto/letra_C.png', 0, NULL, 3),
(69, 1, 'D', 'Levanta el dedo índice estirado hacia arriba y une la punta del pulgar con las puntas del anular y meñique, formando un pequeño círculo debajo del índice.', 'img/lsc/alfabeto/letra_D.png', 0, NULL, 4),
(70, 1, 'E', 'Dobla los cuatro dedos hacia la palma (como un puño flojo) dejando las puntas visibles por arriba, y coloca el pulgar recogido contra ellos.', 'img/lsc/alfabeto/letra_E.png', 0, NULL, 5),
(71, 1, 'F', 'Levanta el dedo índice estirado; dobla el pulgar y los dedos medio, anular y meñique hacia la palma, apoyando el pulgar por delante de ellos.', 'img/lsc/alfabeto/letra_F.png', 0, NULL, 6),
(72, 1, 'G', 'Extiende el dedo índice apuntando hacia el costado (no hacia arriba), con los demás dedos cerrados. Realiza un pequeño giro de muñeca hacia arriba, como indica la flecha.', 'img/lsc/alfabeto/letra_G.png', 1, 'Gira levemente la punta del índice hacia arriba/afuera.', 7),
(73, 1, 'H', 'Extiende el índice y el medio juntos hacia el costado, con el pulgar y los demás dedos recogidos. Baja levemente la mano siguiendo la dirección de la flecha.', 'img/lsc/alfabeto/letra_H.png', 1, 'Mueve la mano hacia abajo en un pequeño arco, sin separar los dedos.', 8),
(74, 1, 'I', 'Cierra los dedos índice, medio y anular hacia la palma, y extiende el meñique y el pulgar hacia afuera.', 'img/lsc/alfabeto/letra_I.png', 0, NULL, 9),
(75, 1, 'J', 'Extiende el dedo índice hacia abajo con los demás dedos cerrados y el pulgar apoyado al costado. Traza en el aire la forma de una letra J.', 'img/lsc/alfabeto/letra_J.png', 1, 'Dibuja con el índice el trazo de una J: baja, gira hacia la derecha y sube.', 10),
(76, 1, 'K', 'Extiende el índice hacia arriba y el pulgar hacia el costado, formando una escuadra; los demás dedos permanecen cerrados.', 'img/lsc/alfabeto/letra_K.png', 0, NULL, 11),
(77, 1, 'L', 'Extiende el índice hacia arriba y el pulgar hacia el costado formando una \'L\', con el medio, anular y meñique doblados hacia la palma.', 'img/lsc/alfabeto/letra_L.png', 0, NULL, 12),
(78, 1, 'M', 'Cierra la mano en puño y esconde el pulgar debajo de los dedos índice, medio y anular.', 'img/lsc/alfabeto/letra_M.png', 0, NULL, 13),
(79, 1, 'N', 'Cierra la mano en puño y esconde el pulgar debajo únicamente de los dedos índice y medio (diferencia con la M, que cubre tres dedos).', 'img/lsc/alfabeto/letra_N.png', 0, NULL, 14),
(80, 1, 'Ñ (eñe)', 'Forma la misma mano que la N (puño con el pulgar bajo el índice y el medio) y realiza un movimiento ondulado de lado a lado, imitando la virgulilla (~) de la Ñ.', 'img/lsc/alfabeto/letra_Ñ.png', 1, 'Mueve la muñeca en zigzag/ondulación de izquierda a derecha.', 15),
(81, 1, 'O', 'Une la punta del pulgar con las puntas de todos los demás dedos formando un círculo cerrado, como la forma de la letra O.', 'img/lsc/alfabeto/letra_O.png', 0, NULL, 16),
(82, 1, 'P', 'Con la muñeca girada hacia abajo, extiende el índice apuntando al suelo mientras el pulgar toca el dedo medio; los demás dedos quedan recogidos.', 'img/lsc/alfabeto/letra_P.png', 0, NULL, 17),
(83, 1, 'Q', 'Junta las puntas de todos los dedos con la del pulgar apuntando hacia abajo, como pellizcando algo.', 'img/lsc/alfabeto/letra_Q.png', 0, NULL, 18),
(84, 1, 'R', 'Cruza el dedo índice sobre el medio (como al \'cruzar los dedos\'), manteniendo el anular y el meñique doblados y el pulgar recogido.', 'img/lsc/alfabeto/letra_R.png', 0, NULL, 19),
(85, 1, 'S', 'Extiende el índice hacia arriba con los demás dedos cerrados (similar a la D/F) y realiza un pequeño giro o vaivén con la punta del dedo, trazando una S.', 'img/lsc/alfabeto/letra_S.png', 1, 'Gira suavemente la muñeca dibujando una pequeña curva en forma de S.', 20),
(86, 1, 'T', 'Extiende juntos los dedos medio, anular y meñique hacia arriba, mientras el índice y el pulgar se tocan formando un pequeño círculo al frente.', 'img/lsc/alfabeto/letra_T.png', 0, NULL, 21),
(87, 1, 'U', 'Extiende el índice y el meñique hacia arriba (como \'cuernos\'), manteniendo el medio y el anular doblados y el pulgar recogido.', 'img/lsc/alfabeto/letra_U.png', 0, NULL, 22),
(88, 1, 'V', 'Extiende el índice y el medio hacia arriba separados en forma de V, con el anular, meñique y pulgar recogidos.', 'img/lsc/alfabeto/letra_V.png', 0, NULL, 23),
(89, 1, 'W', 'Extiende el índice, medio y anular hacia arriba separados entre sí, con el meñique y el pulgar recogidos.', 'img/lsc/alfabeto/letra_W.png', 0, NULL, 24),
(90, 1, 'X', 'Dobla el dedo índice formando un gancho, manteniendo los demás dedos cerrados y el pulgar apoyado al costado.', 'img/lsc/alfabeto/letra_X.png', 0, NULL, 25),
(91, 1, 'Y', 'Extiende el pulgar y el meñique hacia los extremos (signo de \'hang loose\'), manteniendo el índice, medio y anular doblados.', 'img/lsc/alfabeto/letra_Y.png', 0, NULL, 26),
(92, 1, 'Z', 'Extiende el índice y el medio juntos apuntando al frente, con los demás dedos cerrados, y traza en el aire la forma de la letra Z.', 'img/lsc/alfabeto/letra_Z.png', 1, 'Dibuja con los dedos una Z: línea horizontal, diagonal y otra horizontal.', 27),
(117, 2, 'Hola formal', 'Mano plana cerca de la frente, con la palma hacia el cuerpo.', 'img/lsc/senas/hola-formal.png', 1, 'Haz un arco fuerte hacia adelante.', 1),
(118, 2, 'Hola informal', 'Mano cerca de la frente con el índice extendido.', 'img/lsc/senas/hola-informal.png', 1, 'Haz un pequeño arco hacia adelante.', 2),
(119, 2, 'Buenos días', 'Mano plana cerca de la barbilla.', 'img/lsc/senas/buenos-dias.png', 1, 'Bájala para unirse con la otra mano plana.', 3),
(120, 2, 'Buenas tardes', 'Mano plana cerca de la barbilla.', 'img/lsc/senas/buenas-tardes.png', 1, 'Bájala para unirse con la otra mano plana a la altura del pecho.', 4),
(121, 2, 'Buenas noches', 'Dedos índice y medio extendidos de una mano, sobre la otra mano plana y ahuecada.', 'img/lsc/senas/buenas-noches.png', 1, 'Baja los dedos hacia la otra mano.', 5),
(122, 2, 'Adiós', 'Mano plana cerca de la frente, con la palma hacia el cuerpo.', 'img/lsc/senas/adios.png', 1, 'Saluda de lado a lado.', 6),
(123, 2, 'Nos vemos', 'Manos cerca del pecho, palmas cara a cara, dedos ahuecados.', 'img/lsc/senas/nos-vemos.png', 0, NULL, 7),
(124, 2, 'Por favor', 'Palma abierta con los dedos juntos, sobre el centro del pecho.', 'img/lsc/senas/por-favor.png', 0, NULL, 8),
(125, 2, 'Gracias', 'Mano plana con los dedos juntos, junto a la barbilla.', 'img/lsc/senas/gracias.png', 1, 'Muévela hacia adelante y abajo desde la barbilla.', 9),
(126, 2, 'Lo siento', 'Mano plana con los dedos juntos sobre el pecho.', 'img/lsc/senas/lo-siento.png', 1, 'Haz un suave círculo sobre el pecho.', 10),
(127, 2, 'Bienvenido', 'Una mano plana con la palma hacia arriba, junto a la otra mano plana con la palma hacia arriba.', 'img/lsc/senas/bienvenido.png', 0, NULL, 11),
(128, 2, 'Qué gusto', 'Una mano plana con la palma hacia arriba.', 'img/lsc/senas/que-gusto.png', 1, 'Se mueve hacia adentro y arriba, encontrando la otra mano.', 12),
(129, 2, 'Saludo', 'Palmas cara a cara con los dedos entrelazados.', 'img/lsc/senas/saludo.png', 1, 'Gira las manos suavemente.', 13),
(130, 2, 'Presentación', 'Palmas planas, cara a cara, a la altura de la cintura.', 'img/lsc/senas/presentacion.png', 1, 'Bájalas.', 14),
(131, 2, 'Nombre', 'Dedo índice extendido.', 'img/lsc/senas/nombre.png', 1, 'Haz un movimiento corto hacia abajo.', 15),
(132, 2, 'Apellido', 'Dedos índice y medio extendidos.', 'img/lsc/senas/apellido.png', 1, 'Toca la frente dos veces.', 16),
(133, 2, 'Soy de', 'Dedo índice apoyado contra el pecho.', 'img/lsc/senas/soy-de.png', 1, 'El otro índice se mueve hacia afuera.', 17),
(134, 2, 'Vine aquí', 'Palmas hacia abajo.', 'img/lsc/senas/vine-aqui.png', 1, 'Bájalas deliberadamente al nivel de la cintura y para.', 18),
(135, 2, 'Vivir', 'Manos planas frente al cuerpo.', 'img/lsc/senas/vivir.png', 1, 'Suben del pecho a la barbilla.', 19),
(136, 2, 'Nosotros', 'Manos ahuecadas con los dedos entrelazados, frente al pecho.', 'img/lsc/senas/nosotros.png', 1, 'Gira las manos.', 20),
(137, 2, 'Conocerse', 'Manos planas y ahuecadas con las palmas frente a frente.', 'img/lsc/senas/conocerse.png', 0, NULL, 21),
(138, 2, 'Invitar', 'Manos planas con las palmas hacia arriba.', 'img/lsc/senas/invitar.png', 1, 'Tráelas hacia el cuerpo.', 22),
(139, 2, 'Preguntar', 'Dedos índices cerca de la boca.', 'img/lsc/senas/preguntar.png', 1, 'Muévelos ligeramente hacia afuera y abajo.', 23),
(140, 2, 'Seña', 'Manos ahuecadas, cerca una de la otra.', 'img/lsc/senas/sena.png', 0, NULL, 24);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contenidos_educativos`
--
ALTER TABLE `contenidos_educativos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`);

--
-- Indices de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ip_email` (`ip`,`email`),
  ADD KEY `idx_actualizado` (`actualizado`);

--
-- Indices de la tabla `palabras_repasadas`
--
ALTER TABLE `palabras_repasadas`
  ADD PRIMARY KEY (`id_palabra`),
  ADD KEY `fk_palabras_usuario` (`id_usuario`);

--
-- Indices de la tabla `progreso_usuario`
--
ALTER TABLE `progreso_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_contenido` (`usuario_id`,`contenido_id`),
  ADD KEY `fk_progreso_usuario_usuario` (`usuario_id`),
  ADD KEY `fk_progreso_usuario_contenido` (`contenido_id`);

--
-- Indices de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token_hash` (`token_hash`),
  ADD KEY `idx_id_usuario` (`id_usuario`),
  ADD KEY `idx_expira` (`expira`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vocabulario_senas`
--
ALTER TABLE `vocabulario_senas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_contenido_palabra` (`contenido_id`,`palabra`),
  ADD KEY `idx_contenido_orden` (`contenido_id`,`orden`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contenidos_educativos`
--
ALTER TABLE `contenidos_educativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `palabras_repasadas`
--
ALTER TABLE `palabras_repasadas`
  MODIFY `id_palabra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `progreso_usuario`
--
ALTER TABLE `progreso_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `vocabulario_senas`
--
ALTER TABLE `vocabulario_senas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `palabras_repasadas`
--
ALTER TABLE `palabras_repasadas`
  ADD CONSTRAINT `fk_palabras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `progreso_usuario`
--
ALTER TABLE `progreso_usuario`
  ADD CONSTRAINT `fk_progreso_usuario_contenido` FOREIGN KEY (`contenido_id`) REFERENCES `contenidos_educativos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progreso_usuario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD CONSTRAINT `fk_recuperacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `vocabulario_senas`
--
ALTER TABLE `vocabulario_senas`
  ADD CONSTRAINT `fk_vocabulario_contenido` FOREIGN KEY (`contenido_id`) REFERENCES `contenidos_educativos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
