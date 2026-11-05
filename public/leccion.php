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
                    <video controls preload="metadata" poster="<?= htmlspecialchars($leccion['ruta_imagen']) ?>">
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
        <section class="seccion-practica seccion-wizard" aria-labelledby="titulo-practica" id="practica-guiada">
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
                <?php foreach ($leccion['vocabulario'] as $indice => $item): ?>
                    <article class="wizard-paso<?= $indice === 0 ? ' activo' : '' ?>"
                             data-indice="<?= (int) $indice ?>"
                             aria-labelledby="wizard-palabra-<?= htmlspecialchars($item['id']) ?>">
                        <div class="wizard-media">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?= htmlspecialchars($item['imagen']) ?>"
                                     alt="Seña de «<?= htmlspecialchars($item['palabra']) ?>» en Lengua de Señas Colombiana"
                                     loading="<?= $indice === 0 ? 'eager' : 'lazy' ?>" decoding="async">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                                    <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                                    <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                                    <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <div class="wizard-contenido">
                            <p class="wizard-etiqueta">Seña <?= (int) $indice + 1 ?> de <?= $total_senas ?></p>
                            <h3 id="wizard-palabra-<?= htmlspecialchars($item['id']) ?>"><?= htmlspecialchars($item['palabra']) ?></h3>
                            <p class="wizard-descripcion"><?= htmlspecialchars($item['descripcion']) ?></p>

                            <?php if (!empty($item['con_movimiento']) || !empty($item['instruccion'])): ?>
                                <div class="wizard-movimiento">
                                    <?php if (!empty($item['con_movimiento'])): ?>
                                        <span class="etq-movimiento"><span aria-hidden="true">↔</span> Con movimiento</span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['instruccion'])): ?>
                                        <p class="instruccion-movimiento"><strong>Movimiento:</strong> <?= htmlspecialchars($item['instruccion']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

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
    </main>

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
    </script>
</body>
</html>
