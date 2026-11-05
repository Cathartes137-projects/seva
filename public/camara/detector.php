<?php
// =============================================================================
// camara/detector.php - Reto por cámara: Alfabeto Dactilológico LSC
// =============================================================================
// Práctica asistida con MediaPipe Hands. La evaluación se basa en reglas
// geométricas sobre los 21 landmarks y no sustituye la valoración de un
// intérprete de LSC.
// =============================================================================

require __DIR__ . '/../../config/conexion.php';
require __DIR__ . '/../../config/seguridad.php';

seguridad_iniciar_sesion();

if (empty($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$base       = '../';

$secuencia = ['A', 'B', 'C', 'L', 'I'];

$descripcionesFallback = [
    'A' => 'Cierra la mano formando un puño y extiende el pulgar hacia un costado, pegado al lado de los demás dedos (no hacia arriba).',
    'B' => 'Extiende los cuatro dedos juntos y rectos hacia arriba, con la palma hacia el frente, y dobla el pulgar cruzándolo por delante de la palma.',
    'C' => 'Curva todos los dedos y el pulgar formando la silueta de la letra C, dejando un espacio abierto entre el pulgar y el índice.',
    'L' => 'Extiende el índice hacia arriba y el pulgar hacia el costado formando una L, con el medio, anular y meñique doblados hacia la palma.',
    'I' => 'Cierra los dedos índice, medio y anular hacia la palma, y extiende el meñique y el pulgar hacia afuera.',
];

$mapaVocabulario = [];
try {
    $marcadores = implode(',', array_fill(0, count($secuencia), '?'));
    $stmt = $pdo->prepare(
        "SELECT palabra, imagen, descripcion
         FROM vocabulario_senas
         WHERE contenido_id = 1 AND palabra IN ($marcadores)"
    );
    $stmt->execute($secuencia);
    foreach ($stmt as $fila) {
        $mapaVocabulario[(string) $fila['palabra']] = $fila;
    }
} catch (PDOException $e) {
    error_log('Reto cámara - no se pudo cargar el vocabulario: ' . $e->getMessage());
}

$retoLetras = [];
foreach ($secuencia as $letra) {
    $fila = $mapaVocabulario[$letra] ?? null;
    $ruta = $fila['imagen'] ?? ('img/lsc/alfabeto/letra_' . $letra . '.png');
    $ruta = ltrim(str_replace('\\', '/', (string) $ruta), '/');

    if ($ruta === '' || strpos($ruta, '..') !== false || !is_file(__DIR__ . '/../' . $ruta)) {
        $ruta = 'img/lecciones/default.svg';
    }

    $retoLetras[] = [
        'letra'       => $letra,
        'imagen'      => '../' . $ruta,
        'descripcion' => (string) ($fila['descripcion'] ?? $descripcionesFallback[$letra]),
    ];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reto por cámara · Alfabeto LSC · SEVA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/estilos.css">
<style>
    .reto {
        max-width: 1240px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .reto-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.5rem 1.6rem;
        border-radius: 1.2rem;
        color: #fff;
        background: linear-gradient(135deg, var(--ink), #123b63);
        box-shadow: var(--sombra);
    }

    .reto-volver {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        min-height: 44px;
        color: #d7e7ff;
        font-weight: 700;
        text-decoration: none;
    }

    .reto-volver:hover {
        color: #fff;
        text-decoration: underline;
    }

    .reto-eyebrow {
        margin: .55rem 0 .15rem;
        color: #8ed0ff;
        font: 700 .72rem 'Space Mono', monospace;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .reto-hero h1 {
        margin: .15rem 0 .35rem;
        color: #fff;
        font-size: clamp(1.7rem, 4vw, 2.6rem);
    }

    .reto-hero p {
        max-width: 62ch;
        margin: 0;
        color: #c9d6ec;
        line-height: 1.55;
    }

    .reto-stats {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .55rem;
    }

    .reto-stat {
        min-width: 88px;
        padding: .55rem .75rem;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: .8rem;
        background: rgba(255, 255, 255, .1);
    }

    .reto-stat span {
        display: block;
        color: #c9d6ec;
        font: 700 .68rem 'Inter', sans-serif;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .reto-stat strong {
        color: #fff;
        font: 800 1.3rem 'Baloo 2', sans-serif;
    }

    .reto-grid {
        display: grid;
        grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
        gap: 1rem;
        margin-top: 1rem;
    }

    .reto-panel {
        padding: 1rem;
        border: 1px solid var(--line);
        border-radius: 1rem;
        background: #fff;
        box-shadow: var(--sombra);
    }

    .reto-panel-cabecera {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .8rem;
    }

    .reto-panel-cabecera h2 {
        margin: 0;
        color: var(--ink);
        font-size: 1.2rem;
    }

    .reto-paso,
    .reto-estado {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: .2rem .65rem;
        border-radius: 99px;
        font-size: .78rem;
        font-weight: 700;
    }

    .reto-paso {
        color: #0b4d8f;
        background: #e8f2ff;
    }

    .reto-estado {
        color: #31506f;
        background: #eef4f8;
    }

    .reto-estado[data-tipo="exito"] {
        color: #146c3a;
        background: #e1f7e9;
    }

    .reto-estado[data-tipo="error"] {
        color: #9b1c1c;
        background: #fde8e8;
    }

    .reto-letra {
        display: grid;
        grid-template-columns: minmax(150px, 220px) minmax(0, 1fr);
        gap: 1rem;
        align-items: center;
    }

    .reto-letra img {
        width: 100%;
        max-height: 300px;
        object-fit: contain;
        padding: .5rem;
        border: 1px solid var(--line);
        border-radius: .85rem;
        background: #f4f8ff;
    }

    .reto-letra-texto {
        min-width: 0;
    }

    .reto-letra-eyebrow {
        margin: 0;
        color: var(--muted);
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .reto-letra-grande {
        margin: .05rem 0 .4rem;
        color: var(--azul);
        font: 800 clamp(3rem, 8vw, 5rem) 'Baloo 2', sans-serif;
        line-height: .95;
    }

    .reto-descripcion {
        margin: 0;
        color: var(--text);
        line-height: 1.55;
    }

    .reto-progreso-linea {
        margin-top: 1rem;
    }

    .reto-barra {
        height: 12px;
        overflow: hidden;
        border-radius: 99px;
        background: var(--line);
    }

    .reto-barra span {
        display: block;
        width: 0;
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--azul), var(--verde-oscuro));
        transition: width .18s ease;
    }

    .reto-coincidencia {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        margin: .45rem 0 0;
        color: var(--muted);
        font-size: .82rem;
    }

    .reto-coincidencia strong {
        color: var(--ink);
    }

    .reto-acciones,
    .reto-cam-controles,
    .reto-modal-acciones {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        margin-top: .9rem;
    }

    .btn-reto {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-width: 44px;
        min-height: 44px;
        padding: .7rem 1rem;
        border: 0;
        border-radius: .7rem;
        background: var(--azul);
        color: #fff;
        font: 700 .92rem 'Inter', sans-serif;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-reto:hover {
        filter: brightness(.94);
    }

    .btn-reto:focus-visible {
        outline: 3px solid var(--sol, #FFB400);
        outline-offset: 3px;
    }

    .btn-reto:disabled {
        opacity: .5;
        cursor: not-allowed;
        filter: none;
    }

    .btn-reto-secundario {
        background: var(--verde-oscuro);
    }

    .btn-reto-ghost {
        border: 1px solid var(--line);
        background: #eef4ff;
        color: var(--ink);
    }

    .reto-ayuda {
        margin-top: 1rem;
        padding-top: .85rem;
        border-top: 1px solid var(--line);
    }

    .reto-ayuda h2 {
        margin: 0 0 .3rem;
        color: var(--ink);
        font-size: 1rem;
    }

    .reto-pista {
        margin: 0 0 .65rem;
        color: var(--text);
        line-height: 1.5;
    }

    .reto-aviso {
        margin: 0;
        padding: .6rem .7rem;
        border-left: 4px solid var(--sol, #FFB400);
        border-radius: .4rem;
        background: #f7f9fc;
        color: var(--muted);
        font-size: .8rem;
        line-height: 1.5;
    }

    .reto-video-wrap {
        position: relative;
        overflow: hidden;
        aspect-ratio: 4 / 3;
        border: 3px solid transparent;
        border-radius: .9rem;
        background: #071426;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .reto-video-wrap video,
    .reto-video-wrap canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reto-video-wrap video {
        background: #071426;
    }

    .reto-video-wrap canvas {
        z-index: 1;
        pointer-events: none;
    }

    .reto-video-wrap.espejo video,
    .reto-video-wrap.espejo canvas {
        transform: scaleX(-1);
    }

    .reto-video-wrap.cerca {
        border-color: var(--sol, #FFB400);
    }

    .reto-video-wrap.exito {
        border-color: #1f9d55;
        box-shadow: 0 0 0 6px rgba(31, 157, 85, .18);
    }

    .reto-video-placeholder {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        padding: 1.25rem;
        color: #d7e7ff;
        text-align: center;
        background: radial-gradient(circle at center, rgba(35, 79, 126, .88), rgba(4, 20, 36, .96));
    }

    .reto-video-placeholder[hidden] {
        display: none;
    }

    .reto-video-placeholder strong {
        color: #fff;
        font: 800 1.15rem 'Baloo 2', sans-serif;
    }

    .reto-video-placeholder p {
        max-width: 300px;
        margin: 0;
        color: #c9d6ec;
        font-size: .85rem;
        line-height: 1.5;
    }

    .reto-feedback {
        position: absolute;
        left: 50%;
        bottom: 1rem;
        z-index: 3;
        max-width: calc(100% - 2rem);
        transform: translateX(-50%);
        padding: .55rem .9rem;
        border-radius: 99px;
        background: rgba(4, 20, 36, .84);
        color: #fff;
        font-size: .88rem;
        font-weight: 700;
        text-align: center;
        transition: background .2s ease;
    }

    .reto-feedback[data-tipo="cerca"] {
        background: #8a5a00;
    }

    .reto-feedback[data-tipo="exito"] {
        background: #1f9d55;
    }

    .reto-feedback[data-tipo="error"] {
        background: #9b1c1c;
    }

    .reto-porcentaje {
        position: absolute;
        top: .7rem;
        right: .7rem;
        z-index: 3;
        min-width: 62px;
        padding: .35rem .55rem;
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: .6rem;
        background: rgba(4, 20, 36, .82);
        color: #fff;
        font: 800 1rem 'Space Mono', monospace;
        text-align: center;
    }

    .reto-note,
    .reto-detalle {
        margin: .65rem 0 0;
        color: var(--muted);
        font-size: .86rem;
        line-height: 1.5;
    }

    .reto-detalle {
        min-height: 2.6em;
    }

    .reto-guardado {
        margin: .7rem 0 0;
        font-size: .84rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .reto-guardado[data-tipo="ok"] {
        color: #146c3a;
    }

    .reto-guardado[data-tipo="error"] {
        color: #9b1c1c;
    }

    .reto-modal-fondo {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(3, 12, 24, .68);
    }

    .reto-modal-fondo.abierto {
        display: flex;
    }

    .reto-modal {
        width: min(540px, 100%);
        padding: 1.5rem;
        border-radius: 1.1rem;
        background: #fff;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
        text-align: center;
    }

    .reto-modal-icono {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin-bottom: .5rem;
        border-radius: 50%;
        background: #e1f7e9;
        color: #146c3a;
        font-size: 2rem;
    }

    .reto-modal h2 {
        margin: .2rem 0 .4rem;
        color: var(--ink);
        font-size: 1.6rem;
    }

    .reto-modal p {
        color: var(--text);
        line-height: 1.55;
    }

    .reto-xp {
        margin: .8rem 0;
        color: var(--verde-oscuro);
        font: 800 2.2rem 'Baloo 2', sans-serif;
    }

    .reto-xp small {
        font: 600 .85rem 'Inter', sans-serif;
        color: var(--muted);
    }

    .reto-modal-acciones {
        justify-content: center;
    }

    body.modal-abierto {
        overflow: hidden;
    }

    @media (max-width: 900px) {
        .reto-grid {
            grid-template-columns: 1fr;
        }

        .reto-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .reto-stats {
            justify-content: flex-start;
        }
    }

    @media (max-width: 560px) {
        .reto {
            padding: .8rem;
        }

        .reto-hero {
            padding: 1rem;
        }

        .reto-letra {
            grid-template-columns: 1fr;
        }

        .reto-letra img {
            max-height: 220px;
        }

        .reto-panel-cabecera {
            flex-wrap: wrap;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .reto *,
        .reto *::before,
        .reto *::after {
            scroll-behavior: auto !important;
            animation-duration: .001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .001ms !important;
        }
    }
</style>
</head>
<body class="pagina-camara">
<?php require __DIR__ . '/../partials/topbar.php'; ?>

<main class="reto" id="contenido">
    <header class="reto-hero">
        <div>
            <a class="reto-volver" href="../dashboard.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Volver al dashboard
            </a>
            <p class="reto-eyebrow">Lección 1 · Práctica con cámara</p>
            <h1>Reto del Alfabeto LSC</h1>
            <p>Completa 5 letras frente a la cámara. La meta es sostener cada seña con al menos un 80 % de coincidencia durante un segundo.</p>
        </div>
        <div class="reto-stats" aria-label="Racha y experiencia">
            <div class="reto-stat">
                <span>Racha</span>
                <strong data-racha>0</strong> días
            </div>
            <div class="reto-stat">
                <span>XP</span>
                <strong data-xp>0</strong> XP
            </div>
            <div class="reto-stat">
                <span>Avance</span>
                <strong id="reto-contador">0 / 5</strong>
            </div>
        </div>
    </header>

    <div class="reto-grid">
        <section class="reto-panel" aria-labelledby="titulo-objetivo">
            <div class="reto-panel-cabecera">
                <span class="reto-paso">Letra <b id="reto-indice">1</b> de 5</span>
                <span class="reto-estado" id="reto-estado" role="status" aria-live="polite">Esperando cámara</span>
            </div>
            <h2 id="titulo-objetivo" class="visually-hidden">Letra objetivo</h2>

            <div class="reto-letra">
                <img id="reto-imagen"
                     src="<?= htmlspecialchars($retoLetras[0]['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Seña de la letra <?= htmlspecialchars($retoLetras[0]['letra'], ENT_QUOTES, 'UTF-8') ?> en Lengua de Señas Colombiana"
                     decoding="async">
                <div class="reto-letra-texto">
                    <p class="reto-letra-eyebrow">Letra objetivo</p>
                    <p class="reto-letra-grande" id="reto-letra"><?= htmlspecialchars($retoLetras[0]['letra'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="reto-descripcion" id="reto-descripcion"><?= htmlspecialchars($retoLetras[0]['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <div class="reto-progreso-linea">
                <div class="reto-barra" id="reto-barra-progreso" role="progressbar" aria-label="Letras completadas" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0">
                    <span id="reto-barra"></span>
                </div>
                <p class="reto-coincidencia">
                    <span>Coincidencia en vivo en el visor</span>
                    <span id="reto-contador-linea">0 / 5 completadas</span>
                </p>
            </div>

            <div class="reto-acciones">
                <button type="button" class="btn-reto" id="reto-siguiente">Siguiente letra / Omitir</button>
                <button type="button" class="btn-reto btn-reto-ghost" id="reto-reiniciar">Reiniciar reto</button>
            </div>

            <div class="reto-ayuda">
                <h2>Ayuda rápida</h2>
                <p class="reto-pista" id="reto-pista">Cierra el puño y lleva el pulgar al costado, sin levantarlo.</p>
                <p class="reto-aviso"><strong>Práctica asistida:</strong> la evaluación usa la posición de la mano y puede variar según la cámara, la luz y el ángulo. No sustituye la valoración de un intérprete de LSC.</p>
            </div>
        </section>

        <section class="reto-panel" aria-labelledby="titulo-camara">
            <div class="reto-panel-cabecera">
                <h2 id="titulo-camara">Cámara en vivo</h2>
                <span class="reto-estado" id="estado">Cámara apagada</span>
            </div>

            <div class="reto-video-wrap espejo" id="reto-video-wrap">
                <video id="video" playsinline muted autoplay aria-label="Vista de la cámara para practicar las letras del alfabeto LSC"></video>
                <canvas id="overlay" aria-hidden="true"></canvas>
                <div class="reto-video-placeholder" id="reto-placeholder">
                    <strong>Activa la cámara</strong>
                    <p>Coloca una mano completa dentro del encuadre, con buena luz y el fondo despejado.</p>
                </div>
                <div class="reto-feedback" id="reto-feedback">Activa la cámara o practica sin cámara.</div>
                <div class="reto-porcentaje" id="reto-porcentaje" aria-label="Porcentaje de coincidencia">0%</div>
            </div>

            <div class="reto-cam-controles">
                <button type="button" class="btn-reto" id="start">Activar cámara</button>
                <button type="button" class="btn-reto btn-reto-secundario" id="stop" disabled>Detener cámara</button>
                <button type="button" class="btn-reto btn-reto-ghost" id="btn-pausar" aria-pressed="false" disabled>Pausar cámara</button>
                <button type="button" class="btn-reto btn-reto-ghost" id="btn-espejo" aria-pressed="true">Vista espejo: activa</button>
                <button type="button" class="btn-reto btn-reto-ghost" id="btn-sin-camara">Practicar sin cámara</button>
            </div>

            <p class="reto-detalle" id="detalle">Activa la cámara para comenzar. También puedes usar el modo sin cámara.</p>
            <p class="reto-note">Para este reto muestra una sola mano. La mano contraria puede servir de apoyo, pero no debe tapar la seña.</p>
        </section>
    </div>
</main>

<div class="reto-modal-fondo" id="reto-modal" role="dialog" aria-modal="true" aria-labelledby="reto-modal-titulo" aria-describedby="reto-modal-texto" aria-hidden="true">
    <div class="reto-modal">
        <div class="reto-modal-icono" aria-hidden="true">✓</div>
        <h2 id="reto-modal-titulo">¡Reto completado!</h2>
        <p id="reto-modal-texto">Completaste las 5 letras del reto.</p>
        <p class="reto-xp">+<span id="reto-xp-ganado">0</span> XP <small>de práctica</small></p>
        <p class="reto-guardado" id="reto-guardado" role="status" aria-live="polite">Guardando progreso...</p>
        <div class="reto-modal-acciones">
            <a class="btn-reto" href="../leccion.php?id=1">Ver Lección 1</a>
            <button type="button" class="btn-reto btn-reto-ghost" id="reto-reintentar" hidden>Reintentar guardado</button>
            <button type="button" class="btn-reto btn-reto-ghost" id="reto-modal-cerrar">Cerrar</button>
        </div>
    </div>
</div>

<p id="reto-anuncio" class="visually-hidden" role="status" aria-live="polite"></p>

<input type="hidden" id="reto-csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands/hands.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
<script>
(() => {
    'use strict';

    const LETRAS = <?= json_encode($retoLetras, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>;
    const CSRF = (document.getElementById('reto-csrf') || {}).value || '';
    const UMBRAL = 80;
    const MS_SOSTENIDO = 1000;
    const XP_POR_LETRA = 10;
    const XP_BONO = 20;
    const reduceMotion = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);

    const $ = (id) => document.getElementById(id);
    const video = $('video');
    const canvas = $('overlay');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const wrap = $('reto-video-wrap');
    const placeholder = $('reto-placeholder');
    const feedback = $('reto-feedback');
    const porcentaje = $('reto-porcentaje');
    const anuncio = $('reto-anuncio');
    const detalle = $('detalle');
    const estado = $('estado');
    const imagen = $('reto-imagen');
    const letraEl = $('reto-letra');
    const descripcionEl = $('reto-descripcion');
    const pistaEl = $('reto-pista');
    const indiceEl = $('reto-indice');
    const contadorEl = $('reto-contador');
    const contadorLineaEl = $('reto-contador-linea');
    const barra = $('reto-barra');
    const barraProgreso = $('reto-barra-progreso');
    const btnIniciar = $('start');
    const btnDetener = $('stop');
    const btnPausar = $('btn-pausar');
    const btnEspejo = $('btn-espejo');
    const btnSinCamara = $('btn-sin-camara');
    const btnSiguiente = $('reto-siguiente');
    const btnReiniciar = $('reto-reiniciar');
    const modal = $('reto-modal');
    const modalTexto = $('reto-modal-texto');
    const modalXp = $('reto-xp-ganado');
    const modalGuardado = $('reto-guardado');
    const btnReintentar = $('reto-reintentar');
    const btnCerrarModal = $('reto-modal-cerrar');

    const PISTAS = {
        A: 'Cierra el puño y lleva el pulgar al costado, sin levantarlo.',
        B: 'Abre la palma con los cuatro dedos juntos y cruza el pulgar al frente.',
        C: 'Curva los dedos y el pulgar formando una C abierta.',
        L: 'Levanta el índice y extiende el pulgar formando una L.',
        I: 'Levanta el meñique y separa el pulgar; los demás dedos quedan cerrados.'
    };

    let indice = 0;
    let aciertos = 0;
    let completado = false;
    let bloqueado = false;
    let xpOtorgado = false;
    let modoSinCamara = false;
    let active = false;
    let starting = false;
    let pausado = false;
    let hands = null;
    let camera = null;
    let inicioSostenido = 0;
    let ultimoFrame = 0;
    let guardando = false;
    let ultimoDetalle = '';
    let ultimoFocoModal = null;
    let generacion = 0;

    function setFeedback(texto, tipo) {
        if (!feedback) return;
        if (feedback.textContent !== texto) {
            feedback.textContent = texto;
        }
        feedback.dataset.tipo = tipo || '';
    }

    function setScore(valor) {
        const n = Math.max(0, Math.min(100, Math.round(Number(valor) || 0)));
        if (porcentaje) {
            porcentaje.textContent = n + '%';
        }
        if (wrap && !wrap.classList.contains('exito')) {
            wrap.classList.toggle('cerca', n >= UMBRAL);
        }
    }

    function resetSostenido() {
        inicioSostenido = 0;
    }

    function anunciar(mensaje) {
        if (!anuncio) return;
        anuncio.textContent = '';
        window.setTimeout(() => {
            anuncio.textContent = mensaje;
        }, 40);
    }

    function setEstado(texto, tipo) {
        if (!estado) return;
        estado.textContent = texto;
        estado.dataset.tipo = tipo || '';
    }

    function setDetalle(texto) {
        if (!detalle || ultimoDetalle === texto) return;
        ultimoDetalle = texto;
        detalle.textContent = texto;
    }

    function mostrarPlaceholder(titulo, texto) {
        if (!placeholder) return;
        const strong = placeholder.querySelector('strong');
        const p = placeholder.querySelector('p');
        if (strong) strong.textContent = titulo;
        if (p) p.textContent = texto;
        placeholder.hidden = false;
    }

    function ocultarPlaceholder() {
        if (placeholder) placeholder.hidden = true;
    }

    function actualizarObjetivo() {
        const item = LETRAS[indice] || LETRAS[0];
        if (!item) return;

        if (imagen) {
            imagen.src = item.imagen;
            imagen.alt = 'Seña de la letra ' + item.letra + ' en Lengua de Señas Colombiana';
        }
        if (letraEl) letraEl.textContent = item.letra;
        if (descripcionEl) descripcionEl.textContent = item.descripcion;
        if (pistaEl) pistaEl.textContent = PISTAS[item.letra] || '';
        if (indiceEl) indiceEl.textContent = String(Math.min(indice + 1, LETRAS.length));
        if (estado) {
            setEstado(completado ? 'Reto completado' : (modoSinCamara ? 'Modo sin cámara' : (active ? 'En práctica' : 'Esperando cámara')), completado ? 'exito' : '');
        }
        setScore(0);
        resetSostenido();
    }

    function actualizarProgreso() {
        const total = LETRAS.length;
        const hechas = Math.min(indice, total);
        const pct = total > 0 ? (hechas / total) * 100 : 0;

        if (contadorEl) contadorEl.textContent = hechas + ' / ' + total;
        if (contadorLineaEl) contadorLineaEl.textContent = hechas + ' / ' + total + ' completadas';
        if (barra) barra.style.width = pct + '%';
        if (barraProgreso) barraProgreso.setAttribute('aria-valuenow', String(hechas));
        if (indiceEl && !completado) indiceEl.textContent = String(Math.min(indice + 1, total));
    }

    const DEDOS = {
        index:  { mcp: 5,  pip: 6,  dip: 7,  tip: 8  },
        middle: { mcp: 9,  pip: 10, dip: 11, tip: 12 },
        ring:   { mcp: 13, pip: 14, dip: 15, tip: 16 },
        pinky:  { mcp: 17, pip: 18, dip: 19, tip: 20 }
    };

    function dist2(a, b) {
        return Math.hypot(a.x - b.x, a.y - b.y);
    }

    function angle2(a, b, c) {
        const abx = a.x - b.x;
        const aby = a.y - b.y;
        const cbx = c.x - b.x;
        const cby = c.y - b.y;
        const mag = Math.hypot(abx, aby) * Math.hypot(cbx, cby);
        if (!mag) return 180;
        const dot = abx * cbx + aby * cby;
        return Math.acos(Math.max(-1, Math.min(1, dot / mag))) * 180 / Math.PI;
    }

    function clamp01(v) {
        return Math.max(0, Math.min(1, v));
    }

    function promedio(valores) {
        if (!valores.length) return 0;
        return valores.reduce((a, b) => a + b, 0) / valores.length;
    }

    function banda(valor, min, max, margen) {
        if (valor < min) return clamp01(1 - (min - valor) / margen);
        if (valor > max) return clamp01(1 - (valor - max) / margen);
        return 1;
    }

    function calcularFeatures(lm) {
        if (!lm || lm.length < 21) return null;

        const wrist = lm[0];
        const indexMcp = lm[5];
        const middleMcp = lm[9];
        const ringMcp = lm[13];
        const pinkyMcp = lm[17];
        const scale = Math.max(
            dist2(wrist, middleMcp),
            dist2(indexMcp, pinkyMcp) * 0.82,
            0.000001
        );

        const palm = {
            x: (wrist.x + indexMcp.x + middleMcp.x + ringMcp.x + pinkyMcp.x) / 5,
            y: (wrist.y + indexMcp.y + middleMcp.y + ringMcp.y + pinkyMcp.y) / 5
        };

        const ext = {};
        Object.keys(DEDOS).forEach((nombre) => {
            const dedo = DEDOS[nombre];
            const tipWrist = dist2(lm[dedo.tip], wrist);
            const pipWrist = dist2(lm[dedo.pip], wrist);
            const ratio = tipWrist / (pipWrist + 0.000001);
            const recto = clamp01((angle2(lm[dedo.mcp], lm[dedo.pip], lm[dedo.tip]) - 112) / 58);
            ext[nombre] = clamp01(
                0.65 * clamp01((ratio - 0.98) / 0.30) +
                0.35 * recto
            );
        });

        const avgExt = promedio(Object.values(ext));
        const avgCurl = 1 - avgExt;
        const maxExt = Math.max(ext.index, ext.middle, ext.ring, ext.pinky);
        const tipPalm = promedio(
            ['index', 'middle', 'ring', 'pinky'].map(
                (nombre) => dist2(lm[DEDOS[nombre].tip], palm) / scale
            )
        );

        const thumbStraight = clamp01(
            (angle2(lm[2], lm[3], lm[4]) - 110) / 60
        );
        const thumbOut = clamp01(
            (dist2(lm[4], lm[5]) / scale - 0.35) / 0.65
        );
        const thumbIndexGap = dist2(lm[4], lm[8]) / scale;
        const thumbPalm = dist2(lm[4], palm) / scale;

        const gapAvg = promedio([
            dist2(lm[8], lm[12]),
            dist2(lm[12], lm[16]),
            dist2(lm[16], lm[20])
        ]) / scale;
        const together = 1 - clamp01((gapAvg - 0.12) / 0.35);

        return {
            scale,
            ext,
            avgExt,
            avgCurl,
            maxExt,
            tipPalm,
            thumbStraight,
            thumbOut,
            thumbIndexGap,
            thumbPalm,
            gapAvg,
            together
        };
    }

    function evaluarLetra(letra, lm) {
        const f = calcularFeatures(lm);
        if (!f) return 0;

        const { ext, avgExt, avgCurl, maxExt, tipPalm, thumbOut, thumbIndexGap, together } = f;
        const noneExt = 1 - clamp01((maxExt - 0.55) / 0.30);
        let pct = 0;

        switch (letra) {
            case 'A':
                pct = 100 * (
                    0.55 * avgCurl +
                    0.15 * banda(tipPalm, 0, 0.45, 0.12) +
                    0.10 * clamp01((thumbOut - 0.08) / 0.55) +
                    0.20 * noneExt
                );
                break;

            case 'B':
                pct = 100 * (
                    0.70 * avgExt +
                    0.20 * together +
                    0.10 * (1 - thumbOut)
                );
                break;

            case 'C':
                pct = 100 * (
                    0.35 * banda(avgExt, 0.08, 0.55, 0.18) +
                    0.25 * banda(tipPalm, 0.55, 1.05, 0.18) +
                    0.15 * clamp01((thumbIndexGap - 0.35) / 0.55) +
                    0.25 * noneExt
                );
                break;

            case 'L':
                pct = 100 * (
                    0.45 * ext.index +
                    0.35 * promedio([1 - ext.middle, 1 - ext.ring, 1 - ext.pinky]) +
                    0.20 * thumbOut
                );
                break;

            case 'I':
                pct = 100 * (
                    0.45 * ext.pinky +
                    0.35 * promedio([1 - ext.index, 1 - ext.middle, 1 - ext.ring]) +
                    0.20 * thumbOut
                );
                break;

            default:
                pct = 0;
        }

        return Math.max(0, Math.min(100, pct));
    }

    window.SEVA_Reto = {
        letras: LETRAS,
        calcularFeatures,
        evaluarLetra,
        evaluarTodas: (lm) => {
            const salida = {};
            LETRAS.forEach((item) => {
                salida[item.letra] = evaluarLetra(item.letra, lm);
            });
            return salida;
        },
        estado: () => ({ indice, aciertos, completado, modoSinCamara, active, pausado })
    };

    function dibujarMano(lm, color, grosor) {
        if (!ctx) return;

        if (window.drawConnectors && window.drawLandmarks && window.HAND_CONNECTIONS) {
            try {
                drawConnectors(ctx, lm, HAND_CONNECTIONS, { color, lineWidth: grosor });
                drawLandmarks(ctx, lm, { color: '#FFB400', lineWidth: 1, radius: 3 });
                return;
            } catch (e) {
                /* Si drawing_utils falla, se usa el trazado manual. */
            }
        }

        const enlaces = [
            [0, 1], [1, 2], [2, 3], [3, 4],
            [0, 5], [5, 6], [6, 7], [7, 8],
            [5, 9], [9, 10], [10, 11], [11, 12],
            [9, 13], [13, 14], [14, 15], [15, 16],
            [13, 17], [17, 18], [18, 19], [19, 20],
            [0, 17]
        ];

        ctx.strokeStyle = color;
        ctx.lineWidth = grosor;
        enlaces.forEach(([a, b]) => {
            ctx.beginPath();
            ctx.moveTo(lm[a].x * canvas.width, lm[a].y * canvas.height);
            ctx.lineTo(lm[b].x * canvas.width, lm[b].y * canvas.height);
            ctx.stroke();
        });

        lm.forEach((p, i) => {
            ctx.beginPath();
            ctx.arc(
                p.x * canvas.width,
                p.y * canvas.height,
                i % 4 === 0 ? 5 : 3,
                0,
                Math.PI * 2
            );
            ctx.fillStyle = i % 4 === 0 ? '#FFB400' : '#28a745';
            ctx.fill();
        });
    }

    function procesarCoincidencia(score) {
        if (completado || bloqueado || modoSinCamara) return;

        const ahora = performance.now();
        if (ahora - ultimoFrame > 350) {
            inicioSostenido = 0;
        }
        ultimoFrame = ahora;

        if (score >= UMBRAL) {
            if (!inicioSostenido) {
                inicioSostenido = ahora;
            }

            const transcurrido = ahora - inicioSostenido;
            if (transcurrido >= MS_SOSTENIDO) {
                detectarLetra();
            } else {
                setFeedback('¡Vas bien! Mantén la seña', 'cerca');
            }
        } else {
            inicioSostenido = 0;
            setFeedback('Ajusta la seña', '');
        }
    }

    function onResults(results) {
        if (!active || pausado || !ctx || !canvas || !video) return;

        const w = video.videoWidth || 640;
        const h = video.videoHeight || 480;
        if (canvas.width !== w || canvas.height !== h) {
            canvas.width = w;
            canvas.height = h;
        }

        ctx.clearRect(0, 0, w, h);

        const manos = (results && results.multiHandLandmarks) || [];
        if (!manos.length) {
            setScore(0);
            resetSostenido();
            if (wrap && !wrap.classList.contains('exito')) {
                wrap.classList.remove('cerca');
            }
            if (!completado && !bloqueado) {
                setFeedback('Muestra una mano frente a la cámara', '');
            }
            setDetalle('No se detectan manos. Acércalas, mejora la luz y evita el contraluz.');
            return;
        }

        const actual = LETRAS[indice];
        if (!actual) return;

        const evaluaciones = manos.map((lm) => ({
            lm,
            score: evaluarLetra(actual.letra, lm)
        }));

        let mejor = evaluaciones[0];
        evaluaciones.forEach((item) => {
            if (item.score > mejor.score) mejor = item;
        });

        evaluaciones.forEach((item) => {
            dibujarMano(
                item.lm,
                item === mejor ? '#35d07f' : '#57b7ff',
                item === mejor ? 5 : 3
            );
        });

        setScore(mejor.score);

        if (manos.length > 1) {
            setDetalle('Se detectaron varias manos. Para este reto muestra solo una mano.');
        } else {
            setDetalle('Mano detectada. Ajusta la seña hasta superar el 80 % de coincidencia.');
        }

        if (!completado && !bloqueado) {
            procesarCoincidencia(mejor.score);
        }
    }

    function mensajeErrorCamara(e) {
        if (!e) return 'No se pudo iniciar la cámara.';
        switch (e.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return 'Permiso denegado. Autoriza la cámara en el navegador y vuelve a intentarlo.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                return 'No se encontró una cámara conectada.';
            case 'NotReadableError':
                return 'La cámara está siendo usada por otra aplicación.';
            case 'OverconstrainedError':
                return 'La cámara no admite la configuración solicitada.';
            default:
                return 'No se pudo iniciar la cámara: ' + (e.message || 'revisa el dispositivo y la conexión.');
        }
    }

    async function iniciarCamara() {
        if (active || starting) return;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setEstado('Cámara no disponible', 'error');
            setFeedback('Tu navegador no permite usar la cámara. Usa “Practicar sin cámara”.', 'error');
            setDetalle('Abre el sitio desde http://localhost o HTTPS, con Chrome o Edge.');
            return;
        }

        if (!window.Hands || !window.Camera) {
            setEstado('MediaPipe no disponible', 'error');
            setFeedback('No se pudo cargar MediaPipe. Revisa la conexión o usa “Practicar sin cámara”.', 'error');
            setDetalle('Comprueba tu conexión a Internet y recarga la página.');
            return;
        }

        starting = true;
        btnIniciar.disabled = true;
        setEstado('Solicitando permiso');
        setFeedback('Solicitando permiso para la cámara…', '');

        try {
            hands = new Hands({
                locateFile: (file) => 'https://cdn.jsdelivr.net/npm/@mediapipe/hands/' + file
            });
            hands.setOptions({
                maxNumHands: 2,
                modelComplexity: 1,
                minDetectionConfidence: 0.65,
                minTrackingConfidence: 0.6
            });
            hands.onResults(onResults);

            camera = new Camera(video, {
                onFrame: async () => {
                    if (!active || pausado || !hands) return;
                    try {
                        await hands.send({ image: video });
                    } catch (e) {
                        /* Un frame aislado puede fallar mientras la cámara inicia. */
                    }
                },
                width: 1280,
                height: 720
            });

            active = true;
            await camera.start();

            modoSinCamara = false;
            ocultarPlaceholder();
            btnDetener.disabled = false;
            btnPausar.disabled = false;
            btnIniciar.disabled = true;
            if (btnSinCamara) btnSinCamara.setAttribute('aria-pressed', 'false');
            setEstado('Cámara activa');
            setFeedback('Muestra la letra ' + LETRAS[indice].letra, '');
            setDetalle('Cámara activa. Coloca una mano completa dentro del encuadre.');
            anunciar('Cámara activada. Muestra la letra ' + LETRAS[indice].letra + '.');
        } catch (e) {
            detenerCamara(true);
            const mensaje = mensajeErrorCamara(e);
            setEstado('Cámara no disponible', 'error');
            setFeedback('No se pudo iniciar la cámara. Usa “Practicar sin cámara”.', 'error');
            setDetalle(mensaje);
            mostrarPlaceholder('Cámara no disponible', mensaje);
            anunciar('No se pudo iniciar la cámara. Puedes practicar sin cámara.');
        } finally {
            starting = false;
            if (!active) {
                btnIniciar.disabled = completado;
            }
        }
    }

    function detenerCamara(silencioso) {
        active = false;
        pausado = false;
        resetSostenido();

        try {
            if (camera) camera.stop();
        } catch (e) {
            /* La cámara ya estaba detenida. */
        }

        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach((track) => {
                try {
                    track.stop();
                } catch (e) {
                    /* Ignorar pistas ya cerradas. */
                }
            });
            video.srcObject = null;
        }

        try {
            if (hands) hands.close();
        } catch (e) {
            /* MediaPipe ya estaba cerrado. */
        }

        hands = null;
        camera = null;

        if (ctx && canvas) {
            ctx.clearRect(0, 0, canvas.width || 0, canvas.height || 0);
        }

        if (wrap) {
            wrap.classList.remove('exito', 'cerca');
        }

        setScore(0);

        if (btnPausar) {
            btnPausar.textContent = 'Pausar cámara';
            btnPausar.setAttribute('aria-pressed', 'false');
            btnPausar.disabled = true;
        }
        if (btnDetener) btnDetener.disabled = true;
        if (btnIniciar) btnIniciar.disabled = completado;

        if (!silencioso) {
            setEstado('Cámara apagada');
            setFeedback('Activa la cámara para comenzar.', '');
            setDetalle('Cámara apagada. Activa la cámara o usa el modo sin cámara.');
            mostrarPlaceholder(
                'Activa la cámara',
                'Coloca una mano completa dentro del encuadre, con buena luz y el fondo despejado.'
            );
        }
    }

    function pausarCamara() {
        if (!active) return;

        pausado = !pausado;

        if (video && video.srcObject) {
            video.srcObject.getVideoTracks().forEach((track) => {
                try {
                    track.enabled = !pausado;
                } catch (e) {
                    /* Ignorar pistas no disponibles. */
                }
            });
        }

        if (pausado) {
            try {
                video.pause();
            } catch (e) {
                /* El video puede no estar listo. */
            }
            resetSostenido();
            setScore(0);
            setFeedback('Cámara en pausa.', '');
            setEstado('Cámara en pausa');
            btnPausar.textContent = 'Reanudar cámara';
        } else {
            const reproduccion = video.play();
            if (reproduccion && reproduccion.catch) {
                reproduccion.catch(() => {});
            }
            setFeedback('Cámara reanudada. Muestra la letra ' + LETRAS[indice].letra, '');
            setEstado('Cámara activa');
            btnPausar.textContent = 'Pausar cámara';
        }

        btnPausar.setAttribute('aria-pressed', pausado ? 'true' : 'false');
    }

    function alternarEspejo() {
        if (!wrap) return;
        const activo = wrap.classList.toggle('espejo');
        if (btnEspejo) {
            btnEspejo.setAttribute('aria-pressed', activo ? 'true' : 'false');
            btnEspejo.textContent = 'Vista espejo: ' + (activo ? 'activa' : 'desactivada');
        }
    }

    function detectarLetra() {
        if (completado || bloqueado) return;

        bloqueado = true;
        resetSostenido();
        aciertos++;

        const letra = LETRAS[indice].letra;
        const generacionActual = generacion;

        if (wrap) {
            wrap.classList.remove('cerca');
            wrap.classList.add('exito');
        }

        setScore(100);
        setFeedback('¡Excelente seña!', 'exito');
        setDetalle('Letra ' + letra + ' detectada. ¡Buen trabajo!');
        anunciar('Letra ' + letra + ' detectada.');

        if (btnSiguiente) btnSiguiente.disabled = true;
        if (window.SEVA && SEVA.toast) {
            SEVA.toast('¡Letra ' + letra + ' detectada!', 'exito');
        }

        const espera = reduceMotion ? 350 : 900;
        window.setTimeout(() => {
            if (generacion !== generacionActual) return;
            if (wrap) wrap.classList.remove('exito');
            if (btnSiguiente) btnSiguiente.disabled = false;
            bloqueado = false;
            avanzarLetra(false);
        }, espera);
    }

    function avanzarLetra(manual) {
        if (completado) return;

        resetSostenido();
        if (wrap) wrap.classList.remove('exito', 'cerca');
        setScore(0);

        indice++;

        if (indice >= LETRAS.length) {
            finalizarReto();
            return;
        }

        bloqueado = false;
        if (btnSiguiente) btnSiguiente.disabled = false;

        actualizarObjetivo();
        actualizarProgreso();

        const letra = LETRAS[indice].letra;
        setFeedback(
            manual ? 'Siguiente: letra ' + letra : 'Ahora realiza la letra ' + letra,
            ''
        );
        anunciar('Letra ' + letra + '. ' + LETRAS[indice].descripcion);
    }

    function marcarFinalUI() {
        if (indiceEl) indiceEl.textContent = String(LETRAS.length);
        if (btnSiguiente) {
            btnSiguiente.disabled = true;
            btnSiguiente.textContent = 'Reto completado';
        }
        setEstado('Reto completado', 'exito');
    }

    function finalizarReto() {
        if (completado) return;

        completado = true;
        bloqueado = true;
        resetSostenido();
        indice = LETRAS.length;

        actualizarProgreso();
        marcarFinalUI();
        detenerCamara(true);
        setScore(100);
        setFeedback('¡Reto completado!', 'exito');
        setEstado('Reto completado', 'exito');
        mostrarPlaceholder(
            'Reto completado',
            'Puedes cerrar este mensaje, revisar la lección o reiniciar el reto.'
        );

        const xp = XP_BONO + aciertos * XP_POR_LETRA;
        if (modalXp) modalXp.textContent = String(xp);
        if (modalTexto) {
            modalTexto.textContent = aciertos === LETRAS.length
                ? 'Detectaste correctamente las 5 letras del reto. La Lección 1 quedará marcada como completada.'
                : 'Completaste el reto con ' + aciertos + ' de ' + LETRAS.length + ' letras detectadas; las demás se omitieron.';
        }
        if (modalGuardado) {
            modalGuardado.textContent = 'Guardando progreso…';
            modalGuardado.dataset.tipo = '';
        }
        if (btnReintentar) {
            btnReintentar.hidden = true;
            btnReintentar.disabled = false;
        }

        abrirModal();

        if (!xpOtorgado) {
            xpOtorgado = true;
            if (window.SEVA && SEVA.sumarXP) {
                SEVA.sumarXP(xp);
            }
        }

        anunciar('Reto completado. Ganaste ' + xp + ' XP de práctica.');
        guardarProgreso();
    }

    function abrirModal() {
        if (!modal) return;

        ultimoFocoModal = document.activeElement;
        modal.classList.add('abierto');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-abierto');

        if (window.SEVA && SEVA.confeti) {
            SEVA.confeti(160);
        }

        const primero = modal.querySelector('a[href], button:not([disabled])');
        window.setTimeout(() => {
            if (primero) primero.focus();
        }, 40);
    }

    function cerrarModal() {
        if (!modal) return;

        modal.classList.remove('abierto');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-abierto');

        const destino = (ultimoFocoModal && !ultimoFocoModal.disabled)
            ? ultimoFocoModal
            : btnReiniciar;
        if (destino && typeof destino.focus === 'function') {
            destino.focus();
        }
    }

    async function guardarProgreso() {
        if (guardando) return;

        guardando = true;
        if (btnReintentar) btnReintentar.disabled = true;
        if (modalGuardado) {
            modalGuardado.textContent = 'Guardando progreso…';
            modalGuardado.dataset.tipo = '';
        }

        try {
            const cuerpo = new URLSearchParams();
            cuerpo.set('csrf_token', CSRF);
            cuerpo.set('marcar_completada', '1');

            const respuesta = await fetch('../leccion.php?id=1', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: cuerpo.toString(),
                credentials: 'same-origin'
            });

            const datos = await respuesta.json().catch(() => ({}));
            if (!respuesta.ok || !datos.exito) {
                throw new Error(datos.error || 'No se pudo guardar el progreso');
            }

            if (modalGuardado) {
                modalGuardado.textContent = 'Progreso guardado. La Lección 1 quedó completada.';
                modalGuardado.dataset.tipo = 'ok';
            }
            if (btnReintentar) btnReintentar.hidden = true;
        } catch (e) {
            if (modalGuardado) {
                modalGuardado.textContent = 'No pudimos guardar el avance. Revisa tu conexión e inténtalo de nuevo.';
                modalGuardado.dataset.tipo = 'error';
            }
            if (btnReintentar) btnReintentar.hidden = false;
        } finally {
            guardando = false;
            if (btnReintentar) btnReintentar.disabled = false;
        }
    }

    function activarModoSinCamara() {
        if (active) {
            detenerCamara(true);
        }

        modoSinCamara = true;
        if (btnSinCamara) btnSinCamara.setAttribute('aria-pressed', 'true');
        if (btnIniciar) btnIniciar.disabled = completado;
        setEstado('Modo sin cámara');
        setFeedback('Modo sin cámara: avanza cuando tengas lista la seña.', '');
        setDetalle('Modo sin cámara activo. Usa “Siguiente letra / Omitir” para avanzar.');
        mostrarPlaceholder(
            'Modo sin cámara',
            'Practica con la tarjeta de la izquierda y avanza cuando estés listo.'
        );
        anunciar('Modo sin cámara activado. Usa el botón Siguiente letra para avanzar.');
    }

    function reiniciarReto() {
        cerrarModal();
        generacion++;

        indice = 0;
        aciertos = 0;
        completado = false;
        bloqueado = false;
        xpOtorgado = false;
        modoSinCamara = false;
        resetSostenido();

        if (btnSiguiente) {
            btnSiguiente.disabled = false;
            btnSiguiente.textContent = 'Siguiente letra / Omitir';
        }
        if (btnSinCamara) btnSinCamara.setAttribute('aria-pressed', 'false');
        if (btnIniciar) btnIniciar.disabled = false;
        if (btnReintentar) {
            btnReintentar.hidden = true;
            btnReintentar.disabled = false;
        }
        if (modalGuardado) {
            modalGuardado.textContent = 'Guardando progreso…';
            modalGuardado.dataset.tipo = '';
        }

        actualizarObjetivo();
        actualizarProgreso();
        setScore(0);

        if (active) {
            setEstado('Cámara activa');
            setFeedback('Muestra la letra ' + LETRAS[0].letra, '');
            ocultarPlaceholder();
            setDetalle('Reto reiniciado. Coloca una mano dentro del encuadre.');
        } else {
            setEstado('Esperando cámara');
            setFeedback('Activa la cámara o practica sin cámara.', '');
            mostrarPlaceholder(
                'Activa la cámara',
                'Coloca una mano completa dentro del encuadre, con buena luz y el fondo despejado.'
            );
            setDetalle('Reto reiniciado. Activa la cámara o usa el modo sin cámara.');
        }

        anunciar('Reto reiniciado. Comienza con la letra A.');
    }

    btnIniciar.addEventListener('click', iniciarCamara);
    btnDetener.addEventListener('click', () => detenerCamara(false));
    btnPausar.addEventListener('click', pausarCamara);
    btnEspejo.addEventListener('click', alternarEspejo);
    btnSinCamara.addEventListener('click', activarModoSinCamara);
    btnSiguiente.addEventListener('click', () => {
        if (completado || bloqueado) return;
        avanzarLetra(true);
    });
    btnReiniciar.addEventListener('click', reiniciarReto);
    btnCerrarModal.addEventListener('click', cerrarModal);
    btnReintentar.addEventListener('click', guardarProgreso);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (!modal || !modal.classList.contains('abierto')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            cerrarModal();
            return;
        }

        if (e.key !== 'Tab') return;

        const focusables = Array.from(
            modal.querySelectorAll('a[href], button:not([disabled])')
        ).filter((el) => !el.hidden && el.offsetParent !== null);

        if (!focusables.length) return;

        const primero = focusables[0];
        const ultimo = focusables[focusables.length - 1];

        if (e.shiftKey && document.activeElement === primero) {
            e.preventDefault();
            ultimo.focus();
        } else if (!e.shiftKey && document.activeElement === ultimo) {
            e.preventDefault();
            primero.focus();
        }
    });

    window.addEventListener('pagehide', () => detenerCamara(true));

    if (btnSinCamara) {
        btnSinCamara.setAttribute('aria-pressed', 'false');
    }

    actualizarObjetivo();
    actualizarProgreso();
    setScore(0);
    setFeedback('Activa la cámara o practica sin cámara.', '');
    setDetalle('Activa la cámara para comenzar. También puedes usar el modo sin cámara.');
    mostrarPlaceholder(
        'Activa la cámara',
        'Coloca una mano completa dentro del encuadre, con buena luz y el fondo despejado.'
    );
})();
</script>
<script src="../js/seva-ui.js"></script>
<script>
if (window.SEVA) {
    SEVA.init(<?= $usuario_id ?>);
    SEVA.menu();
}
</script>
</body>
</html>
