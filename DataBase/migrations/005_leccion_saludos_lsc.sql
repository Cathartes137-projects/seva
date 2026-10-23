-- =============================================================================
-- SEVA - Migración 005: Lección 2 "Saludos en LSC"
-- =============================================================================
-- Crea/actualiza la lección 2 y reemplaza su vocabulario por los 24 saludos
-- del archivo data/lsc/senas.json. Las imágenes viven en img/lsc/senas/.
--
-- Idempotente: puede ejecutarse varias veces sin duplicar registros.
--
-- Uso:
--   mysql -u root -h 127.0.0.1 -P 3306 --default-character-set=utf8mb4 \
--     seva_educativo < DataBase/migrations/005_leccion_saludos_lsc.sql
-- =============================================================================

START TRANSACTION;

INSERT INTO `contenidos_educativos`
    (`id`, `titulo`, `descripcion`, `ruta_imagen`, `ruta_video`, `nivel`, `orden`)
VALUES
    (2, 'Saludos y presentaciones · LSC', 'Aprende 24 saludos, presentaciones y expresiones de cortesía en Lengua de Señas Colombiana (LSC), con imagen guía, descripción gestual e instrucciones de movimiento.', 'img/lecciones/saludos.jpg', 'video/saludos.mp4', 'basico', 2)
ON DUPLICATE KEY UPDATE
    `titulo` = 'Saludos y presentaciones · LSC',
    `descripcion` = 'Aprende 24 saludos, presentaciones y expresiones de cortesía en Lengua de Señas Colombiana (LSC), con imagen guía, descripción gestual e instrucciones de movimiento.',
    `ruta_imagen` = 'img/lecciones/saludos.jpg',
    `nivel` = 'basico',
    `orden` = 2;

DELETE FROM `vocabulario_senas` WHERE `contenido_id` = 2;

INSERT INTO `vocabulario_senas`
    (`contenido_id`, `palabra`, `descripcion`, `imagen`, `con_movimiento`, `instruccion_movimiento`, `orden`)
VALUES
    (2, 'Hola formal', 'Mano plana cerca de la frente, con la palma hacia el cuerpo.', 'img/lsc/senas/hola-formal.png', 1, 'Haz un arco fuerte hacia adelante.', 1),
    (2, 'Hola informal', 'Mano cerca de la frente con el índice extendido.', 'img/lsc/senas/hola-informal.png', 1, 'Haz un pequeño arco hacia adelante.', 2),
    (2, 'Buenos días', 'Mano plana cerca de la barbilla.', 'img/lsc/senas/buenos-dias.png', 1, 'Bájala para unirse con la otra mano plana.', 3),
    (2, 'Buenas tardes', 'Mano plana cerca de la barbilla.', 'img/lsc/senas/buenas-tardes.png', 1, 'Bájala para unirse con la otra mano plana a la altura del pecho.', 4),
    (2, 'Buenas noches', 'Dedos índice y medio extendidos de una mano, sobre la otra mano plana y ahuecada.', 'img/lsc/senas/buenas-noches.png', 1, 'Baja los dedos hacia la otra mano.', 5),
    (2, 'Adiós', 'Mano plana cerca de la frente, con la palma hacia el cuerpo.', 'img/lsc/senas/adios.png', 1, 'Saluda de lado a lado.', 6),
    (2, 'Nos vemos', 'Manos cerca del pecho, palmas cara a cara, dedos ahuecados.', 'img/lsc/senas/nos-vemos.png', 0, NULL, 7),
    (2, 'Por favor', 'Palma abierta con los dedos juntos, sobre el centro del pecho.', 'img/lsc/senas/por-favor.png', 0, NULL, 8),
    (2, 'Gracias', 'Mano plana con los dedos juntos, junto a la barbilla.', 'img/lsc/senas/gracias.png', 1, 'Muévela hacia adelante y abajo desde la barbilla.', 9),
    (2, 'Lo siento', 'Mano plana con los dedos juntos sobre el pecho.', 'img/lsc/senas/lo-siento.png', 1, 'Haz un suave círculo sobre el pecho.', 10),
    (2, 'Bienvenido', 'Una mano plana con la palma hacia arriba, junto a la otra mano plana con la palma hacia arriba.', 'img/lsc/senas/bienvenido.png', 0, NULL, 11),
    (2, 'Qué gusto', 'Una mano plana con la palma hacia arriba.', 'img/lsc/senas/que-gusto.png', 1, 'Se mueve hacia adentro y arriba, encontrando la otra mano.', 12),
    (2, 'Saludo', 'Palmas cara a cara con los dedos entrelazados.', 'img/lsc/senas/saludo.png', 1, 'Gira las manos suavemente.', 13),
    (2, 'Presentación', 'Palmas planas, cara a cara, a la altura de la cintura.', 'img/lsc/senas/presentacion.png', 1, 'Bájalas.', 14),
    (2, 'Nombre', 'Dedo índice extendido.', 'img/lsc/senas/nombre.png', 1, 'Haz un movimiento corto hacia abajo.', 15),
    (2, 'Apellido', 'Dedos índice y medio extendidos.', 'img/lsc/senas/apellido.png', 1, 'Toca la frente dos veces.', 16),
    (2, 'Soy de', 'Dedo índice apoyado contra el pecho.', 'img/lsc/senas/soy-de.png', 1, 'El otro índice se mueve hacia afuera.', 17),
    (2, 'Vine aquí', 'Palmas hacia abajo.', 'img/lsc/senas/vine-aqui.png', 1, 'Bájalas deliberadamente al nivel de la cintura y para.', 18),
    (2, 'Vivir', 'Manos planas frente al cuerpo.', 'img/lsc/senas/vivir.png', 1, 'Suben del pecho a la barbilla.', 19),
    (2, 'Nosotros', 'Manos ahuecadas con los dedos entrelazados, frente al pecho.', 'img/lsc/senas/nosotros.png', 1, 'Gira las manos.', 20),
    (2, 'Conocerse', 'Manos planas y ahuecadas con las palmas frente a frente.', 'img/lsc/senas/conocerse.png', 0, NULL, 21),
    (2, 'Invitar', 'Manos planas con las palmas hacia arriba.', 'img/lsc/senas/invitar.png', 1, 'Tráelas hacia el cuerpo.', 22),
    (2, 'Preguntar', 'Dedos índices cerca de la boca.', 'img/lsc/senas/preguntar.png', 1, 'Muévelos ligeramente hacia afuera y abajo.', 23),
    (2, 'Seña', 'Manos ahuecadas, cerca una de la otra.', 'img/lsc/senas/sena.png', 0, NULL, 24);

COMMIT;
