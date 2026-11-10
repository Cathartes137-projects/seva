<?php
require '../config/conexion.php';
require '../config/seguridad.php';

seguridad_iniciar_sesion();

// Validación estricta de sesión de usuario
seguridad_requiere_login();

$usuario_id = $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 1;

// Procesar completitud de lección vía POST (AJAX o envío tradicional)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['marcar_completada'])) {
    // Toda petición POST exige un token CSRF válido (D05)
    seguridad_validar_csrf('leccion.php?id=' . $id);

    try {
        $stmt_comp = $pdo->prepare('INSERT INTO progreso_usuario (usuario_id, contenido_id, completado, fecha_completado) 
            VALUES (?, ?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE completado = 1, fecha_completado = NOW()');
        $stmt_comp->execute([$usuario_id, $id]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['exito' => true]);
            exit;
        }
    } catch (PDOException $e) {
        error_log('Error al registrar progreso en progreso_usuario: ' . $e->getMessage());
    }
}

/**
 * Convierte un texto en una clave estable para el DOM
 * (p. ej. "¿Cómo estás?" → "como_estas"). Se usa para identificar cada
 * tarjeta del modo guiado aunque cambien los IDs de la base de datos.
 */
function seva_slug_palabra(string $texto): string
{
    $texto = strtolower(trim($texto));
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        '¿' => '', '?' => '', '¡' => '', '!' => '',
    ]);
    $texto = preg_replace('/[^a-z0-9]+/', '_', (string) $texto);

    return trim((string) $texto, '_');
}

// Datos base de la lección
$leccion = [
    'id'          => $id,
    'titulo'      => 'Saludos y presentaciones',
    'nivel'       => 'Básico',
    'duracion'    => '8 min',
    'descripcion' => 'Aprende a saludar, presentarte y despedirte en Lengua de Señas: la base de cualquier conversación.',
    'ruta_video'  => '',
    'ruta_imagen' => 'img/lecciones/saludos.jpg',
    'objetivos'   => [
        'Saludar y responder con fluidez a un saludo inicial',
        'Presentar tu nombre usando la dactilología básica',
        'Preguntar "¿cómo estás?" y despedirte con cortesía',
    ],
    'vocabulario' => [
        [
            'id' => 'hola',
            'palabra' => 'Hola',
            'descripcion' => 'Con la palma abierta orientada hacia el frente, realiza un movimiento suave de lado a lado cerca de la sien.',
        ],
        [
            'id' => 'buenos_dias',
            'palabra' => 'Buenos días',
            'descripcion' => 'Junta las yemas de los dedos de una mano sobre el pecho y asciende el brazo suavemente simulando la salida del sol.',
        ],
        [
            'id' => 'mucho_gusto',
            'palabra' => 'Mucho gusto',
            'descripcion' => 'Con los pulgares hacia arriba, toca primero suavemente tu pecho y luego extiende ambas manos hacia tu interlocutor.',
        ],
        [
            'id' => 'como_estas',
            'palabra' => '¿Cómo estás?',
            'descripcion' => 'Mueve ambas manos con pulgares arriba alternadamente hacia adelante, acompañando con una expresión facial interrogativa.',
        ],
        [
            'id' => 'mi_nombre',
            'palabra' => 'Mi nombre es',
            'descripcion' => 'Toca dos veces los dedos índice y medio entre ambas manos de forma cruzada y procede a deletrear tu nombre.',
        ],
        [
            'id' => 'adios',
            'palabra' => 'Adiós',
            'descripcion' => 'Abre y cierra suavemente los cuatro dedos con la palma extendida hacia la otra persona en gesto de despedida.',
        ],
    ],
];

$leccion_completada = false;

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, titulo, descripcion, ruta_imagen, ruta_video, nivel FROM contenidos_educativos WHERE id = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        if ($fila) {
            $leccion['id']          = $fila['id'];
            $leccion['titulo']      = $fila['titulo'];
            if (!empty($fila['descripcion'])) {
                $leccion['descripcion'] = $fila['descripcion'];
            }
            if (!empty($fila['ruta_imagen'])) {
                // Fallback (D14): solo se acepta una ruta existente y sin traversal
                $ruta_img = ltrim(str_replace('\\', '/', (string) $fila['ruta_imagen']), '/');
                $leccion['ruta_imagen'] = ($ruta_img !== '' && strpos($ruta_img, '..') === false
                    && is_file(__DIR__ . '/' . $ruta_img))
                    ? $ruta_img
                    : 'img/lecciones/default.svg';
            }
            if (!empty($fila['ruta_video'])) {
                // La ruta se valida en el render con is_file(); aquí se normaliza
                $ruta_vid = ltrim(str_replace('\\', '/', (string) $fila['ruta_video']), '/');
                $leccion['ruta_video'] = ($ruta_vid !== '' && strpos($ruta_vid, '..') === false)
                    ? $ruta_vid
                    : '';
            }
            if (!empty($fila['nivel'])) {
                $leccion['nivel'] = ucfirst(strtolower($fila['nivel']));
            }

            // Vocabulario propio de la lección (D16). Si la consulta falla
            // (por ejemplo, migración sin aplicar) se conserva el array base.
            try {
                $stmt_voc = $pdo->prepare(
                    'SELECT id, palabra, descripcion, imagen, con_movimiento, instruccion_movimiento
                     FROM vocabulario_senas
                     WHERE contenido_id = ?
                     ORDER BY orden ASC, id ASC'
                );
                $stmt_voc->execute([$leccion['id']]);
                $filas_voc = $stmt_voc->fetchAll();

                $leccion['vocabulario'] = [];
                $claves_usadas = [];
                foreach ($filas_voc as $fila_voc) {
                    $clave = seva_slug_palabra((string) $fila_voc['palabra']);
                    if ($clave === '') {
                        $clave = 'sena_' . $fila_voc['id'];
                    }
                    if (isset($claves_usadas[$clave])) {
                        $clave .= '_' . $fila_voc['id'];
                    }
                    $claves_usadas[$clave] = true;

                    $ruta_img_voc = ltrim(str_replace('\\', '/', (string) ($fila_voc['imagen'] ?? '')), '/');
                    $imagen_voc = ($ruta_img_voc !== '' && strpos($ruta_img_voc, '..') === false
                        && is_file(__DIR__ . '/' . $ruta_img_voc))
                        ? $ruta_img_voc
                        : '';

                    $leccion['vocabulario'][] = [
                        'id'          => $clave,
                        'palabra'     => $fila_voc['palabra'],
                        'descripcion' => $fila_voc['descripcion'],
                        'imagen'      => $imagen_voc,
                        'con_movimiento' => !empty($fila_voc['con_movimiento']),
                        'instruccion' => (string) ($fila_voc['instruccion_movimiento'] ?? ''),
                    ];
                }
            } catch (PDOException $e) {
                error_log('Lección - no se pudo cargar el vocabulario: ' . $e->getMessage());
            }
        }

        // Consultar si el usuario ya completó esta lección
        $stmt_prog = $pdo->prepare('SELECT completado FROM progreso_usuario WHERE usuario_id = ? AND contenido_id = ?');
        $stmt_prog->execute([$usuario_id, $id]);
        $prog_fila = $stmt_prog->fetch();
        if ($prog_fila && (int)$prog_fila['completado'] === 1) {
            $leccion_completada = true;
        }
    }
} catch (PDOException $e) {
    error_log('Lección - error al consultar base de datos: ' . $e->getMessage());
}

$nivel_clase = strtolower($leccion['nivel'] ?? 'basico');
if ($nivel_clase !== 'intermedio' && $nivel_clase !== 'avanzado') {
    $nivel_clase = 'basico';
}

$es_alfabeto = stripos((string) $leccion['titulo'], 'alfabeto') !== false;
$total_senas = count($leccion['vocabulario']);
$primera_sena = $total_senas > 0 ? (string) $leccion['vocabulario'][0]['palabra'] : '';
$porcentaje_inicial = $total_senas > 0 ? round(100 / $total_senas, 2) : 0;

// Consejos de errores comunes LSC vinculados estrictamente a cada seña (palabra)
$consejosLSC = [
    'Rojo'   => 'No confundir con "Negro": el dedo índice apunta hacia adelante, no hacia el pecho.',
    'Azul'   => 'Mantén la palma hacia adelante y los dedos índice y medio juntos; no los separes.',
    'Verde'  => 'El pulgar y el meñique forman un cuerno; el resto de dedos permanecen doblados.',
    'Amarillo' => 'Todos los dedos abiertos y extendidos; movimiento rápido de apertura/cierre.',
    'Blanco' => 'Palma abierta, dedos juntos, movimiento lineal desde el pecho hacia afuera.',
    'Negro'  => 'Puño cerrado con pulgar recogido; no confundir con "Rojo".',
    'Rosado' => 'Índice traza un círculo pequeño; movimiento suave y continuo.',
    'Morado' => 'Índice y medio en V, palma adelante; no cruzar los dedos.',
    'Café'   => 'Pulgar e índice tocándose en punta; círculo pequeño.',
    'Dorado' => 'Dedos abiertos; movimiento de destello al abrir y cerrar.',
    'Gris'   => 'Índice y medio extendidos, palma hacia adentro; movimiento lateral.',
    'Anaranjado' => 'Pulgar y meñique extendidos, medios doblados; arco amplio.',
    'Plateado' => 'Todos los dedos abiertos; movimiento de reflejo metálico.',
    'Púrpura' => 'Índice y medio cruzados; no confundir con "Azul".',
    'Oscuro' => 'Puño cerrado, pulgar hacia adentro; acercar al pecho lentamente.',
    'Claro'  => 'Índice apuntando arriba, palma adelante; movimiento breve hacia arriba.',
    'Brillante' => 'Todos los dedos abiertos; apertura y cierre rápido de dedos.',
    'Opaco'  => 'Todos los dedos doblados hacia la palma, pulgar sobre ellos; mano quieta.',
    'Transparente' => 'Índice y medio separados, palma adelante; movimiento suave hacia adelante.',
    'Fosforescente' => 'Todos los dedos abiertos; movimiento sostenido simulando brillo.',
    'A' => 'Puño cerrado con pulgar al costado, no hacia arriba.',
    'B' => 'Cuatro dedos juntos y rectos, palma al frente; pulgar cruzado delante.',
    'C' => 'Dedos y pulgar curvados formando C; espacio abierto entre pulgar e índice.',
    'D' => 'Índice estirado arriba; pulgar toca anular y meñique formando círculo.',
    'E' => 'Cuatro dedos hacia la palma (puño flojo); pulgar recogido contra ellos.',
    'F' => 'Índice estirado; pulgar y medios/anular/meñique hacia la palma.',
    'G' => 'Índice apuntando al costado (no arriba); giro de muñeca hacia arriba.',
    'H' => 'Índice y medio juntos al costado; pulgar y demás recogidos.',
    'I' => 'Índice, medio y anular hacia la palma; meñique y pulgar hacia afuera.',
    'J' => 'Índice hacia abajo; pulgar al costado; traza una J en el aire.',
    'K' => 'Índice arriba y pulgar al costado formando escuadra.',
    'L' => 'Índice arriba y pulgar al costado formando L.',
    'M' => 'Puño, pulgar debajo de índice, medio y anular.',
    'N' => 'Puño, pulgar debajo de índice y medio únicamente.',
    'Ñ (eñe)' => 'Mismo que N con movimiento ondulado de lado a lado.',
    'O' => 'Pulgar unido a todos los dedos formando círculo cerrado.',
    'P' => 'Muñeca girada abajo; índice al suelo, pulgar toca medio.',
    'Q' => 'Todas las puntas unidas con pulgar apuntando abajo.',
    'R' => 'Índice cruzado sobre medio; anular y meñique doblados.',
    'S' => 'Índice arriba, demás cerrados; giro de muñeca trazando S.',
    'T' => 'Medio, anular y meñique arriba; índice y pulgar se tocan.',
    'U' => 'Índice y meñique arriba (cuernos); medio y anular doblados.',
    'V' => 'Índice y medio arriba separados en V.',
    'W' => 'Índice, medio y anular arriba separados; meñique y pulgar recogidos.',
    'X' => 'Índice doblado en gancho; demás cerrados, pulgar al costado.',
    'Y' => 'Pulgar y meñique hacia extremos (hang loose).',
    'Z' => 'Índice y medio juntos al frente; traza Z en el aire.',
    'Hola' => 'Palma abierta al frente, movimiento suave de lado a lado cerca de la sien.',
    'Adiós' => 'Cuatro dedos abren y cierran con palma extendida hacia la otra persona.',
    'Gracias' => 'Mano plana, dedos juntos, se mueve hacia adelante y abajo desde la barbilla.',
    'Por favor' => 'Palma abierta, dedos juntos, sobre el centro del pecho.',
    'Lo siento' => 'Mano plana, dedos juntos, círculo suave sobre el pecho.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($leccion['titulo']) ?> · SEVA</title>
    <script>document.documentElement.classList.add('js')</script>
    <!-- Fuentes Web Accesibles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Oficiales SEVA -->
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        /* Modo guiado: una seña por pantalla */
        .seccion-wizard {
            margin-top: 1.5rem;
        }
        .wizard-cabecera {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .wizard-eyebrow {
            margin: 0 0 .25rem;
            color: var(--azul);
            font: 700 .72rem 'Space Mono', monospace;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .wizard-cabecera h2 {
            margin: 0 0 .25rem;
            font-size: 1.35rem;
        }
        .wizard-intro {
            margin: 0;
            color: var(--muted);
            font-size: .92rem;
        }
        .wizard-progreso {
            width: min(100%, 340px);
            flex: 1 1 260px;
        }
        .wizard-progreso-texto {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .4rem;
            color: var(--ink);
            font: 700 .82rem 'Inter', sans-serif;
        }
        .wizard-progreso-barra {
            height: 12px;
            overflow: hidden;
            border-radius: 99px;
            background: var(--line);
            box-shadow: inset 0 1px 2px rgba(4, 20, 36, .08);
        }
        .wizard-progreso-barra span {
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--azul), var(--verde-oscuro));
            transition: width .25s ease;
        }
        .wizard {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 1.1rem;
            background: #fff;
            box-shadow: var(--sombra);
        }
        .wizard-paso {
            display: grid;
            grid-template-columns: minmax(220px, .85fr) minmax(0, 1.15fr);
            gap: clamp(1rem, 3vw, 1.75rem);
            align-items: center;
            padding: clamp(1rem, 2.5vw, 1.5rem);
        }
        .wizard.wizard-activo .wizard-paso {
            display: none;
        }
        .wizard.wizard-activo .wizard-paso.activo {
            display: grid;
        }
        .wizard-media {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: .9rem;
            background: #f6f9ff;
        }
        .wizard-media img {
            display: block;
            width: 100%;
            max-height: 380px;
            object-fit: contain;
        }
        .wizard-contenido {
            min-width: 0;
        }
        .wizard-etiqueta {
            margin: 0 0 .15rem;
            color: var(--muted);
            font: 700 .72rem 'Space Mono', monospace;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .wizard-contenido h3 {
            margin: 0 0 .55rem;
            color: var(--azul);
            font: 800 clamp(2rem, 6vw, 3.5rem) 'Baloo 2', sans-serif;
            line-height: 1.05;
            overflow-wrap: anywhere;
        }
        .wizard-descripcion {
            margin: 0 0 .85rem;
            color: var(--text);
            font-size: 1rem;
            line-height: 1.65;
        }
        .wizard-movimiento {
            margin: 0 0 .9rem;
        }
        .etq-movimiento {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin: 0 0 .45rem;
            padding: .2rem .6rem;
            border-radius: 99px;
            background: var(--sol-cl, #FFF4D6);
            color: #7A4B00;
            font: 700 .72rem 'Inter', sans-serif;
            letter-spacing: .02em;
        }
        .wizard-paso .instruccion-movimiento {
            margin: 0;
            padding: .55rem .7rem;
            border-left: 4px solid var(--sol, #FFB400);
            border-radius: .4rem;
            background: var(--sol-cl, #FFF4D6);
            color: var(--text);
            font-size: .85rem;
            line-height: 1.45;
        }
        .wizard-acciones-sena {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1rem;
        }
        .wizard-btn-camara,
        .wizard-nav,
        .wizard-form-final .btn,
        .wizard-pie-secundario .btn {
            min-height: 44px;
            min-width: 44px;
        }
        .wizard-btn-camara:focus-visible,
        .wizard-nav:focus-visible,
        .wizard-form-final .btn:focus-visible,
        .wizard-pie-secundario .btn:focus-visible {
            outline: 3px solid var(--sol, #FFB400);
            outline-offset: 3px;
        }
        .wizard-pie {
            padding: 1rem clamp(1rem, 2.5vw, 1.5rem) 1.15rem;
            border-top: 1px solid var(--line);
            background: #fbfcff;
        }
        .wizard-pie-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            flex-wrap: wrap;
        }
        .wizard-paso-actual {
            color: var(--muted);
            font: 700 .82rem 'Space Mono', monospace;
            white-space: nowrap;
        }
        .wizard-nav:disabled {
            opacity: .45;
            cursor: not-allowed;
        }
        .wizard-form-final {
            display: inline-flex;
            margin: 0;
        }
        .wizard-pie.wizard-js .wizard-form-final {
            display: none;
        }
        .wizard-pie.wizard-js.es-ultimo .wizard-form-final {
            display: inline-flex;
        }
        .wizard-pie.wizard-js.es-ultimo #wizard-siguiente {
            display: none;
        }
        .wizard-pie-secundario {
            display: flex;
            justify-content: flex-end;
            margin-top: .85rem;
        }
        .wizard-vacio {
            padding: 1.1rem 1.25rem;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: #fff;
        }
        .wizard-vacio h2 {
            margin: 0 0 .35rem;
            font-size: 1.35rem;
        }
        .wizard-vacio p {
            margin: 0 0 1rem;
            color: var(--muted);
            font-size: .92rem;
        }
        .wizard-vacio .wizard-pie-secundario {
            justify-content: flex-start;
        }
        @media (max-width: 760px) {
            .wizard-cabecera {
                align-items: flex-start;
            }
            .wizard-progreso {
                width: 100%;
                flex-basis: 100%;
            }
            .wizard-paso,
            .wizard.wizard-activo .wizard-paso.activo {
                grid-template-columns: 1fr;
            }
            .wizard-media {
                min-height: 210px;
            }
            .wizard-media img {
                max-height: 300px;
            }
            .wizard-pie-nav {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .wizard-paso-actual {
                grid-column: 1 / -1;
                grid-row: 1;
                text-align: center;
            }
            .wizard-form-final {
                grid-column: 1 / -1;
            }
            .wizard-form-final .btn {
                width: 100%;
            }
            .wizard-pie-secundario {
                justify-content: stretch;
            }
            .wizard-pie-secundario .btn {
                width: 100%;
                justify-content: center;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .wizard-progreso-barra span {
                transition: none;
            }
        }

        /* Selector de vista dual */
        .vista-selector {
            display: flex;
            gap: .5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            border: 1px solid var(--line);
            border-radius: .8rem;
            padding: .35rem;
            background: #fff;
        }
        .vista-btn {
            flex: 1;
            padding: .55rem .9rem;
            border: 1px solid transparent;
            border-radius: .6rem;
            background: transparent;
            color: var(--muted);
            font: 700 .85rem 'Inter', sans-serif;
            cursor: pointer;
            text-align: center;
        }
        .vista-btn.activo {
            background: var(--azul);
            color: #fff;
            border-color: var(--azul);
        }
        .vista-btn:hover:not(.activo) {
            background: #f4f8ff;
            color: var(--ink);
        }

        /* Buscador de señas */
        .buscador-senas {
            margin-bottom: 1rem;
            position: relative;
        }
        .buscador-senas input {
            width: 100%;
            padding: .7rem 1rem .7rem 2.6rem;
            border: 1px solid var(--line);
            border-radius: .8rem;
            font: 400 .95rem 'Inter', sans-serif;
            background: #fff;
            box-sizing: border-box;
        }
        .buscador-senas input:focus {
            outline: none;
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(4, 60, 120, .1);
        }
        .buscador-icono {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1.1rem;
        }

        /* Badges LSC */
        .badge-lsc {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .6rem;
            border-radius: 99px;
            font: 700 .72rem 'Inter', sans-serif;
            margin-top: .35rem;
        }
        .badge-movimiento {
            background: #e8f2ff;
            color: #0b4d8f;
            border: 1px solid #bfe0ff;
        }
        .badge-estatica {
            background: #f4f6f8;
            color: #5b6b7c;
            border: 1px solid #d7dee6;
        }

        /* Vista rejilla */
        .vista-rejilla {
            display: none;
            padding-top: 1rem;
        }
        .vista-rejilla.activa {
            display: block;
        }
        .grid-senas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .tarjeta-sena {
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: #fff;
            box-shadow: var(--sombra);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .tarjeta-sena-media {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f6f9ff;
            padding: .75rem;
        }
        .tarjeta-sena-media img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }
        .tarjeta-sena-contenido {
            padding: .85rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }
        .tarjeta-sena h3 {
            margin: 0;
            font: 800 1.25rem 'Baloo 2', sans-serif;
            color: var(--azul);
        }
        .tarjeta-sena p {
            margin: 0;
            color: var(--text);
            font-size: .88rem;
            line-height: 1.5;
            flex: 1;
        }
        .tarjeta-sena .btn {
            margin-top: auto;
            width: 100%;
            text-align: center;
        }

        /* Controles de velocidad */
        .controles-velocidad {
            display: flex;
            gap: .5rem;
            justify-content: center;
            margin-top: .75rem;
            padding-top: .75rem;
            border-top: 1px solid var(--line);
        }
        .btn-velocidad {
            padding: .45rem .85rem;
            border: 1px solid var(--line);
            border-radius: .6rem;
            background: #fff;
            color: var(--ink);
            font: 700 .85rem 'Inter', sans-serif;
            cursor: pointer;
        }
        .btn-velocidad.activa {
            background: var(--azul);
            color: #fff;
            border-color: var(--azul);
        }
        .btn-velocidad:hover:not(.activa) {
            background: #f4f8ff;
        }

        /* Sección pedagógica colapsable */
        .seccion-pedagogica {
            margin-top: 1.5rem;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: #fff;
        }
        .detalle-pedagogico {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--line);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font: 700 .95rem 'Inter', sans-serif;
            color: var(--ink);
            background: #fbfcff;
        }
        .detalle-pedagogico:hover {
            background: #f4f8ff;
        }
        .detalle-pedagogico svg {
            width: 20px;
            height: 20px;
            color: var(--azul);
            transition: transform .2s ease;
        }
        .detalle-pedagogico.abierto svg {
            transform: rotate(180deg);
        }
        .contenido-pedagogico {
            display: none;
            padding: 1.25rem;
        }
        .contenido-pedagogico.abierto {
            display: block;
        }
        .contenido-pedagogico h3 {
            margin: 0 0 .75rem;
            font: 800 1.3rem 'Baloo 2', sans-serif;
            color: var(--azul);
        }
        .contenido-pedagogico ul {
            margin: 0;
            padding-left: 1.3rem;
            color: var(--text);
            line-height: 1.7;
        }
        .contenido-pedagogico li {
            margin-bottom: .5rem;
        }

        .oculto {
            display: none !important;
        }

        /* Barra de herramientas superior independiente */
        .toolbar-superior {
            display: flex;
            gap: .75rem;
            margin: 1rem 0 1.5rem 0;
            padding: .75rem 1rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 1rem;
            box-shadow: var(--sombra);
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar-superior .vista-selector {
            border: none;
            padding: 0;
            background: transparent;
            box-shadow: none;
            margin: 0;
            flex: 1 1 260px;
        }

        /* Modal de zoom / vista ampliada */
        .modal-zoom-fondo {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(3, 12, 24, .75);
            backdrop-filter: blur(4px);
        }
        .modal-zoom-fondo.abierto {
            display: flex;
        }
        .modal-zoom {
            width: min(640px, 92vw);
            max-height: 85vh;
            border-radius: 1.2rem;
            background: #fff;
            box-shadow: 0 24px 70px rgba(0,0,0,.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .modal-zoom-cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--line);
            background: #fbfcff;
        }
        .modal-zoom-cabecera h2 {
            margin: 0;
            font: 800 1.35rem 'Baloo 2', sans-serif;
            color: var(--ink);
        }
        .modal-zoom-cerrar {
            width: 36px;
            height: 36px;
            border: 1px solid var(--line);
            border-radius: .6rem;
            background: #fff;
            color: var(--ink);
            font: 700 1.2rem 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-zoom-cerrar:hover {
            background: #f4f8ff;
        }
        .modal-zoom-contenido {
            padding: 1.5rem;
            overflow-y: auto;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .modal-zoom-imagen-wrap {
            display: flex;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: #f6f9ff;
            min-height: 260px;
            align-items: center;
        }
        .modal-zoom-imagen-wrap img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            display: block;
        }
        .modal-zoom-descripcion {
            margin: 0;
            color: var(--text);
            font-size: 1rem;
            line-height: 1.6;
        }
        .modal-zoom-movimiento {
            margin: 0;
            padding: .6rem .9rem;
            border-radius: .8rem;
            font: 700 .88rem 'Inter', sans-serif;
        }
        .modal-zoom-movimiento.movimiento {
            background: #e8f2ff;
            color: #0b4d8f;
            border: 1px solid #bfe0ff;
        }
        .modal-zoom-movimiento.estatica {
            background: #f4f6f8;
            color: #5b6b7c;
            border: 1px solid #d7dee6;
        }
        .modal-zoom-consejo {
            margin: 0;
            padding: .75rem 1rem;
            border-left: 4px solid var(--sol, #FFB400);
            border-radius: 0 .8rem .8rem 0;
            background: #fff7e6;
            color: #7a4b00;
            font-size: .92rem;
            line-height: 1.55;
        }

        /* Consejos de errores comunes en tarjetas */
        .alerta-lsc {
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            padding: .55rem .8rem;
            border-radius: .8rem;
            background: #fff7e6;
            border: 1px solid #f3e3b5;
            color: #7a4b00;
            font: 600 .82rem 'Inter', sans-serif;
            line-height: 1.5;
            margin-top: .5rem;
        }
        .alerta-lsc span:first-child {
            font-size: 1rem;
            line-height: 1;
        }

        /* Tarjeta de autoevaluación */
        .card-evaluacion {
            margin-top: 1.5rem;
            border-radius: 16px;
            padding: 24px;
            background: linear-gradient(135deg, #0D47A1, #123b63);
            box-shadow: 0 8px 24px rgba(13, 71, 161, .25);
            overflow: hidden;
        }
        .quiz-cabecera {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 0;
            background: transparent;
            color: #fff;
        }
        .quiz-texto {
            display: flex;
            flex-direction: column;
            gap: .5rem;
            min-width: 0;
        }
        .quiz-cabecera h2 {
            margin: 0;
            font: 800 1.35rem 'Baloo 2', sans-serif;
            color: #FFFFFF;
        }
        .quiz-cabecera p {
            margin: 0;
            font-size: .95rem;
            color: rgba(255, 255, 255, 0.92);
            line-height: 1.5;
        }
        .quiz-contenido {
            padding: 1.25rem;
            display: none;
            background: transparent;
        }
        .quiz-contenido.activo {
            display: block;
        }
        .quiz-pregunta {
            margin-bottom: 1rem;
        }
        .quiz-pregunta h4 {
            margin: 0 0 .75rem;
            font: 700 1.05rem 'Inter', sans-serif;
            color: var(--ink);
        }
        .quiz-opciones {
            display: grid;
            gap: .5rem;
        }
        .quiz-opcion {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .7rem .9rem;
            border: 1px solid var(--line);
            border-radius: .8rem;
            background: #fff;
            cursor: pointer;
            font-size: .95rem;
            color: var(--text);
        }
        .quiz-opcion:hover {
            border-color: var(--azul);
            background: #f4f8ff;
        }
        .quiz-opcion input {
            width: 18px;
            height: 18px;
            accent-color: var(--azul);
        }
        .quiz-opcion.correcta {
            border-color: #1f9d55;
            background: #e1f7e9;
            color: #146c3a;
        }
        .quiz-opcion.incorrecta {
            border-color: #9b1c1c;
            background: #fde8e8;
            color: #9b1c1c;
        }
        .quiz-feedback {
            margin-top: .75rem;
            padding: .7rem .9rem;
            border-radius: .8rem;
            font: 700 .9rem 'Inter', sans-serif;
            display: none;
        }
        .quiz-feedback.correcto {
            background: #e1f7e9;
            color: #146c3a;
            border: 1px solid #b9e6c9;
            display: block;
        }
        .quiz-feedback.incorrecto {
            background: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f5c2c2;
            display: block;
        }
        .quiz-puntaje {
            margin-top: 1rem;
            padding: .8rem 1rem;
            border-radius: .8rem;
            background: #fbfcff;
            border: 1px solid var(--line);
            font: 800 1.1rem 'Baloo 2', sans-serif;
            color: var(--ink);
            display: none;
            text-align: center;
        }
        .quiz-puntaje.mostrar {
            display: block;
        }
        .quiz-btn-reiniciar {
            margin-top: 1rem;
            padding: .6rem 1.2rem;
            border: 1px solid var(--line);
            border-radius: .6rem;
            background: #fff;
            color: var(--ink);
            font: 700 .9rem 'Inter', sans-serif;
            cursor: pointer;
            display: none;
        }
        .quiz-btn-reiniciar.mostrar {
            display: inline-block;
        }
        .quiz-btn-reiniciar:hover {
            background: #f4f8ff;
        }
    </style>
</head>
<body>
    <a href="#contenido-leccion" class="skip-link">Saltar al contenido</a>

    <!-- Topbar Oficial SEVA -->
    <header class="topbar">
        <a href="dashboard.php" class="marca" aria-label="Volver al inicio de SEVA">
            <span class="punto" aria-hidden="true"></span>SEVA
        </a>

        <div class="topbar-derecha">
            <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre) ?></strong></span>

            <!-- Menú Desplegable Accesible -->
            <div class="menu-opciones">
                <button type="button" class="btn-hamburguesa" id="btn-opciones" aria-label="Abrir menú de usuario" aria-haspopup="true" aria-expanded="false" aria-controls="panel-opciones">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>

                <div class="panel-opciones" id="panel-opciones" role="menu">
                    <div class="encabezado-opciones">
                        <span class="nombre-usuario"><?= htmlspecialchars($nombre) ?></span>
                        <span class="correo-usuario"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                    </div>

                    <a href="perfil.php" class="opcion" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                        Mi perfil
                    </a>
                    <a href="configuracion.php" class="opcion" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Configuración de cuenta
                    </a>

                    <hr class="separador-opciones">

                    <a href="logout.php" class="opcion salir" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        Cerrar sesión
                    </a>
                </div>
            </div>

            <a href="logout.php" class="btn-salir-directo" title="Cerrar sesión">Salir</a>
        </div>
    </header>

    <main class="contenedor" id="contenido-leccion">
        <!-- Navegación de migas de pan -->
        <nav class="migas" aria-label="Ruta de navegación">
            <a href="dashboard.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Tu Camino
            </a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= htmlspecialchars($leccion['titulo']) ?></span>
        </nav>

        <!-- Encabezado de la lección -->
        <header class="encabezado-leccion">
            <div class="meta-leccion">
                <span class="badge-contador" style="background:var(--paper); border:1px solid var(--line);">⏱ <?= htmlspecialchars($leccion['duracion']) ?> de práctica</span>
                <?php if ($leccion_completada): ?>
                    <span class="insignia-completada" style="position:static;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        Completada
                    </span>
                <?php endif; ?>
            </div>
            <h1><?= htmlspecialchars($leccion['titulo']) ?></h1>
            <p class="descripcion"><?= htmlspecialchars($leccion['descripcion']) ?></p>
        </header>

        <!-- 1. Contenedor de Video 16:9 Adaptado W3C (.video-lse) -->
        <section class="seccion-multimedia" aria-labelledby="titulo-video">
            <h2 id="titulo-video" style="font-size:1.35rem; margin-bottom:0.75rem;">Demostración en Lengua de Señas Colombiana (LSC)</h2>

            <div class="video-lse" style="background:linear-gradient(rgba(4,20,36,.28),rgba(4,20,36,.55)), url('<?= htmlspecialchars($leccion['ruta_imagen']) ?>') center/cover no-repeat;">
                <span class="etiqueta-lse" aria-hidden="true">LSC</span>

                <?php if (!empty($leccion['ruta_video']) && is_file(__DIR__ . '/' . $leccion['ruta_video'])): ?>
                    <video controls preload="metadata" id="video-leccion" poster="<?= htmlspecialchars($leccion['ruta_imagen']) ?>">
                        <source src="<?= htmlspecialchars($leccion['ruta_video']) ?>" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                <?php else: ?>
                    <div class="placeholder-video" style="background:rgba(4,20,36,.72); border-radius:.9rem; padding:1.4rem 1.8rem; backdrop-filter:blur(3px);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="2" width="20" height="20" rx="4"></rect>
                            <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon>
                        </svg>
                        <p><strong>Demostración visual en preparación</strong></p>
                        <p style="font-size:0.85rem; color:#A4B4CB; max-width:400px; margin:0.3rem auto 0;">
                            Practica con el modo guiado y la transcripción mientras se sincroniza el video de la lección.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="subtitulos" aria-live="polite">
                    Demostración activa: <?= htmlspecialchars($leccion['titulo']) ?>
                </div>
            </div>

            <!-- Controles y Transcripción Textual Accesible -->
            <div class="barra-controles-video">
                <button type="button" class="btn-transcripcion" id="btn-transcripcion" aria-expanded="false" aria-controls="panel-transcripcion">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                    <span>Ver transcripción textual</span>
                </button>
                <span style="font-size:0.85rem; color:var(--muted);">
                    Accesibilidad: consulta las pautas de movimiento de cada seña.
                </span>
            </div>
            <div class="controles-velocidad" aria-label="Velocidad de reproducción">
                <button type="button" class="btn-velocidad activa" data-rate="0.5" aria-pressed="true">0.5x Lento</button>
                <button type="button" class="btn-velocidad" data-rate="0.75" aria-pressed="false">0.75x Medio</button>
                <button type="button" class="btn-velocidad" data-rate="1" aria-pressed="false">1.0x Normal</button>
            </div>

            <div class="panel-transcripcion" id="panel-transcripcion">
                <h3 style="font-size:1.1rem; margin-top:0; color:var(--ink);">Transcripción y descripción gestual</h3>
                <ol style="margin:0; padding-left:1.3rem; line-height:1.7; color:var(--text);">
                    <li><strong>Postura y espacio gestual:</strong> Mantén una postura relajada, con las manos frente al pecho y la palma de la mano dominante visible para quien te lee.</li>
                    <?php if ($es_alfabeto): ?>
                        <li><strong>Mano dominante:</strong> Forma cada letra con la mano activa; la mano contraria sirve de apoyo y permanece quieta.</li>
                        <li><strong>Una letra a la vez:</strong> Deletrea sin rebotes ni cambios bruscos de muñeca; la transición entre letras debe ser fluida.</li>
                        <li><strong>Letras con movimiento:</strong> G, H, J, Ñ, S y Z requieren un trazo o giro adicional; sigue la instrucción de cada tarjeta.</li>
                    <?php else: ?>
                        <li><strong>Mano dominante:</strong> Realiza cada seña con la mano activa; la mano contraria sirve de apoyo y permanece quieta.</li>
                        <li><strong>Una seña a la vez:</strong> Ejecuta el gesto completo sin rebotes ni cambios bruscos; la transición entre señas debe ser fluida.</li>
                        <li><strong>Movimiento y expresión:</strong> Si la tarjeta indica «Con movimiento», sigue la instrucción y acompaña la seña con una expresión facial natural.</li>
                    <?php endif; ?>
                </ol>
            </div>
        </section>

        <!-- 2. Modo guiado: una seña por pantalla -->
        <?php if ($total_senas > 0): ?>
        <!-- Barra de herramientas superior independiente (vista dual + busca) -->
        <div class="toolbar-superior" role="region" aria-label="Controles de la lección">
            <div class="vista-selector" role="tablist" aria-label="Seleccionar vista de práctica">
                <button type="button" class="vista-btn activo" id="vista-guiada" role="tab" aria-selected="true" aria-controls="vista-guiada-panel">Modo Guiado (Paso a paso)</button>
                <button type="button" class="vista-btn" id="vista-rejilla" role="tab" aria-selected="false" aria-controls="vista-rejilla-panel">Vista Glosario (Rejilla completa)</button>
            </div>
        </div>
        <section class="seccion-practica seccion-wizard" aria-labelledby="titulo-practica" id="practica-guiada">
            <div class="buscador-senas">
                <span class="buscador-icono" aria-hidden="true">🔍</span>
                <input type="search" id="buscador-senas" placeholder="Buscar seña por palabra…" aria-label="Buscar señas de la lección" autocomplete="off">
            </div>

            <div class="wizard-cabecera">
                <div>
                    <p class="wizard-eyebrow">Modo guiado</p>
                    <h2 id="titulo-practica">Aprende seña por seña</h2>
                    <p class="wizard-intro">Avanza a tu ritmo: una seña, una imagen y una instrucción a la vez.</p>
                </div>
                <div class="wizard-progreso" id="wizard-progreso" role="progressbar"
                     aria-label="Progreso de la lección"
                     aria-valuemin="1"
                     aria-valuemax="<?= $total_senas ?>"
                     aria-valuenow="1"
                     aria-valuetext="Seña 1 de <?= $total_senas ?>: <?= htmlspecialchars($primera_sena) ?>">
                    <div class="wizard-progreso-texto">
                        <span id="wizard-contador">Seña 1 de <?= $total_senas ?></span>
                        <span id="wizard-porcentaje"><?= (int) round($porcentaje_inicial) ?>%</span>
                    </div>
                    <div class="wizard-progreso-barra" aria-hidden="true">
                        <span id="wizard-barra" style="width:<?= $porcentaje_inicial ?>%"></span>
                    </div>
                </div>
            </div>

            <div class="wizard" id="wizard">
                <?php foreach ($leccion['vocabulario'] as $indice => $item):
                    $palabra = htmlspecialchars($item['palabra']);
                    $consejo = $consejosLSC[$item['palabra']] ?? 'Mantén una postura relajada y la mano dominante visible para quien te lee.';
                    $instruccionMov = !empty($item['instruccion_movimiento']) ? htmlspecialchars($item['instruccion_movimiento']) : (isset($item['instruccion']) ? htmlspecialchars($item['instruccion']) : '');
                ?>
                    <article class="wizard-paso<?= $indice === 0 ? ' activo' : '' ?>"
                             data-indice="<?= (int) $indice ?>"
                             aria-labelledby="wizard-palabra-<?= htmlspecialchars($item['id']) ?>">
                        <div class="wizard-media">
                            <button type="button" class="btn-abrir-modal" data-indice="<?= (int) $indice ?>" aria-label="Ver seña ampliada: <?= $palabra ?>">
                                <?php if (!empty($item['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($item['imagen']) ?>"
                                         alt="Seña de «<?= $palabra ?>» en Lengua de Señas Colombiana"
                                         loading="<?= $indice === 0 ? 'eager' : 'lazy' ?>" decoding="async">
                                <?php else: ?>
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                                        <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                                        <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                                        <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                                    </svg>
                                <?php endif; ?>
                                <span class="overlay-zoom" aria-hidden="true">🔍</span>
                            </button>
                        </div>

                        <div class="wizard-contenido">
                            <p class="wizard-etiqueta">Seña <?= (int) $indice + 1 ?> de <?= $total_senas ?></p>
                            <h3 id="wizard-palabra-<?= htmlspecialchars($item['id']) ?>"><?= $palabra ?></h3>
                            <p class="wizard-descripcion"><?= htmlspecialchars($item['descripcion']) ?></p>

                            <?php if (!empty($item['con_movimiento']) || !empty($item['instruccion'])): ?>
                                <div class="wizard-movimiento">
                                    <?php if (!empty($item['con_movimiento'])): ?>
                                        <span class="badge-lsc badge-movimiento" aria-hidden="true">🔄 Con movimiento: <?= $instruccionMov ?></span>
                                    <?php else: ?>
                                        <span class="badge-lsc badge-estatica" aria-hidden="true">🖐️ Estática</span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['instruccion']) && empty($item['con_movimiento'])): ?>
                                        <p class="instruccion-movimiento"><strong>Instrucción:</strong> <?= htmlspecialchars($item['instruccion']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="alerta-lsc" aria-hidden="true">
                                <span>⚠️</span>
                                <span><strong>Ojo con la LSC:</strong> <?= htmlspecialchars($consejo) ?></span>
                            </div>

                            <div class="wizard-acciones-sena">
                                <a href="camara/detector.php" class="btn btn-contorno wizard-btn-camara">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                    Practicar con cámara
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="wizard-pie" id="wizard-pie">
                <div class="wizard-pie-nav">
                    <button type="button" class="btn btn-contorno wizard-nav" id="wizard-anterior" disabled>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Anterior
                    </button>
                    <span class="wizard-paso-actual" id="wizard-paso-actual" aria-hidden="true">1 / <?= $total_senas ?></span>
                    <button type="button" class="btn btn-primario wizard-nav" id="wizard-siguiente">
                        Siguiente
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>

                    <form method="POST" action="leccion.php?id=<?= $id ?>" id="form-completar" class="wizard-form-final">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="marcar_completada" value="1">
                        <button type="submit" class="btn btn-secundario" id="btn-completar" <?= $leccion_completada ? 'disabled style="background:var(--line); color:var(--muted); cursor:default;"' : '' ?>>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span id="texto-completar"><?= $leccion_completada ? '¡Lección Completada!' : 'Completar lección' ?></span>
                        </button>
                    </form>
                </div>
                <div class="wizard-pie-secundario">
                    <a href="dashboard.php" class="btn btn-ghost">Volver a Tu Camino</a>
                </div>
            </div>

            <p id="wizard-anuncio" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></p>
        </section>

        <!-- Vista rejilla: glosario completo de la lección -->
        <section class="vista-rejilla" id="vista-rejilla-panel" aria-labelledby="titulo-rejilla" tabindex="0">
            <h2 id="titulo-rejilla" class="visually-hidden">Glosario de señas · <?= htmlspecialchars($leccion['titulo']) ?></h2>
            <div class="grid-senas" id="grid-senas">
                <?php foreach ($leccion['vocabulario'] as $indice => $item):
                    $palabra = htmlspecialchars($item['palabra']);
                    $consejo = $consejosLSC[$item['palabra']] ?? 'Mantén una postura relajada y la mano dominante visible para quien te lee.';
                    $instruccionMov = !empty($item['instruccion_movimiento']) ? htmlspecialchars($item['instruccion_movimiento']) : (isset($item['instruccion']) ? htmlspecialchars($item['instruccion']) : '');
                    $conMovimiento = !empty($item['con_movimiento']);
                ?>
                <article class="tarjeta-sena" data-palabra="<?= strtolower($item['palabra']) ?>" data-indice="<?= (int) $indice ?>">
                    <div class="tarjeta-sena-media">
                        <button type="button" class="btn-abrir-modal" data-indice="<?= (int) $indice ?>" aria-label="Ver seña ampliada: <?= $palabra ?>">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?= htmlspecialchars($item['imagen']) ?>"
                                     alt="Seña de «<?= $palabra ?>» en Lengua de Señas Colombiana"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                                    <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                                    <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                                    <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                                </svg>
                            <?php endif; ?>
                            <span class="overlay-zoom" aria-hidden="true">🔍</span>
                        </button>
                    </div>
                    <div class="tarjeta-sena-contenido">
                        <h3><?= $palabra ?></h3>
                        <p><?= htmlspecialchars($item['descripcion']) ?></p>
                        <?php if ($conMovimiento): ?>
                            <span class="badge-lsc badge-movimiento" aria-hidden="true">🔄 Con movimiento: <?= $instruccionMov ?></span>
                        <?php else: ?>
                            <span class="badge-lsc badge-estatica" aria-hidden="true">🖐️ Estática</span>
                        <?php endif; ?>
                    </div>
                    <div class="alerta-lsc" aria-hidden="true">
                        <span>⚠️</span>
                        <span><strong>Ojo con la LSC:</strong> <?= htmlspecialchars($consejo) ?></span>
                    </div>
                    <a href="camara/detector.php" class="btn btn-contorno" style="width:100%; text-align:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        Practicar con cámara
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="seccion-practica seccion-wizard" aria-labelledby="titulo-practica-vacia">
            <div class="wizard-vacio">
                <h2 id="titulo-practica-vacia">Vocabulario en preparación</h2>
                <p>Todavía no hay señas cargadas para esta lección. Puedes repasar la transcripción y marcar la lección como completada.</p>

                <form method="POST" action="leccion.php?id=<?= $id ?>" id="form-completar">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="marcar_completada" value="1">
                    <button type="submit" class="btn btn-secundario" id="btn-completar" <?= $leccion_completada ? 'disabled style="background:var(--line); color:var(--muted); cursor:default;"' : '' ?>>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="texto-completar"><?= $leccion_completada ? '¡Lección Completada!' : 'Completar lección' ?></span>
                    </button>
                </form>

                <div class="wizard-pie-secundario">
                    <a href="dashboard.php" class="btn btn-contorno">Volver a Tu Camino</a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <p role="status" class="visually-hidden" id="anuncio-estado" aria-live="polite"></p>

        <!-- Sección de consejos pedagógicos LSC -->
        <section class="seccion-pedagogica" aria-labelledby="titulo-pedagogico">
            <div class="detalle-pedagogico" id="btn-pedagogico" aria-expanded="false" aria-controls="contenido-pedagogico" role="button" tabindex="0">
                <span>🎓 Consejos pedagógicos LSC</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="contenido-pedagogico" id="contenido-pedagogico" role="region" aria-label="Pautas pedagógicas de LSC">
                <h3 id="titulo-pedagogico">Pautas para aprender Lengua de Señas Colombiana</h3>
                <ul>
                    <li><strong>Expresión facial:</strong> Acompaña cada seña con una expresión acorde al significado; la cara es parte de la gramática LSC.</li>
                    <li><strong>Espacio neutro:</strong> Mantén las manos en el espacio frente al pecho, entre los hombros y la cintura, sin exceder los límites naturales del cuerpo.</li>
                    <li><strong>Postura del torso:</strong> Mantén la espalda recta y relajada, hombros sueltos, orientando el torso hacia quien te observa.</li>
                    <li><strong>Mano dominante:</strong> Usa siempre la misma mano para ejecutar la seña principal; la otra sirve de apoyo y permanece quieta.</li>
                    <li><strong>Fluidez:</strong> Ejecuta el gesto completo sin rebotes; las transiciones entre señas deben ser suaves y continuas.</li>
                </ul>
            </div>
        </section>
    </main>

    <!-- Modal de zoom / vista ampliada de seña -->
    <div class="modal-zoom-fondo" id="modal-zoom" role="dialog" aria-modal="true" aria-labelledby="modal-zoom-titulo" aria-hidden="true">
        <div class="modal-zoom">
            <div class="modal-zoom-cabecera">
                <h2 id="modal-zoom-titulo">Seña</h2>
                <button type="button" class="modal-zoom-cerrar" id="btn-cerrar-zoom" aria-label="Cerrar vista ampliada">✕</button>
            </div>
            <div class="modal-zoom-contenido">
                <div class="modal-zoom-imagen-wrap" id="modal-zoom-imagen-wrap">
                    <img id="modal-zoom-img" src="" alt="Seña ampliada">
                </div>
                <h3 id="modal-zoom-titulo-seña" style="margin:0 0 .5rem 0; font:800 1.4rem 'Baloo 2', sans-serif; color:var(--azul);"></h3>
                <p id="modal-zoom-descripcion" class="modal-zoom-descripcion"></p>
                <p id="modal-zoom-movimiento" class="modal-zoom-movimiento"></p>
                <p id="modal-zoom-consejo" class="modal-zoom-consejo"></p>
            </div>
        </div>
    </div>

    <!-- Mini-quiz / Autoevaluación rápida -->
    <section class="card-evaluacion seccion-quiz" id="seccion-quiz" aria-labelledby="quiz-titulo">
        <div class="quiz-cabecera">
            <div class="quiz-texto">
                <h2 id="quiz-titulo">🎯 Demostrar lo aprendido</h2>
                <p>Autoevaluación rápida de 3 preguntas sobre las señas de esta lección.</p>
            </div>
            <button type="button" class="btn btn-primario btn-iniciar-quiz" id="quiz-btn-iniciar">Iniciar autoevaluación</button>
        </div>
        <div class="quiz-contenido" id="quiz-contenido">
            <div id="quiz-preguntas"></div>
            <div id="quiz-feedback" class="quiz-feedback"></div>
            <div id="quiz-puntaje" class="quiz-puntaje"></div>
            <button type="button" class="quiz-btn-reiniciar" id="quiz-btn-reiniciar">Volver a intentar</button>
        </div>
    </section>

    <!-- Modal de lección completada (solo se abre tras guardar el avance) -->
    <div class="modal-fondo" id="modal-fin" role="dialog" aria-modal="true" aria-labelledby="modal-fin-titulo" aria-describedby="modal-fin-texto" aria-hidden="true">
        <div class="modal">
            <div class="gran-emoji" aria-hidden="true">🎉</div>
            <h2 id="modal-fin-titulo">¡Lección completada!</h2>
            <p id="modal-fin-texto">Tu avance quedó guardado. La siguiente lección ya está disponible en Tu Camino.</p>
            <span class="xp-ganado">+20 XP de práctica</span>
            <div class="acciones">
                <a class="btn btn-primario" href="dashboard.php">Ver mi camino</a>
                <a class="btn btn-contorno" href="practica.php">Seguir practicando</a>
                <button type="button" class="btn btn-ghost" id="modal-fin-cerrar">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="js/seva-ui.js"></script>
    <script>
        if (window.SEVA) {
            SEVA.init(<?= (int) $usuario_id ?>);
        }

        // Transcripción colapsable
        const btnTranscripcion = document.getElementById('btn-transcripcion');
        const panelTranscripcion = document.getElementById('panel-transcripcion');
        if (btnTranscripcion && panelTranscripcion) {
            btnTranscripcion.addEventListener('click', function () {
                const abierto = panelTranscripcion.classList.toggle('abierto');
                btnTranscripcion.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                const span = btnTranscripcion.querySelector('span');
                if (span) {
                    span.textContent = abierto ? 'Ocultar transcripción textual' : 'Ver transcripción textual';
                }
            });
        }

        // Modo guiado: una seña por pantalla
        const anuncioEstado = document.getElementById('anuncio-estado');
        const wizard = document.getElementById('wizard');
        const pasosWizard = Array.from(document.querySelectorAll('.wizard-paso'));
        const btnWizardAnterior = document.getElementById('wizard-anterior');
        const btnWizardSiguiente = document.getElementById('wizard-siguiente');
        const progresoWizard = document.getElementById('wizard-progreso');
        const contadorWizard = document.getElementById('wizard-contador');
        const porcentajeWizard = document.getElementById('wizard-porcentaje');
        const barraWizard = document.getElementById('wizard-barra');
        const pasoActualWizard = document.getElementById('wizard-paso-actual');
        const pieWizard = document.getElementById('wizard-pie');
        const anuncioWizard = document.getElementById('wizard-anuncio');
        let indiceWizard = 0;
        let temporizadorAnuncioWizard = 0;

        function actualizarWizard(nuevoIndice, anunciarCambio) {
            if (!pasosWizard.length) return;

            indiceWizard = Math.max(0, Math.min(nuevoIndice, pasosWizard.length - 1));
            const numero = indiceWizard + 1;
            const total = pasosWizard.length;
            const pasoActivo = pasosWizard[indiceWizard];
            const titulo = pasoActivo.querySelector('h3');
            const palabra = titulo ? titulo.textContent.trim() : '';

            pasosWizard.forEach(function (paso, indice) {
                const activo = indice === indiceWizard;
                paso.classList.toggle('activo', activo);
                paso.setAttribute('aria-hidden', activo ? 'false' : 'true');
            });

            if (contadorWizard) contadorWizard.textContent = 'Seña ' + numero + ' de ' + total;
            if (porcentajeWizard) porcentajeWizard.textContent = Math.round((numero / total) * 100) + '%';
            if (barraWizard) barraWizard.style.width = ((numero / total) * 100) + '%';
            if (pasoActualWizard) pasoActualWizard.textContent = numero + ' / ' + total;

            if (progresoWizard) {
                progresoWizard.setAttribute('aria-valuenow', String(numero));
                progresoWizard.setAttribute('aria-valuetext', 'Seña ' + numero + ' de ' + total + ': ' + palabra);
            }

            if (btnWizardAnterior) {
                btnWizardAnterior.disabled = indiceWizard === 0;
            }

            if (pieWizard) {
                pieWizard.classList.toggle('es-ultimo', indiceWizard === total - 1);
            }

            if (anunciarCambio && anuncioWizard) {
                window.clearTimeout(temporizadorAnuncioWizard);
                anuncioWizard.textContent = '';
                temporizadorAnuncioWizard = window.setTimeout(function () {
                    anuncioWizard.textContent = 'Seña ' + numero + ' de ' + total + ': ' + palabra;
                }, 40);
            }
        }

        if (pasosWizard.length > 0) {
            if (wizard) wizard.classList.add('wizard-activo');
            if (pieWizard) pieWizard.classList.add('wizard-js');

            actualizarWizard(0, false);

            if (btnWizardAnterior) {
                btnWizardAnterior.addEventListener('click', function () {
                    actualizarWizard(indiceWizard - 1, true);
                });
            }

            if (btnWizardSiguiente) {
                btnWizardSiguiente.addEventListener('click', function () {
                    if (indiceWizard >= pasosWizard.length - 1) return;
                    actualizarWizard(indiceWizard + 1, true);

                    if (indiceWizard === pasosWizard.length - 1) {
                        const btnCompletar = document.getElementById('btn-completar');
                        if (btnCompletar && !btnCompletar.disabled) {
                            btnCompletar.focus();
                        } else if (btnWizardAnterior) {
                            btnWizardAnterior.focus();
                        }
                    }
                });
            }
        }

        // Modal de finalización: foco, Escape y trampa de foco.
        const modalFin = document.getElementById('modal-fin');
        let ultimoFocoModal = null;

        function abrirModalFin() {
            if (!modalFin) return;
            ultimoFocoModal = document.activeElement;
            modalFin.classList.add('abierto');
            modalFin.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-abierto');
            const primero = modalFin.querySelector('a[href], button:not([disabled])');
            if (primero) primero.focus();
        }

        function cerrarModalFin() {
            if (!modalFin) return;
            modalFin.classList.remove('abierto');
            modalFin.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-abierto');
            if (ultimoFocoModal && ultimoFocoModal !== document.body
                && !ultimoFocoModal.disabled
                && !modalFin.contains(ultimoFocoModal)
                && document.contains(ultimoFocoModal)) {
                ultimoFocoModal.focus();
            } else {
                const enlaceMigas = document.querySelector('.migas a');
                if (enlaceMigas) enlaceMigas.focus();
            }
        }

        if (modalFin) {
            const btnCerrarModal = document.getElementById('modal-fin-cerrar');
            if (btnCerrarModal) {
                btnCerrarModal.addEventListener('click', cerrarModalFin);
            }
            modalFin.addEventListener('click', function (e) {
                if (e.target === modalFin) cerrarModalFin();
            });
            modalFin.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    cerrarModalFin();
                    return;
                }
                if (e.key === 'Tab') {
                    const focos = Array.from(modalFin.querySelectorAll('a[href], button:not([disabled])'))
                        .filter(function (el) { return el.offsetParent !== null; });
                    if (focos.length === 0) return;
                    const primero = focos[0];
                    const ultimo = focos[focos.length - 1];
                    if (e.shiftKey && document.activeElement === primero) {
                        e.preventDefault();
                        ultimo.focus();
                    } else if (!e.shiftKey && document.activeElement === ultimo) {
                        e.preventDefault();
                        primero.focus();
                    }
                }
            });
        }

        // Envío interactivo para completar la lección
        const formCompletar = document.getElementById('form-completar');
        const btnCompletar = document.getElementById('btn-completar');
        const textoCompletar = document.getElementById('texto-completar');

        if (formCompletar && btnCompletar) {
            let enviandoNativo = false;
            formCompletar.addEventListener('submit', function (e) {
                if (enviandoNativo || !window.fetch) {
                    return;
                }
                e.preventDefault();
                btnCompletar.disabled = true;
                if (textoCompletar) textoCompletar.textContent = 'Guardando avance...';

                const formData = new FormData(formCompletar);
                fetch('leccion.php?id=<?= $id ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data && data.exito) {
                        if (textoCompletar) textoCompletar.textContent = '¡Lección Completada!';
                        btnCompletar.style.background = 'var(--line)';
                        btnCompletar.style.color = 'var(--muted)';
                        btnCompletar.style.cursor = 'default';
                        if (anuncioEstado) {
                            anuncioEstado.textContent = 'La lección ha sido registrada como completada en tu perfil.';
                        }
                        if (window.SEVA) {
                            SEVA.confeti(160);
                            SEVA.sumarXP(20);
                        }
                        window.setTimeout(abrirModalFin, 450);
                    } else {
                        enviandoNativo = true;
                        formCompletar.submit();
                    }
                })
                .catch(() => {
                    enviandoNativo = true;
                    formCompletar.submit();
                });
            });
        }

        // Menú de opciones accesible
        const btnOpciones = document.getElementById('btn-opciones');
        const panelOpciones = document.getElementById('panel-opciones');
        if (btnOpciones && panelOpciones) {
            btnOpciones.addEventListener('click', function (e) {
                e.stopPropagation();
                const abierto = panelOpciones.classList.toggle('abierto');
                btnOpciones.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            });

            document.addEventListener('click', function () {
                panelOpciones.classList.remove('abierto');
                btnOpciones.setAttribute('aria-expanded', 'false');
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    panelOpciones.classList.remove('abierto');
                    btnOpciones.setAttribute('aria-expanded', 'false');
                    btnOpciones.focus();
                }
            });
        }

        // ===== Selector de vista dual =====
        const btnVistaGuiada = document.getElementById('vista-guiada');
        const btnVistaRejilla = document.getElementById('vista-rejilla');
        const panelGuiada = document.getElementById('practica-guiada');
        const panelRejilla = document.getElementById('vista-rejilla-panel');

        function setVista(guiada) {
            if (!panelGuiada || !panelRejilla || !btnVistaGuiada || !btnVistaRejilla) return;
            if (guiada) {
                panelGuiada.style.display = '';
                panelRejilla.style.display = 'none';
                panelRejilla.classList.remove('activa');
                btnVistaGuiada.classList.add('activo');
                btnVistaRejilla.classList.remove('activo');
                btnVistaGuiada.setAttribute('aria-selected', 'true');
                btnVistaRejilla.setAttribute('aria-selected', 'false');
            } else {
                panelGuiada.style.display = 'none';
                panelRejilla.style.display = '';
                panelRejilla.classList.add('activa');
                btnVistaGuiada.classList.remove('activo');
                btnVistaRejilla.classList.add('activo');
                btnVistaGuiada.setAttribute('aria-selected', 'false');
                btnVistaRejilla.setAttribute('aria-selected', 'true');
            }
        }

        if (btnVistaGuiada) btnVistaGuiada.addEventListener('click', () => setVista(true));
        if (btnVistaRejilla) btnVistaRejilla.addEventListener('click', () => setVista(false));

        // ===== Buscador instantáneo =====
        const inputBuscador = document.getElementById('buscador-senas');

        function filtrarSenas() {
            const texto = (inputBuscador ? inputBuscador.value : '').toLowerCase().trim();
            // Filtrar tarjetas del modo guiado
            document.querySelectorAll('#practica-guiada .wizard-paso').forEach(function (paso) {
                const palabra = (paso.getAttribute('data-palabra') || paso.querySelector('h3')?.textContent || '').toLowerCase();
                const coincide = texto === '' || palabra.includes(texto);
                paso.style.display = coincide ? '' : 'none';
                paso.setAttribute('aria-hidden', coincide ? 'false' : 'true');
            });
            // Filtrar tarjetas del modo rejilla
            document.querySelectorAll('#vista-rejilla-panel .tarjeta-sena').forEach(function (tarjeta) {
                const palabra = (tarjeta.getAttribute('data-palabra') || '').toLowerCase();
                const coincide = texto === '' || palabra.includes(texto);
                tarjeta.style.display = coincide ? '' : 'none';
                tarjeta.setAttribute('aria-hidden', coincide ? 'false' : 'true');
            });
        }

        if (inputBuscador) {
            inputBuscador.addEventListener('input', filtrarSenas);
        }

        // ===== Control de velocidad de video =====
        const video = document.getElementById('video-leccion');
        const btnsVelocidad = document.querySelectorAll('.btn-velocidad');

        function setVelocidad(rate) {
            if (!video) return;
            video.playbackRate = rate;
            btnsVelocidad.forEach(function (b) {
                const activa = b.getAttribute('data-rate') === String(rate);
                b.classList.toggle('activa', activa);
                b.setAttribute('aria-pressed', activa ? 'true' : 'false');
            });
        }

        btnsVelocidad.forEach(function (b) {
            b.addEventListener('click', function () {
                setVelocidad(parseFloat(b.getAttribute('data-rate')));
            });
        });

        // ===== Sección pedagógica colapsable =====
        const btnPedagogico = document.getElementById('btn-pedagogico');
        const contenidoPedagogico = document.getElementById('contenido-pedagogico');

        if (btnPedagogico && contenidoPedagogico) {
            btnPedagogico.addEventListener('click', function () {
                const abierto = !btnPedagogico.classList.contains('abierto');
                btnPedagogico.classList.toggle('abierto', abierto);
                btnPedagogico.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                contenidoPedagogico.classList.toggle('abierto', abierto);
            });
            btnPedagogico.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btnPedagogico.click();
                }
            });
        }

        // ===== Datos de señas para quiz y modal (desde PHP) =====
        const SENAS_DATA = <?= json_encode(array_map(function($i) {
            return [
                'palabra' => $i['palabra'],
                'descripcion' => $i['descripcion'],
                'imagen' => $i['imagen'] ?? '',
                'con_movimiento' => !empty($i['con_movimiento']),
                'instruccion_movimiento' => $i['instruccion_movimiento'] ?? (isset($i['instruccion']) ? $i['instruccion'] : ''),
                'consejo' => (function() use ($i) {
                    global $consejosLSC;
                    return $consejosLSC[$i['palabra']] ?? 'Mantén una postura relajada y la mano dominante visible para quien te lee.';
                })()
            ];
        }, $leccion['vocabulario']) ?? []); ?>;

        // ===== Modal de zoom / vista ampliada =====
        const modalZoom = document.getElementById('modal-zoom');
        const btnCerrarZoom = document.getElementById('btn-cerrar-zoom');
        const modalImg = document.getElementById('modal-zoom-img');
        const modalTitulo = document.getElementById('modal-zoom-titulo-seña');
        const modalDesc = document.getElementById('modal-zoom-descripcion');
        const modalMov = document.getElementById('modal-zoom-movimiento');
        const modalConsejo = document.getElementById('modal-zoom-consejo');
        const btnsAbrirModal = document.querySelectorAll('.btn-abrir-modal');

        function abrirModalZoom(indice) {
            const seña = SENAS_DATA[indice];
            if (!seña || !modalZoom) return;
            modalImg.src = seña.imagen || '';
            modalImg.alt = 'Seña ampliada: ' + seña.palabra;
            modalTitulo.textContent = seña.palabra;
            modalDesc.textContent = seña.descripcion || '';
            if (seña.con_movimiento) {
                modalMov.textContent = '🔄 Con movimiento: ' + seña.instruccion_movimiento;
                modalMov.className = 'modal-zoom-movimiento movimiento';
                modalMov.style.display = 'block';
            } else {
                modalMov.textContent = '🖐️ Estática';
                modalMov.className = 'modal-zoom-movimiento estatica';
                modalMov.style.display = 'block';
            }
            modalConsejo.textContent = '⚠️ Ojo con la LSC: ' + seña.consejo;
            modalConsejo.style.display = 'block';
            modalZoom.classList.add('abierto');
            modalZoom.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalZoom() {
            if (!modalZoom) return;
            modalZoom.classList.remove('abierto');
            modalZoom.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        btnsAbrirModal.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const idx = parseInt(btn.getAttribute('data-indice'));
                abrirModalZoom(idx);
            });
        });

        if (btnCerrarZoom) btnCerrarZoom.addEventListener('click', cerrarModalZoom);
        if (modalZoom) {
            modalZoom.addEventListener('click', function (e) {
                if (e.target === modalZoom) cerrarModalZoom();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modalZoom && modalZoom.classList.contains('abierto')) {
                cerrarModalZoom();
            }
        });

        // ===== Mini-quiz / Autoevaluación rápida =====
        const btnIniciarQuiz = document.getElementById('quiz-btn-iniciar');
        const btnReiniciarQuiz = document.getElementById('quiz-btn-reiniciar');
        const quizContenido = document.getElementById('quiz-contenido');
        const quizPreguntas = document.getElementById('quiz-preguntas');
        const quizFeedback = document.getElementById('quiz-feedback');
        const quizPuntaje = document.getElementById('quiz-puntaje');

        let quizEstado = {
            preguntas: [],
            indice: 0,
            aciertos: 0,
            respondido: false
        };

        function mezclar(array) {
            return array.slice().sort(() => Math.random() - 0.5);
        }

        function generarQuiz() {
            const senas = SENAS_DATA.filter(s => s.imagen);
            if (senas.length < 2) return [];
            const seleccion = mezclar(senas).slice(0, 3);
            return seleccion.map(function (seña) {
                const distractores = mezclar(SENAS_DATA.filter(s => s.palabra !== seña.palabra)).slice(0, 3);
                const opciones = mezclar([seña].concat(distractores));
                return {
                    seña: seña,
                    opciones: opciones,
                    correcta: seña.palabra
                };
            });
        }

        function renderQuiz() {
            quizPreguntas.innerHTML = '';
            quizEstado.preguntas.forEach(function (p, i) {
                const html = [
                    '<div class="quiz-pregunta">',
                    '<h4>Pregunta ' + (i + 1) + ': ¿Cuál es la seña de esta imagen?</h4>',
                    '<div class="quiz-imagen-pregunta" style="text-align:center; margin-bottom:.75rem;">',
                    '<img src="' + (p.seña.imagen || '') + '" alt="' + p.seña.palabra + '" style="max-width:180px; max-height:180px; object-fit:contain; border:1px solid var(--line); border-radius:.8rem;">',
                    '</div>',
                    '<div class="quiz-opciones" id="quiz-opciones-' + i + '">',
                    p.opciones.map(function (op, j) {
                        return '<label class="quiz-opcion">' +
                            '<input type="radio" name="quiz-' + i + '" value="' + op.palabra + '">' +
                            '<span>' + op.palabra + '</span>' +
                            '</label>';
                    }).join('') +
                    '</div>' +
                    '<div class="quiz-feedback" id="quiz-feedback-' + i + '"></div>' +
                    '</div>'
                ].join('');
                quizPreguntas.innerHTML += html;
            });
            document.querySelectorAll('.quiz-opcion').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    if (quizEstado.respondido) return;
                    const radio = opt.querySelector('input');
                    if (!radio) return;
                    const nombre = radio.name;
                    const indiceP = parseInt(nombre.replace('quiz-', ''));
                    const pregunta = quizEstado.preguntas[indiceP];
                    const seleccionado = radio.value;
                    const correcto = seleccionado === pregunta.correcta;
                    quizEstado.respondido = true;
                    if (correcto) quizEstado.aciertos++;
                    // Marcar opciones
                    const opciones = document.querySelectorAll('input[name="' + nombre + '"]');
                    opciones.forEach(function (inp) {
                        const lbl = inp.parentElement;
                        lbl.classList.remove('correcta', 'incorrecta');
                        lbl.style.cursor = 'default';
                        inp.disabled = true;
                        if (inp.value === pregunta.correcta) lbl.classList.add('correcta');
                        else if (inp.value === seleccionado && !correcto) lbl.classList.add('incorrecta');
                    });
                    // Feedback
                    const fb = document.getElementById('quiz-feedback-' + indiceP);
                    fb.textContent = correcto ? '¡Correcto! ' + pregunta.correcta : 'Incorrecto. La respuesta correcta es: ' + pregunta.correcta;
                    fb.className = 'quiz-feedback ' + (correcto ? 'correcto' : 'incorrecto');
                    // Avanzar o finalizar
                    quizEstado.indice++;
                    if (quizEstado.indice >= quizEstado.preguntas.length) {
                        setTimeout(finalizarQuiz, 800);
                    }
                });
            });
        }

        function finalizarQuiz() {
            quizFeedback.textContent = 'Has terminado. Acertaste ' + quizEstado.aciertos + ' de ' + quizEstado.preguntas.length + ' preguntas.';
            quizFeedback.className = 'quiz-feedback ' + (quizEstado.aciertos >= 2 ? 'correcto' : 'incorrecto');
            quizFeedback.style.display = 'block';
            quizPuntaje.textContent = 'Puntaje: ' + quizEstado.aciertos + ' / ' + quizEstado.preguntas.length;
            quizPuntaje.className = 'quiz-puntaje mostrar';
            btnReiniciarQuiz.className = 'quiz-btn-reiniciar mostrar';
            btnIniciarQuiz.style.display = 'none';
        }

        function reiniciarQuiz() {
            quizEstado = { preguntas: [], indice: 0, aciertos: 0, respondido: false };
            quizPreguntas.innerHTML = '';
            quizFeedback.style.display = 'none';
            quizPuntaje.className = 'quiz-puntaje';
            btnReiniciarQuiz.className = 'quiz-btn-reiniciar';
            btnIniciarQuiz.style.display = 'inline-block';
            quizContenido.classList.remove('activo');
        }

        if (btnIniciarQuiz) {
            btnIniciarQuiz.addEventListener('click', function () {
                quizEstado.preguntas = generarQuiz();
                quizEstado.indice = 0;
                quizEstado.aciertos = 0;
                quizEstado.respondido = false;
                renderQuiz();
                quizContenido.classList.add('activo');
                btnIniciarQuiz.style.display = 'none';
                btnReiniciarQuiz.className = 'quiz-btn-reiniciar';
                window.scrollTo({ top: document.getElementById('seccion-quiz').offsetTop - 100, behavior: 'smooth' });
            });
        }

        if (btnReiniciarQuiz) {
            btnReiniciarQuiz.addEventListener('click', reiniciarQuiz);
        }
    </script>
</body>
</html>
