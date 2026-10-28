<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/*
 * Contenido de ejemplo: "Saludos y presentación".
 * La tabla `contenidos_educativos` todavía no tiene lecciones cargadas,
 * así que esta lección sirve como plantilla visual y estructural.
 * Cuando existan filas reales, el bloque try/catch de abajo las toma
 * de la base de datos y $es_contenido_ejemplo pasa a false.
 */
$leccion = [
    'titulo'      => 'Saludos y presentación',
    'nivel'       => 'Principiante',
    'duracion'    => '8 min',
    'descripcion' => 'Aprende a saludar, presentarte y despedirte en Lengua de Señas: la base de cualquier conversación.',
    'objetivos'   => [
        'Saludar y responder a un saludo',
        'Presentarte usando tu nombre',
        'Preguntar "¿cómo estás?" y despedirte',
    ],
    'vocabulario' => [
        ['id' => 'hola',    'palabra' => 'Hola',          'descripcion' => 'Con la palma abierta hacia el frente, mueve la mano de lado a lado cerca de la sien.'],
        ['id' => 'buenos',  'palabra' => 'Buenos días',   'descripcion' => 'Junta las yemas de los dedos de una mano y sube el brazo desde el pecho hacia arriba, como el sol naciendo.'],
        ['id' => 'gusto',   'palabra' => 'Mucho gusto',   'descripcion' => 'Con los pulgares hacia arriba, toca primero tu pecho y luego extiende la mano hacia la otra persona.'],
        ['id' => 'como',    'palabra' => '¿Cómo estás?',  'descripcion' => 'Con los pulgares hacia arriba, mueve ambas manos alternadamente hacia adelante y levanta las cejas al preguntar.'],
        ['id' => 'nombre',  'palabra' => 'Mi nombre es',  'descripcion' => 'Toca dos veces los dedos índice y medio de ambas manos entre sí y luego deletrea tu nombre letra por letra.'],
        ['id' => 'adios',   'palabra' => 'Adiós',         'descripcion' => 'Abre y cierra los dedos de la mano con la palma hacia la otra persona, como diciendo adiós.'],
    ],
];

$es_contenido_ejemplo = true;
$tipo_leccion = 'demo';
$img_leccion  = null;

/* Cargar contenido real (imágenes + descripciones) desde /data */
function cargar_json(string $archivo): array {
    $ruta = __DIR__ . '/data/' . $archivo;
    if (!is_file($ruta)) return [];
    $d = json_decode(file_get_contents($ruta), true);
    return is_array($d) ? $d : [];
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT titulo, ruta_imagen FROM contenidos_educativos WHERE id = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        if ($fila) {
            $leccion['titulo'] = $fila['titulo'];
            $es_contenido_ejemplo = false;
        }
    }
} catch (PDOException $e) {
    error_log('Lección - error de base de datos: ' . $e->getMessage());
    // Si la consulta falla, seguimos mostrando el contenido de ejemplo.
}

/* Lecciones con contenido real */
if ($id === 1) {
    $alf = cargar_json('alfabeto.json');
    if ($alf) {
        $leccion['titulo']      = 'Alfabeto dactilológico';
        $leccion['nivel']       = 'Básico';
        $leccion['duracion']    = '15 min';
        $leccion['descripcion'] = 'Aprende a deletrear cada letra del abecedario con las manos (A–Z y Ñ).';
        $leccion['objetivos']   = ['Reconocer las 27 letras del abecedario en señas', 'Distinguir las letras que llevan movimiento (G, H, J, Ñ, S, Z)', 'Deletrear tu nombre con las manos'];
        $leccion['vocabulario'] = array_map(fn($a) => [
            'id' => 'letra_' . $a['letra'], 'palabra' => $a['letra'], 'descripcion' => $a['descripcion'],
            'imagen' => $a['archivo'], 'movimiento' => !empty($a['con_movimiento']),
            'extra' => $a['instruccion_movimiento'] ?? ''
        ], $alf);
        $es_contenido_ejemplo = false; $tipo_leccion = 'real';
    }
} elseif ($id === 2) {
    $gui = cargar_json('guia.json');
    if ($gui) {
        $leccion['titulo']      = 'Saludos y presentaciones';
        $leccion['nivel']       = 'Básico';
        $leccion['duracion']    = '12 min';
        $leccion['descripcion'] = 'Hola, buenos días, gracias, por favor y más: la base de cualquier conversación.';
        $leccion['objetivos']   = ['Saludar y despedirte', 'Presentarte (nombre, apellido, de dónde eres)', 'Usar expresiones de cortesía'];
        $leccion['vocabulario'] = array_map(fn($g) => [
            'id' => preg_replace('/[^a-z0-9]+/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $g['palabra']))),
            'palabra' => $g['palabra'], 'descripcion' => $g['descripcion'],
            'imagen' => $g['archivo'], 'movimiento' => !empty($g['con_movimiento']), 'extra' => ''
        ], $gui);
        $es_contenido_ejemplo = false; $tipo_leccion = 'real';
    }
}

/* ¿Ya completó esta lección? */
$ya_completada = false;
if ($id > 0) {
    try {
        $st = $pdo->prepare('SELECT completado FROM progreso_usuario WHERE usuario_id = ? AND contenido_id = ?');
        $st->execute([$usuario_id, $id]);
        $ya_completada = (bool) ($st->fetch()['completado'] ?? false);
    } catch (PDOException $e) { error_log($e->getMessage()); }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($leccion['titulo']) ?> · Señas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root{
        --ink:#161B33;
        --paper:#FFFFFF;
        --bg:#FAF9FF;
        --coral:#FF6B54;
        --coral-dark:#E5543F;
        --gold:#FFB84D;
        --teal:#3FC1B0;
        --text:#1B1F3B;
        --muted:#7A80A0;
        --line:#E9E6F5;
        --danger:#D6465B;
        --danger-bg:#FDECEF;
    }
    *{ box-sizing:border-box; }
    body{
        margin:0;
        font-family:'Inter', sans-serif;
        color:var(--text);
        background:var(--bg);
    }
    :focus-visible{
        outline:3px solid var(--gold);
        outline-offset:2px;
    }
    .visually-hidden{
        position:absolute; width:1px; height:1px; overflow:hidden;
        clip:rect(0,0,0,0); white-space:nowrap;
    }
    .skip-link{
        position:absolute;
        left:1rem; top:-3rem;
        background:var(--coral);
        color:#fff;
        padding:0.7rem 1.1rem;
        border-radius:0.6rem;
        z-index:200;
        font-weight:700;
        text-decoration:none;
        transition:top 0.15s ease;
    }
    .skip-link:focus{ top:1rem; }

    /* ---------- Topbar (igual al panel de aprendizaje) ---------- */
    .topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:1.1rem 2rem;
        background:var(--ink);
        color:#fff;
    }
    .marca{
        display:flex;
        align-items:center;
        gap:0.6rem;
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:1.2rem;
        text-decoration:none;
        color:#fff;
    }
    .marca .punto{
        width:9px;height:9px;border-radius:50%;
        background:var(--coral);
        display:inline-block;
    }
    .topbar-derecha{
        display:flex;
        align-items:center;
        gap:1.4rem;
        font-size:0.95rem;
    }
    .topbar-derecha .saludo{ color:#C7C9E6; }
    .topbar-derecha .saludo strong{ color:#fff; }
    .topbar-derecha a{
        color:#fff;
        text-decoration:none;
        font-weight:600;
        padding:0.45rem 0.9rem;
        border:1.5px solid rgba(255,255,255,0.3);
        border-radius:0.6rem;
        transition:background 0.15s ease;
    }
    .topbar-derecha a:hover{ background:rgba(255,255,255,0.1); }

    .menu-opciones{ position:relative; }
    .btn-hamburguesa{
        display:flex; align-items:center; justify-content:center;
        width:40px; height:40px; padding:0;
        background:transparent;
        border:1.5px solid rgba(255,255,255,0.3);
        border-radius:0.6rem;
        color:#fff;
        cursor:pointer;
        transition:background 0.15s ease;
    }
    .btn-hamburguesa:hover{ background:rgba(255,255,255,0.1); }
    .panel-opciones{
        position:absolute;
        top:calc(100% + 0.6rem);
        right:0;
        width:250px;
        background:var(--paper);
        color:var(--text);
        border:1px solid var(--line);
        border-radius:0.9rem;
        box-shadow:0 16px 32px -14px rgba(22,27,51,0.3);
        padding:0.6rem;
        display:none;
        z-index:80;
    }
    .panel-opciones.abierto{ display:block; }
    .panel-opciones .encabezado-opciones{
        padding:0.5rem 0.6rem 0.7rem;
        border-bottom:1px solid var(--line);
        margin-bottom:0.4rem;
    }
    .panel-opciones .nombre-usuario{ display:block; font-family:'Baloo 2', sans-serif; font-weight:700; font-size:0.95rem; color:var(--ink); }
    .panel-opciones .correo-usuario{ display:block; font-size:0.78rem; color:var(--muted); margin-top:0.1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .panel-opciones .opcion{
        display:flex; align-items:center; gap:0.7rem;
        width:100%; padding:0.6rem 0.6rem; border:none; background:none;
        border-radius:0.6rem; font-family:'Inter', sans-serif; font-size:0.9rem; font-weight:600;
        color:var(--text); text-decoration:none; cursor:pointer; text-align:left;
    }
    .panel-opciones .opcion svg{ flex-shrink:0; color:var(--muted); }
    .panel-opciones .opcion:hover{ background:var(--bg); }
    .panel-opciones .separador-opciones{ border:none; border-top:1px solid var(--line); margin:0.4rem 0.2rem; }
    .panel-opciones .opcion.salir{ color:var(--danger); }
    .panel-opciones .opcion.salir svg{ color:var(--danger); }
    .panel-opciones .opcion.salir:hover{ background:var(--danger-bg); }

    /* ---------- Contenido ---------- */
    .contenedor{
        max-width:920px;
        margin:0 auto;
        padding:2rem 1.5rem 4rem;
    }

    .migas{
        display:flex;
        align-items:center;
        gap:0.4rem;
        font-size:0.88rem;
        color:var(--muted);
        margin-bottom:1.2rem;
    }
    .migas a{ color:var(--muted); text-decoration:none; font-weight:600; }
    .migas a:hover{ color:var(--coral-dark); text-decoration:underline; }

    .aviso-ejemplo{
        display:flex;
        align-items:flex-start;
        gap:0.6rem;
        background:#FFF6EA;
        border:1px solid #FCE3B8;
        color:#8A5A12;
        border-radius:0.8rem;
        padding:0.8rem 1rem;
        font-size:0.85rem;
        line-height:1.5;
        margin-bottom:1.6rem;
    }
    .aviso-ejemplo svg{ flex-shrink:0; margin-top:0.1rem; }

    .encabezado-leccion{ margin-bottom:1.8rem; }
    .fila-insignias{
        display:flex;
        flex-wrap:wrap;
        gap:0.5rem;
        margin-bottom:0.8rem;
    }
    .insignia-nivel, .insignia-duracion{
        font-family:'Space Mono', monospace;
        font-size:0.72rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.05em;
        padding:0.3rem 0.65rem;
        border-radius:0.4rem;
    }
    .insignia-nivel{ background:var(--teal); color:#0F1329; }
    .insignia-duracion{ background:var(--line); color:var(--ink); }

    .encabezado-leccion h1{
        font-family:'Baloo 2', sans-serif;
        font-size:2rem;
        margin:0 0 0.6rem;
        color:var(--ink);
    }
    .encabezado-leccion p.descripcion{
        color:var(--muted);
        font-size:1.02rem;
        line-height:1.6;
        max-width:640px;
        margin:0;
    }

    .tarjeta-objetivos{
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:1rem;
        padding:1.3rem 1.5rem;
        margin:1.6rem 0 2.2rem;
    }
    .tarjeta-objetivos h2{
        font-family:'Baloo 2', sans-serif;
        font-size:1.02rem;
        margin:0 0 0.8rem;
        color:var(--ink);
    }
    .lista-objetivos{
        list-style:none;
        margin:0;
        padding:0;
        display:flex;
        flex-direction:column;
        gap:0.55rem;
    }
    .lista-objetivos li{
        display:flex;
        align-items:flex-start;
        gap:0.6rem;
        font-size:0.92rem;
        line-height:1.4;
    }
    .lista-objetivos svg{ flex-shrink:0; color:var(--coral); margin-top:0.15rem; }

    /* ---------- Escenario de video ---------- */
    .seccion-video h2, .seccion-vocabulario h2, .seccion-pasos h2{
        font-family:'Baloo 2', sans-serif;
        font-size:1.15rem;
        color:var(--ink);
        margin:0 0 1rem;
    }

    .escenario-video{
        position:relative;
        background:#0F1329;
        border:2px solid var(--teal);
        border-radius:1rem;
        aspect-ratio:16/9;
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        margin-bottom:0;
    }
    .escenario-video::before{
        content:"";
        position:absolute;
        inset:0;
        background-image:radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size:22px 22px;
    }
    .marcador-lse{
        position:absolute;
        top:0.7rem; left:0.7rem;
        background:var(--coral);
        color:#fff;
        font-weight:700;
        font-size:0.75rem;
        padding:0.3rem 0.7rem;
        border-radius:0.5rem;
        z-index:2;
    }
    .contenido-video-vacio{
        position:relative;
        z-index:1;
        text-align:center;
        color:#C7C9E6;
        padding:1.5rem;
    }
    .contenido-video-vacio .circulo-icono{
        width:64px; height:64px;
        margin:0 auto 0.9rem;
        border-radius:50%;
        background:rgba(255,255,255,0.08);
        border:1.5px solid rgba(255,255,255,0.2);
        display:flex;
        align-items:center;
        justify-content:center;
    }
    .contenido-video-vacio strong{
        display:block;
        font-family:'Baloo 2', sans-serif;
        color:#fff;
        font-size:1.05rem;
        margin-bottom:0.3rem;
    }
    .contenido-video-vacio p{
        margin:0;
        font-size:0.85rem;
        max-width:320px;
        margin:0 auto;
        line-height:1.5;
    }
    .subtitulos-demo{
        position:absolute;
        bottom:0.9rem;
        left:0.9rem;
        right:0.9rem;
        background:rgba(0,0,0,0.55);
        color:#fff;
        text-align:center;
        padding:0.5rem 0.7rem;
        border-radius:0.5rem;
        font-size:0.85rem;
        z-index:2;
    }

    .barra-controles-video{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:0.8rem;
        background:var(--paper);
        border:1px solid var(--line);
        border-top:none;
        border-radius:0 0 1rem 1rem;
        padding:0.8rem 1.1rem;
        margin-bottom:2.2rem;
        flex-wrap:wrap;
    }
    .btn-transcripcion{
        display:inline-flex;
        align-items:center;
        gap:0.45rem;
        background:none;
        border:1.5px solid var(--line);
        border-radius:0.6rem;
        padding:0.5rem 0.85rem;
        font-family:'Inter', sans-serif;
        font-weight:600;
        font-size:0.85rem;
        color:var(--ink);
        cursor:pointer;
        transition:border-color 0.15s ease, background 0.15s ease;
    }
    .btn-transcripcion:hover{ border-color:var(--coral); background:#FFF3F1; }
    .nota-video{
        font-size:0.8rem;
        color:var(--muted);
    }

    .panel-transcripcion{
        display:none;
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:1rem;
        padding:1.3rem 1.5rem;
        margin:-1.4rem 0 2.2rem;
        font-size:0.9rem;
        line-height:1.7;
        color:var(--text);
    }
    .panel-transcripcion.abierto{ display:block; }
    .panel-transcripcion h3{
        font-family:'Baloo 2', sans-serif;
        font-size:0.95rem;
        margin:0 0 0.6rem;
        color:var(--ink);
    }
    .panel-transcripcion ol{ margin:0; padding-left:1.2rem; }
    .panel-transcripcion li{ margin-bottom:0.4rem; }

    /* ---------- Vocabulario ---------- */
    .seccion-vocabulario{ margin-bottom:2.4rem; }
    .fila-progreso-vocab{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:1rem;
    }
    .cifra-progreso-vocab{
        font-family:'Space Mono', monospace;
        font-weight:700;
        font-size:0.85rem;
        color:var(--coral-dark);
        background:#FFF1EE;
        padding:0.3rem 0.7rem;
        border-radius:0.5rem;
    }

    .rejilla-vocabulario{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
        gap:1.1rem;
    }
    .tarjeta-palabra{
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:1rem;
        padding:1.1rem 1.2rem;
        transition:border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .tarjeta-palabra:has(.check-practicado:checked){
        border-color:var(--teal);
        box-shadow:0 0 0 3px rgba(63,193,176,0.14);
    }
    .tarjeta-palabra .palabra{
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:1.1rem;
        color:var(--ink);
        margin:0 0 0.4rem;
    }
    .tarjeta-palabra .descripcion-seña{
        font-size:0.85rem;
        color:var(--muted);
        line-height:1.55;
        margin:0 0 0.9rem;
    }
    .etiqueta-practicado{
        display:flex;
        align-items:center;
        gap:0.5rem;
        font-size:0.85rem;
        font-weight:600;
        color:var(--text);
        cursor:pointer;
    }
    .etiqueta-practicado input[type="checkbox"]{
        width:18px; height:18px;
        accent-color:var(--teal);
        cursor:pointer;
    }

    .nota-variacion{
        display:flex;
        gap:0.6rem;
        align-items:flex-start;
        background:var(--bg);
        border:1px dashed var(--line);
        border-radius:0.8rem;
        padding:0.9rem 1rem;
        font-size:0.82rem;
        color:var(--muted);
        line-height:1.55;
        margin-top:1.1rem;
    }
    .nota-variacion svg{ flex-shrink:0; color:var(--coral); margin-top:0.1rem; }

    /* ---------- Pasos de práctica ---------- */
    .seccion-pasos{ margin-bottom:2.6rem; }
    .lista-pasos{
        list-style:none;
        margin:0;
        padding:0;
        display:flex;
        flex-direction:column;
        gap:0.9rem;
    }
    .lista-pasos li{
        display:flex;
        gap:1rem;
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:0.9rem;
        padding:1rem 1.2rem;
    }
    .lista-pasos .num-paso{
        width:34px; height:34px;
        flex-shrink:0;
        border-radius:50%;
        background:var(--coral);
        color:#fff;
        font-family:'Space Mono', monospace;
        font-weight:700;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:0.9rem;
    }
    .lista-pasos strong{
        display:block;
        font-family:'Baloo 2', sans-serif;
        font-size:0.98rem;
        color:var(--ink);
        margin-bottom:0.2rem;
    }
    .lista-pasos span{
        font-size:0.88rem;
        color:var(--muted);
        line-height:1.55;
    }

    /* ---------- Pie de lección ---------- */
    .pie-leccion{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1rem;
        flex-wrap:wrap;
        border-top:1px solid var(--line);
        padding-top:1.6rem;
    }
    .enlace-volver{
        display:inline-flex;
        align-items:center;
        gap:0.4rem;
        color:var(--ink);
        font-weight:600;
        font-size:0.92rem;
        text-decoration:none;
    }
    .enlace-volver:hover{ color:var(--coral-dark); }

    .btn-completar{
        display:inline-flex;
        align-items:center;
        gap:0.55rem;
        background:var(--teal);
        color:#0F1329;
        border:none;
        font-weight:700;
        font-family:'Inter', sans-serif;
        font-size:0.95rem;
        padding:0.8rem 1.4rem;
        border-radius:0.7rem;
        cursor:pointer;
        transition:background 0.15s ease, transform 0.1s ease;
    }
    .btn-completar:hover{ background:#33ab9b; }
    .btn-completar:active{ transform:translateY(1px); }
    .btn-completar.completada{ background:var(--line); color:var(--muted); cursor:default; }
    .btn-completar.completada:hover{ background:var(--line); }

    [role="status"]{ }

    @media (prefers-reduced-motion: reduce){
        *{ scroll-behavior:auto !important; }
    }

    @media (max-width: 560px){
        .topbar{ padding:1rem 1.2rem; flex-wrap:wrap; gap:0.6rem; }
        .topbar-derecha{ gap:0.7rem; }
        .encabezado-leccion h1{ font-size:1.6rem; }
        .pie-leccion{ flex-direction:column; align-items:stretch; }
        .btn-completar{ justify-content:center; }
    }
    .img-sena{width:100%;max-width:220px;height:auto;display:block;margin:0 auto 10px;border-radius:10px;background:#f4f8ff;border:1px solid #e4eaf3}
    .chip-mov{display:inline-block;font-size:12px;font-weight:600;color:#1e7e34;background:#e6f6ea;border-radius:99px;padding:2px 8px;margin-left:6px;vertical-align:middle}
    .btn-practica{display:inline-flex;align-items:center;gap:8px;margin-top:14px;padding:10px 18px;border-radius:10px;background:#28a745;color:#fff;font-weight:700;text-decoration:none}
    .btn-practica:hover{background:#1e7e34;color:#fff}
</style>
</head>
<body>
<a href="#contenido-leccion" class="skip-link">Saltar al contenido</a>

<header class="topbar">
    <a href="dashboard.php" class="marca"><span class="punto"></span>Lección</a>
    <div class="topbar-derecha">
        <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre) ?></strong></span>

        <div class="menu-opciones">
            <button type="button" class="btn-hamburguesa" id="btn-opciones" aria-label="Abrir menú de opciones" aria-haspopup="true" aria-expanded="false" aria-controls="panel-opciones">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <div class="panel-opciones" id="panel-opciones" role="menu">
                <div class="encabezado-opciones">
                    <span class="nombre-usuario"><?= htmlspecialchars($nombre) ?></span>
                    <span class="correo-usuario"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                </div>
                <a href="perfil.php" class="opcion" role="menuitem">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                    Mi perfil
                </a>
                <a href="configuracion.php" class="opcion" role="menuitem">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Configuración de cuenta
                </a>
                <a href="#" class="opcion" role="menuitem">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>
                    Ayuda y soporte
                </a>
                <hr class="separador-opciones">
                <a href="login.php?salir=1" class="opcion salir" role="menuitem">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Cerrar sesión
                </a>
            </div>
        </div>

        <a href="Dashboard.php?salir=1">Salir</a>
    </div>
</header>

<div class="contenedor" id="contenido-leccion">

    <p class="migas">
        <a href="dashboard.php">Mis lecciones</a>
        <span aria-hidden="true">›</span>
        <span><?= htmlspecialchars($leccion['titulo']) ?></span>
    </p>

    <?php if ($es_contenido_ejemplo): ?>
        <div class="aviso-ejemplo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Esta es una lección de ejemplo para mostrar cómo se verá la estructura de contenidos. El video real todavía no se ha cargado en la base de datos.</span>
        </div>
    <?php endif; ?>

    <section class="encabezado-leccion">
        <div class="fila-insignias">
            <span class="insignia-nivel"><?= htmlspecialchars($leccion['nivel']) ?></span>
            <span class="insignia-duracion">⏱ <?= htmlspecialchars($leccion['duracion']) ?></span>
        </div>
        <h1><?= htmlspecialchars($leccion['titulo']) ?></h1>
        <p class="descripcion"><?= htmlspecialchars($leccion['descripcion']) ?></p>
    </section>

    <div class="tarjeta-objetivos">
        <h2>Al terminar esta lección podrás:</h2>
        <ul class="lista-objetivos">
            <?php foreach ($leccion['objetivos'] as $objetivo): ?>
            <li>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                <span><?= htmlspecialchars($objetivo) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($tipo_leccion === 'demo'): ?>
<section class="seccion-video">
        <h2>Video de la lección</h2>
        <div class="escenario-video">
            <span class="marcador-lse">LSE</span>
            <div class="contenido-video-vacio">
                <div class="circulo-icono">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                </div>
                <strong>Video próximamente disponible</strong>
                <p>Aquí se reproducirá la demostración en Lengua de Señas. Mientras tanto, usa la transcripción y el vocabulario de abajo para practicar.</p>
            </div>
            <p class="subtitulos-demo">"Hola, mucho gusto" — así se ve la seña que vas a aprender</p>
        </div>
        <div class="barra-controles-video">
            <button type="button" class="btn-transcripcion" id="btn-transcripcion" aria-expanded="false" aria-controls="panel-transcripcion">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                Ver transcripción
            </button>
            <span class="nota-video">Incluye transcripción de texto para que la lección sea accesible sin depender del audio.</span>
        </div>

        <div class="panel-transcripcion" id="panel-transcripcion">
            <h3>Transcripción de la demostración</h3>
            <ol>
                <li>La instructora saluda con la palma abierta, moviéndola de lado a lado (HOLA).</li>
                <li>Sube el brazo desde el pecho con las yemas juntas, sonriendo (BUENOS DÍAS).</li>
                <li>Se señala el pecho y extiende la mano hacia adelante con los pulgares arriba (MUCHO GUSTO).</li>
                <li>Levanta las cejas y mueve ambos puños alternadamente hacia adelante (¿CÓMO ESTÁS?).</li>
                <li>Toca los dedos índice y medio dos veces y deletrea su nombre (MI NOMBRE ES...).</li>
                <li>Cierra la lección abriendo y cerrando los dedos con la palma hacia el frente (ADIÓS).</li>
            </ol>
        </div>
    </section>
    <?php endif; ?>

    <section class="seccion-vocabulario">
        <div class="fila-progreso-vocab">
            <h2 style="margin:0;">Vocabulario de la lección</h2>
            <span class="cifra-progreso-vocab"><span id="contador-practicadas">0</span>/<?= count($leccion['vocabulario']) ?> practicadas</span>
        </div>

        <div class="rejilla-vocabulario">
            <?php foreach ($leccion['vocabulario'] as $item): ?>
            <div class="tarjeta-palabra">
                <?php if (!empty($item['imagen'])): ?>
                <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="Seña: <?= htmlspecialchars($item['palabra']) ?>" class="img-sena" loading="lazy">
                <?php endif; ?>
                <p class="palabra"><?= htmlspecialchars($item['palabra']) ?><?php if (!empty($item['movimiento'])): ?> <span class="chip-mov" title="Esta seña lleva movimiento">↻ movimiento</span><?php endif; ?></p>
                <p class="descripcion-seña"><?= htmlspecialchars($item['descripcion']) ?><?php if (!empty($item['extra'])): ?><br><em><?= htmlspecialchars($item['extra']) ?></em><?php endif; ?></p>
                <label class="etiqueta-practicado">
                    <input type="checkbox" class="check-practicado" data-palabra="<?= htmlspecialchars($item['id']) ?>">
                    Ya practiqué esta seña
                </label>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="nota-variacion">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>El movimiento exacto de cada seña puede variar según la región. Sigue siempre la referencia del video o de tu instructor una vez esté disponible.</span>
        </div>
    </section>

    <?php if ($tipo_leccion === 'real'): ?>
    <p><a class="btn-practica" href="practica.php?modo=<?= $id === 1 ? 'alfabeto' : 'quiz' ?>">🎮 Practicar con juegos</a></p>
    <?php endif; ?>

    <section class="seccion-pasos">
        <h2>Cómo practicar</h2>
        <ol class="lista-pasos">
            <li>
                <span class="num-paso">1</span>
                <div><strong>Observa</strong><span>Mira la seña completa una vez, prestando atención a la forma de la mano y a la expresión facial.</span></div>
            </li>
            <li>
                <span class="num-paso">2</span>
                <div><strong>Imita</strong><span>Repite el mismo movimiento frente a un espejo o tu cámara, despacio y sin prisa.</span></div>
            </li>
            <li>
                <span class="num-paso">3</span>
                <div><strong>Repite</strong><span>Practica cada seña tres veces seguidas para afianzar la memoria muscular.</span></div>
            </li>
            <li>
                <span class="num-paso">4</span>
                <div><strong>Verifica</strong><span>Marca cada palabra como practicada arriba y repásala más tarde en "Palabras repasadas".</span></div>
            </li>
        </ol>
    </section>

    <div class="pie-leccion">
        <a href="dashboard.php" class="enlace-volver">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M5 12h14"/></svg>
            Volver a mis lecciones
        </a>
        <button type="button" class="btn-completar" id="btn-completar">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span id="texto-completar">Marcar lección como completada</span>
        </button>
    </div>

    <p role="status" class="visually-hidden" id="anuncio-estado"></p>
</div>

<script>
    // Menú hamburguesa (idéntico en comportamiento al del panel de aprendizaje)
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
            }
        });
    }

    // Transcripción del video (accesibilidad: contenido sin depender del video)
    const btnTranscripcion = document.getElementById('btn-transcripcion');
    const panelTranscripcion = document.getElementById('panel-transcripcion');
    if (btnTranscripcion) btnTranscripcion.addEventListener('click', function () {
        const abierto = panelTranscripcion.classList.toggle('abierto');
        btnTranscripcion.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        btnTranscripcion.lastChild.textContent = abierto ? ' Ocultar transcripción' : ' Ver transcripción';
    });

    // Progreso de vocabulario practicado (se guarda en este dispositivo)
    const ID_LECCION = <?= (int) $id ?>;
    const LLAVE_PROGRESO = 'seva_leccion_' + ID_LECCION + '_practicadas';
    const casillas = document.querySelectorAll('.check-practicado');
    const contadorPracticadas = document.getElementById('contador-practicadas');
    const anuncioEstado = document.getElementById('anuncio-estado');

    function cargarProgresoVocabulario() {
        let guardadas = [];
        try {
            guardadas = JSON.parse(localStorage.getItem(LLAVE_PROGRESO) || '[]');
        } catch (e) {
            guardadas = [];
        }
        casillas.forEach(function (casilla) {
            if (guardadas.includes(casilla.dataset.palabra)) {
                casilla.checked = true;
            }
        });
        actualizarContador();
    }

    function guardarProgresoVocabulario() {
        const marcadas = Array.from(casillas).filter(c => c.checked).map(c => c.dataset.palabra);
        localStorage.setItem(LLAVE_PROGRESO, JSON.stringify(marcadas));
    }

    function actualizarContador() {
        const total = casillas.length;
        const marcadas = Array.from(casillas).filter(c => c.checked).length;
        contadorPracticadas.textContent = marcadas;
        if (marcadas === total) {
            anuncioEstado.textContent = 'Practicaste todas las señas de esta lección.';
        }
    }

    casillas.forEach(function (casilla) {
        casilla.addEventListener('change', function () {
            guardarProgresoVocabulario();
            actualizarContador();
        });
    });
    cargarProgresoVocabulario();

    // Marcar lección como completada (visual, guardado en este dispositivo;
    // cuando exista la tabla de progreso definitiva, aquí se llamaría a un
    // endpoint que actualice `progreso_usuario` para este usuario y lección).
    const LLAVE_COMPLETADA = 'seva_leccion_' + ID_LECCION + '_completada';
    const YA_COMPLETADA = <?= $ya_completada ? 'true' : 'false' ?>;
    const btnCompletar = document.getElementById('btn-completar');
    const textoCompletar = document.getElementById('texto-completar');

    function marcarComoCompletada() {
        btnCompletar.classList.add('completada');
        btnCompletar.disabled = true;
        textoCompletar.textContent = 'Lección completada';
        anuncioEstado.textContent = 'Marcaste la lección como completada.';
    }

    if (YA_COMPLETADA || localStorage.getItem(LLAVE_COMPLETADA) === '1') {
        marcarComoCompletada();
    }

    btnCompletar.addEventListener('click', function () {
        localStorage.setItem(LLAVE_COMPLETADA, '1');
        marcarComoCompletada();
        if (ID_LECCION > 0) {
            fetch('guardar_progreso.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'contenido_id=' + ID_LECCION
            }).catch(function () {});
        }
    });
</script>
</body>
</html>
