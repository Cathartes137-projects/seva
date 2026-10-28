-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-08-2026 a las 23:18:37
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
-- Base de datos: `seva_educativo`
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
(1, 'Alfabeto dactilológico', 'Aprende a deletrear cada letra del abecedario con las manos.', 'img/lecciones/alfabeto.jpg', 'video/alfabeto.mp4', 'basico', 1, '2026-08-04 19:11:08'),
(2, 'Saludos y presentaciones', 'Hola, buenos días, ¿cómo estás?, mucho gusto.', 'img/lecciones/saludos.jpg', 'video/saludos.mp4', 'basico', 2, '2026-08-04 19:11:08'),
(3, 'Números del 1 al 10', 'Cómo representar los números básicos con señas.', 'img/lecciones/numeros.jpg', 'video/numeros.mp4', 'basico', 3, '2026-08-04 19:11:08'),
(4, 'Colores', 'Vocabulario de colores primarios y secundarios.', 'img/lecciones/colores.jpg', 'video/colores.mp4', 'basico', 4, '2026-08-04 19:11:08'),
(5, 'La familia', 'Mamá, papá, hermano, hermana, abuelo, abuela.', 'img/lecciones/familia.jpg', 'video/familia.mp4', 'basico', 5, '2026-08-04 19:11:08'),
(6, 'Días de la semana', 'Lunes a domingo, y expresiones de tiempo básicas.', 'img/lecciones/dias.jpg', 'video/dias.mp4', 'intermedio', 6, '2026-08-04 19:11:08'),
(7, 'Emociones y sentimientos', 'Feliz, triste, enojado, sorprendido, cansado.', 'img/lecciones/emociones.jpg', 'video/emociones.mp4', 'intermedio', 7, '2026-08-04 19:11:08'),
(8, 'Comida y bebida', 'Vocabulario esencial para pedir y hablar de alimentos.', 'img/lecciones/comida.jpg', 'video/comida.mp4', 'intermedio', 8, '2026-08-04 19:11:08'),
(9, 'Preguntas básicas', 'Qué, quién, dónde, cuándo, por qué, cómo.', 'img/lecciones/preguntas.jpg', 'video/preguntas.mp4', 'intermedio', 9, '2026-08-04 19:11:08'),
(10, 'Lugares y direcciones', 'Casa, escuela, hospital, izquierda, derecha, cerca, lejos.', 'img/lecciones/lugares.jpg', 'video/lugares.mp4', 'avanzado', 10, '2026-08-04 19:11:08'),
(11, 'El clima', 'Sol, lluvia, frío, calor, viento.', 'img/lecciones/clima.jpg', 'video/clima.mp4', 'avanzado', 11, '2026-08-04 19:11:08'),
(12, 'Verbos comunes', 'Comer, dormir, trabajar, estudiar, jugar, viajar.', 'img/lecciones/verbos.jpg', 'video/verbos.mp4', 'avanzado', 12, '2026-08-04 19:11:08');

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
(1, 1234567900, 1, 1, '2026-08-04 19:11:08'),
(2, 1234567900, 2, 1, '2026-08-04 19:11:08'),
(3, 1234567900, 3, 1, '2026-08-04 19:11:08');

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
(1234567899, 'Admin', 'nosemeocurrenada@gmail.com', '$2y$10$2bM.hIO6Fs/5ycfflVvvOuO/AjIApbEH5q2pelOvUTCtkioBV5XGe', '2026-07-23 22:39:34'),
(1234567900, 'Edwin Camacho navarro', 'edwin1234@gmail.com', '$2y$10$v11rirE3RDlB5kt98w3pCeL5U7MzmnptpENL73tXs3UBPWDZwGA.i', '2026-08-03 21:03:35');

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
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT de la tabla `palabras_repasadas`
--
ALTER TABLE `palabras_repasadas`
  MODIFY `id_palabra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `progreso_usuario`
--
ALTER TABLE `progreso_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1234567901;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
