<?php
require 'conexion.php';
require 'seguridad.php';

seguridad_iniciar_sesion();

// Validación estricta de sesión de usuario
seguridad_requiere_login();

$usuario_id = $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$primer     = explode(' ', trim($nombre))[0] ?: 'Estudiante';

$error_bd = '';
$lecciones = [];
$total = 0;
$completadas = 0;
$porcentaje = 0;
$completados_ids = [];

try {
    // Consulta extendida con nivel y orden para la ruta gamificada
    $stmt_lec = $pdo->query('SELECT id, titulo, descripcion, ruta_imagen, ruta_video, nivel, orden FROM contenidos_educativos ORDER BY orden ASC, id ASC');
    $lecciones = $stmt_lec->fetchAll();
    $total = count($lecciones);

    $stmt_comp = $pdo->prepare('SELECT COUNT(*) AS c FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_comp->execute([$usuario_id]);
    $completadas = (int) $stmt_comp->fetch()['c'];

    $porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;

    $stmt_ids = $pdo->prepare('SELECT contenido_id FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_ids->execute([$usuario_id]);
    $completados_ids = array_column($stmt_ids->fetchAll(), 'contenido_id');
} catch (PDOException $e) {
    error_log('Dashboard - error de base de datos: ' . $e->getMessage());
    $error_bd = 'No se pudieron cargar las lecciones. Verifica la conexión a la base de datos de SEVA.';
}

// === Datos de presentación para la ruta de aprendizaje ===
// Solo reorganiza lo ya consultado: no altera el cálculo de progreso ni las
// consultas a "progreso_usuario" / "contenidos_educativos".
$niveles_ruta = [
    'basico' => [
        'nombre'  => 'Básico',
        'lema'    => 'Tus primeras señas',
        'icono'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>',
        'lecciones' => [],
    ],
    'intermedio' => [
        'nombre'  => 'Intermedio',
        'lema'    => 'Amplía tu vocabulario',
        'icono'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>',
        'lecciones' => [],
    ],
    'avanzado' => [
        'nombre'  => 'Avanzado',
        'lema'    => 'Domina la conversación',
        'icono'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="5"/><path d="M8.5 12.5L7 22l5-3 5 3-1.5-9.5"/></svg>',
        'lecciones' => [],
    ],
];

$completados_ids = array_map('intval', $completados_ids);

// Primera lección no completada = siguiente paso sugerido (bloqueo secuencial).
$siguiente_id = null;
foreach ($lecciones as $l) {
    if (!in_array((int) $l['id'], $completados_ids, true)) {
        $siguiente_id = (int) $l['id'];
        break;
    }
}

// Título de la siguiente lección y mensaje del héroe (solo presentación).
$siguiente_titulo = '';
if ($siguiente_id !== null) {
    foreach ($lecciones as $l) {
        if ((int) $l['id'] === $siguiente_id) {
            $siguiente_titulo = (string) $l['titulo'];
            break;
        }
    }
}

if ($error_bd !== '') {
    $hero_subtitulo = 'Revisa la conexión con la base de datos para continuar tu camino.';
} elseif ($total === 0) {
    $hero_subtitulo = 'Todavía no hay lecciones publicadas. Cuando se agreguen, aparecerán aquí.';
} elseif ($siguiente_id !== null) {
    $hero_subtitulo = 'Tu siguiente paso es «' . $siguiente_titulo . '». Avanza a tu ritmo y practica las veces que necesites.';
} else {
    $hero_subtitulo = '¡Completaste todas las lecciones! Repásalas cuando quieras y sigue practicando con los juegos.';
}

// Seña del día: se lee del JSON local y rota según el día del año.
$sena_dia = null;
$sena_archivo = '';
$ruta_guia = __DIR__ . '/data/guia.json';
if (is_file($ruta_guia)) {
    $guia_datos = json_decode((string) file_get_contents($ruta_guia), true);
    if (is_array($guia_datos)) {
        $guia_datos = array_values(array_filter($guia_datos, static function ($g): bool {
            return is_array($g) && !empty($g['palabra']);
        }));
        if (count($guia_datos) > 0) {
            $sena_dia = $guia_datos[(int) date('z') % count($guia_datos)];
            $ruta_rel = ltrim(str_replace('\\', '/', (string) ($sena_dia['archivo'] ?? '')), '/');
            if ($ruta_rel !== '' && strpos($ruta_rel, '..') === false && is_file(__DIR__ . '/' . $ruta_rel)) {
                $sena_archivo = $ruta_rel;
            }
        }
    }
}

foreach ($lecciones as $indice => $l) {
    $slug = strtolower((string) ($l['nivel'] ?? 'basico'));
    if (!isset($niveles_ruta[$slug])) {
        $slug = 'basico';
    }

    $id = (int) $l['id'];
    if (in_array($id, $completados_ids, true)) {
        $estado = 'completada';
    } elseif ($siguiente_id === $id) {
        $estado = 'actual';
    } else {
        $estado = 'bloqueada';
    }

    $niveles_ruta[$slug]['lecciones'][] = [
        'id'     => $id,
        'titulo' => (string) $l['titulo'],
        'estado' => $estado,
        'numero' => count($niveles_ruta[$slug]['lecciones']) + 1,
    ];
}

// Zigzag del camino: centro → izquierda → centro → derecha (se repite).
$patron_x = [50, 24, 50, 76];

foreach ($niveles_ruta as $slug => $niv) {
    $total_niv = count($niv['lecciones']);
    $trazos = [];

    for ($i = 0; $i < $total_niv - 1; $i++) {
        $x1 = $patron_x[$i % 4];
        $y1 = $i * 100 + 25;
        $x2 = $patron_x[($i + 1) % 4];
        $y2 = ($i + 1) * 100 + 25;
        $medio = ($y1 + $y2) / 2;

        $estado_destino = $niv['lecciones'][$i + 1]['estado'];
        $clase_conector = $estado_destino === 'completada'
            ? 'conector-completado'
            : ($estado_destino === 'actual' ? 'conector-actual' : 'conector-bloqueado');

        $trazos[] = '<path class="conector-linea ' . $clase_conector . '" d="M ' . $x1 . ' ' . $y1
            . ' C ' . $x1 . ' ' . $medio . ', ' . $x2 . ' ' . $medio . ', ' . $x2 . ' ' . $y2 . '"></path>';
    }

    $niveles_ruta[$slug]['svg'] = implode('', $trazos);
    $niveles_ruta[$slug]['completadas'] = count(array_filter(
        $niv['lecciones'],
        static fn (array $leccion): bool => $leccion['estado'] === 'completada'
    ));
    $niveles_ruta[$slug]['porcentaje'] = $total_niv > 0
        ? (int) round(($niveles_ruta[$slug]['completadas'] / $total_niv) * 100)
        : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Camino de Aprendizaje · SEVA</title>
    <script>document.documentElement.classList.add('js')</script>
    <!-- Fuentes Web Accesibles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Oficiales SEVA -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <a href="#contenido-principal" class="skip-link">Saltar al contenido principal</a>

    <!-- Encabezado Superior -->
    <header class="topbar">
        <a href="dashboard.php" class="marca" aria-label="Inicio SEVA - Sistema Educativo de Lengua de Señas">
            <span class="punto" aria-hidden="true"></span>SEVA
        </a>

        <div class="topbar-derecha">
            <span class="chip chip-racha" title="Días seguidos practicando (guardado en este dispositivo)">
                <span aria-hidden="true">🔥</span><b data-racha>0</b>
                <span class="visually-hidden">días de racha</span>
            </span>
            <span class="chip" title="Puntos de experiencia guardados en este dispositivo">
                <span aria-hidden="true">⭐</span><b data-xp>0</b> XP
            </span>

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

                    <a href="practica.php" class="opcion" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><circle cx="15.5" cy="15.5" r="1.5"/><path d="M8.5 15.5h.01M15.5 8.5h.01"/></svg>
                        Practicar con juegos
                    </a>
                    <a href="camara/detector.php" class="opcion" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Detector de manos
                    </a>

                    <hr class="separador-opciones">

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

    <main class="contenedor" id="contenido-principal">
        <?php if ($error_bd): ?>
            <div class="alerta alerta-error" role="alert">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <strong>Error de conexión</strong>
                    <p style="margin:0;"><?= htmlspecialchars($error_bd) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$error_bd): ?>
            <!-- 1. Héroe: saludo, siguiente paso y progreso -->
            <?php
                $anillo_circ = 2 * M_PI * 54;
                $anillo_off = $anillo_circ * (1 - ((int) $porcentaje / 100));
            ?>
            <section class="hero" aria-labelledby="titulo-progreso">
                <div>
                    <p class="hero-eyebrow">Tu progreso</p>
                    <h1 id="titulo-progreso">¡Hola, <?= htmlspecialchars($primer) ?>!</h1>
                    <p class="hero-texto"><?= htmlspecialchars($hero_subtitulo) ?></p>
                    <div class="hero-acciones">
                        <?php if ($siguiente_id !== null): ?>
                            <a href="leccion.php?id=<?= (int) $siguiente_id ?>" class="btn btn-primario">Continuar lección</a>
                        <?php elseif (count($lecciones) > 0): ?>
                            <a href="#titulo-camino" class="btn btn-primario">Repasar mis lecciones</a>
                        <?php endif; ?>
                        <a href="practica.php" class="btn btn-contorno">Practicar con juegos</a>
                    </div>
                </div>

                <div class="anillo" role="progressbar" aria-valuenow="<?= (int) $porcentaje ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso acumulado: <?= (int) $porcentaje ?> por ciento, <?= (int) $completadas ?> de <?= (int) $total ?> lecciones completadas">
                    <svg viewBox="0 0 120 120" aria-hidden="true" focusable="false">
                        <circle class="pista-anillo" cx="60" cy="60" r="54"></circle>
                        <circle class="valor-anillo" cx="60" cy="60" r="54" style="--circ: <?= round($anillo_circ, 2) ?>; --off: <?= round($anillo_off, 2) ?>;"></circle>
                    </svg>
                    <div class="anillo-centro" aria-hidden="true">
                        <strong><?= (int) $porcentaje ?>%</strong>
                        <span><?= (int) $completadas ?> de <?= (int) $total ?></span>
                    </div>
                </div>
            </section>

            <!-- 1b. Seña del día y accesos rápidos a práctica -->
            <div class="modulos">
                <section class="caja" aria-labelledby="titulo-sena-dia" data-reveal>
                    <h2 id="titulo-sena-dia"><span aria-hidden="true">👋</span> Seña del día</h2>
                    <?php if ($sena_dia !== null): ?>
                        <div class="sena-dia">
                            <?php if ($sena_archivo !== ''): ?>
                                <img src="<?= htmlspecialchars($sena_archivo) ?>" alt="Tarjeta de referencia visual para la palabra <?= htmlspecialchars((string) $sena_dia['palabra']) ?>" width="96" height="96" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars((string) $sena_dia['palabra']) ?></strong>
                                <p><?= htmlspecialchars((string) ($sena_dia['descripcion'] ?? '')) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="sub-progreso">La seña del día estará disponible cuando se cargue el vocabulario.</p>
                    <?php endif; ?>
                </section>

                <section class="caja" aria-labelledby="titulo-juegos" data-reveal>
                    <h2 id="titulo-juegos"><span aria-hidden="true">🎮</span> Practica jugando</h2>
                    <div class="juegos">
                        <a class="juego" href="practica.php?modo=quiz">
                            <span class="emoji" aria-hidden="true">❓</span>
                            <span class="juego-texto">Quiz<small>Elige la seña correcta</small></span>
                        </a>
                        <a class="juego" href="practica.php?modo=memoria">
                            <span class="emoji" aria-hidden="true">🧠</span>
                            <span class="juego-texto">Memoria<small>Empareja las cartas</small></span>
                        </a>
                        <a class="juego" href="practica.php?modo=alfabeto">
                            <span class="emoji" aria-hidden="true">🔤</span>
                            <span class="juego-texto">Alfabeto<small>Dactilología letra a letra</small></span>
                        </a>
                        <a class="juego" href="camara/detector.php">
                            <span class="emoji" aria-hidden="true">📷</span>
                            <span class="juego-texto">Cámara<small>Beta en revisión</small></span>
                        </a>
                    </div>
                </section>
            </div>

            <!-- 2. Ruta de Aprendizaje "Tu Camino" -->
            <section class="seccion-camino" aria-labelledby="titulo-camino">
                <!-- Barra de herramientas: Buscador y Pestañas de Nivel -->
                <div class="barra-herramientas">
                    <div class="contenedor-buscador">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="buscador" placeholder="Buscar lección por título..." aria-label="Buscar lecciones en tiempo real">
                    </div>

                    <!-- Filtros de nivel: grupo de botones, no pestañas (D17) -->
                    <div class="filtros-camino" role="group" aria-label="Filtrar lecciones por nivel">
                        <button type="button" class="btn-filtro activo" data-filtro="todos" aria-pressed="true">Todas</button>
                        <button type="button" class="btn-filtro" data-filtro="basico" aria-pressed="false">Básico</button>
                        <button type="button" class="btn-filtro" data-filtro="intermedio" aria-pressed="false">Intermedio</button>
                        <button type="button" class="btn-filtro" data-filtro="avanzado" aria-pressed="false">Avanzado</button>
                    </div>
                </div>

                <div class="encabezado-seccion-camino">
                    <h2 id="titulo-camino">
                        Tu Camino
                        <span class="badge-contador" id="contador-visibles" role="status" aria-live="polite"><?= $total ?> lecciones</span>
                    </h2>
                </div>

                <?php if (count($lecciones) > 0): ?>
                    <div class="ruta-aprendizaje" id="contenedor-lecciones">
                        <?php foreach ($niveles_ruta as $slug => $niv): ?>
                            <?php if (count($niv['lecciones']) === 0) { continue; } ?>

                            <section class="tramo-nivel tramo-<?= htmlspecialchars($slug) ?>" data-nivel="<?= htmlspecialchars($slug) ?>" aria-labelledby="titulo-nivel-<?= htmlspecialchars($slug) ?>">
                                <header class="banner-nivel">
                                    <span class="banner-nivel-icono"><?= $niv['icono'] ?></span>
                                    <div class="banner-nivel-texto">
                                        <h3 id="titulo-nivel-<?= htmlspecialchars($slug) ?>">Nivel <?= htmlspecialchars($niv['nombre']) ?></h3>
                                        <p><?= htmlspecialchars($niv['lema']) ?> · <?= (int) $niv['completadas'] ?> de <?= count($niv['lecciones']) ?> completadas</p>
                                    </div>
                                    <span class="banner-nivel-pct" aria-hidden="true"><?= (int) $niv['porcentaje'] ?>%</span>
                                </header>

                                <div class="lienzo-camino">
                                    <!-- Conectores curvos: decorativos, la secuencia se lee en el orden del DOM -->
                                    <svg class="conectores-camino" viewBox="0 0 100 <?= count($niv['lecciones']) * 100 ?>" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                        <?= $niv['svg'] ?>
                                    </svg>

                                    <ol class="nodos-camino" role="list">
                                    <?php foreach ($niv['lecciones'] as $i => $lec): ?>
                                        <?php
                                            $x = $patron_x[$i % 4];
                                            $estado_texto = match ($lec['estado']) {
                                                'completada' => 'Completada. Puedes repasarla cuando quieras.',
                                                'actual'     => 'Disponible ahora. Es tu siguiente lección.',
                                                default      => 'Bloqueada. Completa la lección anterior para desbloquearla.',
                                            };
                                            $aria_label = 'Lección ' . $lec['numero'] . ': ' . $lec['titulo'] . '. ' . $estado_texto;
                                            $icono_badge = match ($lec['estado']) {
                                                'completada' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="20 6 9 17 4 12"/></svg>',
                                                'actual'     => '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><polygon points="6 3 20 12 6 21 6 3"/></svg>',
                                                default      => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
                                            };
                                        ?>
                                        <li class="nodo-item"
                                            data-nivel="<?= htmlspecialchars($slug) ?>"
                                            data-titulo="<?= htmlspecialchars(mb_strtolower($lec['titulo'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-estado="<?= htmlspecialchars($lec['estado']) ?>"
                                            style="--x: <?= $x ?>%;">

                                            <?php if ($lec['estado'] === 'bloqueada'): ?>
                                                <span class="nodo" role="link" aria-disabled="true" aria-label="<?= htmlspecialchars($aria_label, ENT_QUOTES, 'UTF-8') ?>">
                                                    <span class="nodo-circulo" aria-hidden="true">
                                                        <?= $lec['numero'] ?>
                                                        <span class="nodo-badge"><?= $icono_badge ?></span>
                                                    </span>
                                                    <span class="nodo-etiqueta">
                                                        <span class="nodo-titulo"><?= htmlspecialchars($lec['titulo']) ?></span>
                                                    </span>
                                                </span>
                                            <?php else: ?>
                                                <a class="nodo" href="leccion.php?id=<?= (int) $lec['id'] ?>" aria-label="<?= htmlspecialchars($aria_label, ENT_QUOTES, 'UTF-8') ?>"<?= $lec['estado'] === 'actual' ? ' aria-current="step"' : '' ?>>
                                                    <span class="nodo-circulo" aria-hidden="true">
                                                        <?= $lec['numero'] ?>
                                                        <span class="nodo-badge"><?= $icono_badge ?></span>
                                                    </span>
                                                    <span class="nodo-etiqueta">
                                                        <?php if ($lec['estado'] === 'actual'): ?>
                                                            <span class="nodo-etiqueta-estado">Siguiente</span>
                                                        <?php endif; ?>
                                                        <span class="nodo-titulo"><?= htmlspecialchars($lec['titulo']) ?></span>
                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                    </ol>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mensaje para cuando ningún resultado coincida -->
                    <div class="estado-vacio" id="sin-resultados" style="display:none;" role="status" aria-live="polite">
                        <h3>No se encontraron lecciones</h3>
                        <p>No hay lecciones que coincidan con los criterios de búsqueda o filtro seleccionados.</p>
                    </div>
                <?php else: ?>
                    <div class="estado-vacio">
                        <h3>Todavía no hay lecciones cargadas</h3>
                        <p>Cuando se agreguen contenidos educativos en la base de datos, aparecerán en tu camino.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="js/seva-ui.js"></script>
    <script>
        if (window.SEVA) {
            SEVA.init(<?= (int) $usuario_id ?>);
        }

        // Filtrado en tiempo real del camino: buscador y botones de nivel.
        const buscador = document.getElementById('buscador');
        const botonesFiltro = document.querySelectorAll('.btn-filtro');
        const tramos = document.querySelectorAll('.tramo-nivel');
        const todosLosNodos = document.querySelectorAll('.nodo-item');
        const sinResultados = document.getElementById('sin-resultados');
        const contadorVisibles = document.getElementById('contador-visibles');
        const PATRON_X = [50, 24, 50, 76];
        let filtroNivelActual = 'todos';

        // Redibuja los conectores SVG según los nodos visibles del tramo.
        function dibujarConectores(tramo) {
            const lista = tramo.querySelector('.nodos-camino');
            const svg = tramo.querySelector('.conectores-camino');
            if (!lista || !svg) {
                return;
            }

            const visibles = Array.from(lista.querySelectorAll('.nodo-item')).filter(function (nodo) {
                return nodo.style.display !== 'none';
            });

            visibles.forEach(function (nodo, indice) {
                nodo.style.setProperty('--x', PATRON_X[indice % PATRON_X.length] + '%');
            });

            if (visibles.length < 2) {
                svg.innerHTML = '';
                svg.setAttribute('viewBox', '0 0 100 100');
                return;
            }

            svg.setAttribute('viewBox', '0 0 100 ' + (visibles.length * 100));

            let trazos = '';
            for (let i = 0; i < visibles.length - 1; i++) {
                const x1 = PATRON_X[i % PATRON_X.length];
                const y1 = i * 100 + 25;
                const x2 = PATRON_X[(i + 1) % PATRON_X.length];
                const y2 = (i + 1) * 100 + 25;
                const medio = (y1 + y2) / 2;
                const estado = visibles[i + 1].dataset.estado || 'bloqueada';
                const clase = estado === 'completada'
                    ? 'conector-completado'
                    : (estado === 'actual' ? 'conector-actual' : 'conector-bloqueado');

                trazos += '<path class="conector-linea ' + clase + '" d="M ' + x1 + ' ' + y1
                    + ' C ' + x1 + ' ' + medio + ', ' + x2 + ' ' + medio + ', ' + x2 + ' ' + y2 + '"></path>';
            }

            svg.innerHTML = trazos;
        }

        function aplicarFiltros() {
            const termino = buscador ? buscador.value.trim().toLowerCase() : '';
            let visibles = 0;

            tramos.forEach(function (tramo) {
                const nivel = tramo.dataset.nivel || '';
                const coincideNivel = filtroNivelActual === 'todos' || nivel === filtroNivelActual;
                let visiblesTramo = 0;

                tramo.querySelectorAll('.nodo-item').forEach(function (nodo) {
                    const titulo = nodo.dataset.titulo || '';
                    const coincideTexto = termino === '' || titulo.includes(termino);
                    const mostrar = coincideNivel && coincideTexto;

                    nodo.style.display = mostrar ? '' : 'none';
                    if (mostrar) {
                        visiblesTramo++;
                        visibles++;
                    }
                });

                tramo.style.display = (coincideNivel && visiblesTramo > 0) ? '' : 'none';
                if (visiblesTramo > 0) {
                    dibujarConectores(tramo);
                }
            });

            if (sinResultados) {
                sinResultados.style.display = (visibles === 0 && todosLosNodos.length > 0) ? 'block' : 'none';
            }
            if (contadorVisibles) {
                contadorVisibles.textContent = visibles + (visibles === 1 ? ' lección' : ' lecciones');
            }
        }

        if (buscador) {
            buscador.addEventListener('input', aplicarFiltros);
        }

        botonesFiltro.forEach(function (btn) {
            btn.addEventListener('click', function () {
                botonesFiltro.forEach(function (b) {
                    b.classList.remove('activo');
                    b.setAttribute('aria-pressed', 'false');
                });
                this.classList.add('activo');
                this.setAttribute('aria-pressed', 'true');
                filtroNivelActual = this.dataset.filtro;
                aplicarFiltros();
            });
        });

        // Estado inicial: conectores alineados con el zigzag del servidor.
        tramos.forEach(dibujarConectores);

        // Menú accesible de opciones de usuario
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
