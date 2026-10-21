<?php
require 'conexion.php';
require 'seguridad.php';

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
            if (!empty($fila['ruta_video'])) {
                $leccion['ruta_video'] = $fila['ruta_video'];
            }
            if (!empty($fila['ruta_imagen'])) {
                $leccion['ruta_imagen'] = $fila['ruta_imagen'];
            }
            if (!empty($fila['nivel'])) {
                $leccion['nivel'] = ucfirst(strtolower($fila['nivel']));
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($leccion['titulo']) ?> · SEVA</title>
    <!-- Fuentes Web Accesibles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Oficiales SEVA -->
    <link rel="stylesheet" href="css/estilos.css">
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
                <span class="insignia-nivel nivel-<?= htmlspecialchars($nivel_clase) ?>"><?= htmlspecialchars($leccion['nivel']) ?></span>
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
            <h2 id="titulo-video" style="font-size:1.35rem; margin-bottom:0.75rem;">Demostración en Lengua de Señas</h2>

            <div class="video-lse">
                <span class="etiqueta-lse" aria-hidden="true">LSE</span>

                <?php if (!empty($leccion['ruta_video']) && file_exists($leccion['ruta_video'])): ?>
                    <video controls preload="metadata" poster="<?= htmlspecialchars($leccion['ruta_imagen']) ?>">
                        <source src="<?= htmlspecialchars($leccion['ruta_video']) ?>" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                <?php else: ?>
                    <div class="placeholder-video">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="2" width="20" height="20" rx="4"></rect>
                            <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon>
                        </svg>
                        <p><strong>Demostración visual en preparación</strong></p>
                        <p style="font-size:0.85rem; color:#A4B4CB; max-width:400px; margin:0.3rem auto 0;">
                            Practica con las tarjetas interactivas y la transcripción guiada mientras se sincroniza el video de la lección.
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
                    <li><strong>Contacto visual y postura:</strong> Mantén una postura relajada con las manos en el espacio gestual frente al pecho.</li>
                    <li><strong>Señas de bienvenida:</strong> Palma extendida a la altura de la sien con movimiento de vaivén.</li>
                    <li><strong>Expresión facial:</strong> Las cejas y la mirada acompañan el significado interrogativo o afirmativo de cada gesto.</li>
                    <li><strong>Dactilología:</strong> Mantén la mano dominante quieta y deletrea cada letra de forma fluida.</li>
                </ol>
            </div>
        </section>

        <!-- 2. Tarjetas de Señas y Práctica Interactiva -->
        <section class="seccion-practica" aria-labelledby="titulo-practica">
            <div class="encabezado-practica">
                <div>
                    <h2 id="titulo-practica" style="font-size:1.35rem; margin:0 0 0.2rem;">Vocabulario y Práctica de Señas</h2>
                    <p style="color:var(--muted); margin:0; font-size:0.92rem;">Practica cada gesto y marca la casilla cuando domines su ejecución.</p>
                </div>
                <div class="contador-practica">
                    <span id="contador-practicadas">0</span> / <?= count($leccion['vocabulario']) ?> señas practicadas
                </div>
            </div>

            <div class="grid-practica">
                <?php foreach ($leccion['vocabulario'] as $item): ?>
                    <article class="tarjeta-seña" id="tarjeta-<?= htmlspecialchars($item['id']) ?>">
                        <div class="preview-seña">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                                <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                                <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                                <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                            </svg>
                        </div>
                        <h3><?= htmlspecialchars($item['palabra']) ?></h3>
                        <p><?= htmlspecialchars($item['descripcion']) ?></p>

                        <!-- Casilla de verificación accesible con zona táctil >= 44x44px -->
                        <label class="control-check-practica">
                            <input type="checkbox" class="check-practicado" data-palabra="<?= htmlspecialchars($item['id']) ?>" aria-label="Ya practiqué la seña <?= htmlspecialchars($item['palabra']) ?>">
                            <span>Ya practiqué esta seña</span>
                        </label>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 3. Acción Final: Marcar como Completada -->
        <section class="tarjeta-accion-final" aria-labelledby="titulo-completar">
            <h3 id="titulo-completar">¿Dominas las señas de esta lección?</h3>
            <p>Guarda tu avance para reflejarlo en tu progreso general y continuar avanzando en Tu Camino.</p>

            <form method="POST" action="leccion.php?id=<?= $id ?>" id="form-completar" style="display:inline-block;">
                <?= csrf_campo() ?>
                <input type="hidden" name="marcar_completada" value="1">
                <button type="submit" class="btn btn-secundario" id="btn-completar" <?= $leccion_completada ? 'disabled style="background:var(--line); color:var(--muted); cursor:default;"' : '' ?>>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="texto-completar"><?= $leccion_completada ? '¡Lección Completada!' : 'Marcar lección como completada' ?></span>
                </button>
            </form>

            <div style="margin-top:1.25rem;">
                <a href="dashboard.php" class="btn btn-contorno" style="min-height:44px;">
                    Volver a Tu Camino
                </a>
            </div>
        </section>

        <p role="status" class="visually-hidden" id="anuncio-estado" aria-live="polite"></p>
    </main>

    <script>
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

        // Progreso interactivo de señas practicadas
        const STORAGE_KEY = 'seva_leccion_<?= $id ?>_practicadas';
        const casillas = document.querySelectorAll('.check-practicado');
        const contadorPracticadas = document.getElementById('contador-practicadas');
        const anuncioEstado = document.getElementById('anuncio-estado');

        function actualizarContador() {
            let marcadas = 0;
            casillas.forEach(function (c) {
                const card = c.closest('.tarjeta-seña');
                if (c.checked) {
                    marcadas++;
                    if (card) card.classList.add('practicada');
                } else {
                    if (card) card.classList.remove('practicada');
                }
            });

            if (contadorPracticadas) {
                contadorPracticadas.textContent = marcadas;
            }

            if (anuncioEstado && marcadas === casillas.length && casillas.length > 0) {
                anuncioEstado.textContent = '¡Excelente! Has marcado todas las señas de esta lección como practicadas.';
            }
        }

        function guardarProgreso() {
            const marcadas = [];
            casillas.forEach(function (c) {
                if (c.checked) {
                    marcadas.push(c.dataset.palabra);
                }
            });
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(marcadas));
            } catch (e) {}
        }

        function cargarProgreso() {
            try {
                const guardadas = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                casillas.forEach(function (c) {
                    if (guardadas.includes(c.dataset.palabra)) {
                        c.checked = true;
                    }
                });
            } catch (e) {}
            actualizarContador();
        }

        casillas.forEach(function (c) {
            c.addEventListener('change', function () {
                actualizarContador();
                guardarProgreso();
            });
        });

        cargarProgreso();

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
