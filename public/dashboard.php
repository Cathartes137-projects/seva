<?php
require '../config/conexion.php';
require '../config/seguridad.php';

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
    // Consulta de lecciones (se conserva el orden pedagógico)
    $stmt_lec = $pdo->query('SELECT id, titulo, descripcion, ruta_imagen, ruta_video, nivel, orden FROM contenidos_educativos ORDER BY orden ASC, id ASC');
    $lecciones = $stmt_lec->fetchAll();
    $total = count($lecciones);

    $stmt_comp = $pdo->prepare('SELECT COUNT(*) AS c FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_comp->execute([$usuario_id]);
    $completadas = (int) $stmt_comp->fetch()['c'];

    $porcentaje = $total > 0 ? (int) round(($completadas / $total) * 100) : 0;

    $stmt_ids = $pdo->prepare('SELECT contenido_id FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_ids->execute([$usuario_id]);
    $completados_ids = array_column($stmt_ids->fetchAll(), 'contenido_id');
} catch (PDOException $e) {
    error_log('Dashboard - error de base de datos: ' . $e->getMessage());
    $error_bd = 'No se pudieron cargar las lecciones. Verifica la conexión a la base de datos de SEVA.';
}

$completados_ids = array_map('intval', $completados_ids);

// Primera lección no completada = siguiente paso sugerido.
$siguiente_id = null;
foreach ($lecciones as $l) {
    if (!in_array((int) $l['id'], $completados_ids, true)) {
        $siguiente_id = (int) $l['id'];
        break;
    }
}

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
    $hero_subtitulo = 'Revisa la conexión con la base de datos para continuar tu aprendizaje.';
} elseif ($total === 0) {
    $hero_subtitulo = 'Todavía no hay lecciones publicadas. Cuando se agreguen, aparecerán aquí.';
} elseif ($siguiente_id !== null) {
    $hero_subtitulo = 'Tu siguiente paso es «' . $siguiente_titulo . '». Avanza a tu ritmo y practica las veces que necesites.';
} else {
    $hero_subtitulo = '¡Completaste todas las lecciones! Repásalas cuando quieras y sigue practicando con los juegos.';
}

$niveles = [
    'basico'     => 'Básico',
    'intermedio' => 'Intermedio',
    'avanzado'   => 'Avanzado',
];

/**
 * Devuelve una ruta de imagen segura y existente; si no, el marcador local.
 */
function seva_imagen_leccion(?string $ruta): string
{
    $ruta = ltrim(str_replace('\\', '/', (string) $ruta), '/');
    if ($ruta !== '' && strpos($ruta, '..') === false && is_file(__DIR__ . '/' . $ruta)) {
        return $ruta;
    }
    return 'img/lecciones/default.svg';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi aprendizaje · SEVA</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css">
<style>
    /* ---------- Cabecera de progreso ---------- */
    .dash-hero{
        position:relative; overflow:hidden;
        display:grid; grid-template-columns:1.4fr auto; gap:1.5rem; align-items:center;
        padding:2rem 2.1rem; border-radius:1.4rem; color:#F3F6FF;
        background:radial-gradient(circle at 88% 8%, #0A4A8C 0%, var(--ink) 62%, #021A33 100%);
        box-shadow:var(--sombra-card);
    }
    .dash-hero::before{
        content:""; position:absolute; inset:0; opacity:.45; pointer-events:none;
        background-image:radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px);
        background-size:24px 24px;
    }
    .dash-hero > *{ position:relative; }
    .dash-hero h1{ color:#fff; font-size:clamp(1.7rem,3.6vw,2.4rem); margin:0 0 .5rem; }
    .dash-hero p{ margin:0 0 1.2rem; color:#C9D6EC; max-width:52ch; line-height:1.55; }
    .dash-hero .btn{ min-height:44px; display:inline-flex; align-items:center; gap:.45rem; }
    .dash-ola{ display:inline-block; transform-origin:70% 80%; animation:ola-dash 1.6s ease-in-out .4s 2; }
    @keyframes ola-dash{
        0%,100%{ transform:rotate(0) }
        25%{ transform:rotate(18deg) }
        55%{ transform:rotate(-8deg) }
        75%{ transform:rotate(12deg) }
    }

    .dash-progreso{
        min-width:210px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18);
        border-radius:1rem; padding:1.1rem 1.2rem; backdrop-filter:blur(4px);
    }
    .dash-progreso .fila{ display:flex; align-items:baseline; justify-content:space-between; gap:1rem; margin-bottom:.55rem; }
    .dash-progreso .etq{ font:600 .82rem 'Inter',sans-serif; color:#C9D6EC; letter-spacing:.02em; text-transform:uppercase; }
    .dash-progreso strong{ font:800 1.9rem 'Baloo 2',sans-serif; color:#fff; line-height:1; }
    .dash-barra{ height:12px; border-radius:99px; background:rgba(255,255,255,.16); overflow:hidden; }
    .dash-barra span{
        display:block; height:100%; width:var(--pct,0%); border-radius:99px;
        background:linear-gradient(90deg,#34C759 0%,#7BE495 100%);
        transition:width 1.1s cubic-bezier(.2,.8,.2,1) .25s;
    }
    .dash-progreso small{ display:block; margin-top:.5rem; color:#C9D6EC; font-size:.8rem; }

    /* ---------- Banner de minijuegos ---------- */
    .dash-juegos{
        position:relative; overflow:hidden;
        display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1.5rem; align-items:center;
        margin-top:1.5rem; padding:1.4rem 1.6rem;
        border:2px solid #FFD9A8; border-radius:1.2rem;
        background:linear-gradient(120deg,#FFF3D6 0%,#FFE2BD 55%,#FFD6C9 100%);
        box-shadow:var(--sombra-card);
    }
    .dash-juegos::after{
        content:""; position:absolute; right:-40px; top:-60px; width:180px; height:180px;
        border-radius:50%; background:rgba(255,255,255,.35); pointer-events:none;
    }
    .dash-juegos > *{ position:relative; }
    .dash-juegos-etq{
        display:inline-block; margin-bottom:.15rem;
        font:700 .72rem 'Inter',sans-serif; letter-spacing:.08em;
        text-transform:uppercase; color:#8A4B00;
    }
    .dash-juegos h2{
        margin:0 0 .35rem; color:var(--ink);
        font:800 clamp(1.3rem,2.6vw,1.7rem) 'Baloo 2',sans-serif;
    }
    .dash-juegos p{ margin:0 0 1rem; max-width:58ch; color:var(--text); line-height:1.5; }
    .dash-juegos .btn{ min-height:44px; display:inline-flex; align-items:center; gap:.4rem; }
    .dash-juegos-emoji{
        font-size:clamp(3rem,6vw,4.5rem); line-height:1;
        filter:drop-shadow(0 8px 12px rgba(122,75,0,.2));
        animation:dash-flota 3.2s ease-in-out infinite;
    }
    @keyframes dash-flota{
        0%,100%{ transform:translateY(0) rotate(-4deg) }
        50%{ transform:translateY(-8px) rotate(4deg) }
    }
    @media (prefers-reduced-motion: reduce){
        .dash-juegos-emoji{ animation:none; }
    }

    /* ---------- Barra de herramientas ---------- */
    .dash-herr{ display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between; margin:2rem 0 1rem; }
    .dash-herr h2{ font-size:1.45rem; margin:0; }
    .dash-buscador{
        width:100%; max-width:320px; min-height:44px; padding:.65rem 1rem;
        border:2px solid var(--line); border-radius:99px; background:var(--paper);
        font:500 .92rem 'Inter',sans-serif; color:var(--text);
    }
    .dash-buscador:focus{ outline:none; border-color:var(--azul); box-shadow:0 0 0 4px rgba(0,86,179,.14); }
    .dash-filtros{ display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.3rem; }
    .dash-filtro{
        min-height:44px; padding:.5rem 1rem; border-radius:99px; cursor:pointer;
        border:2px solid var(--line); background:var(--paper); color:var(--text);
        font:700 .85rem 'Inter',sans-serif; transition:background .15s, border-color .15s, color .15s;
    }
    .dash-filtro:hover{ border-color:var(--azul); color:var(--azul); }
    .dash-filtro[aria-pressed="true"]{ background:var(--azul); border-color:var(--azul); color:#fff; }
    .dash-resultados{ margin:0 0 1rem; color:var(--muted); font-size:.88rem; }

    /* ---------- Grid de tarjetas ---------- */
    .dash-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.25rem; }
    .dash-card{
        display:flex; flex-direction:column; overflow:hidden; text-decoration:none; color:inherit;
        background:var(--paper); border:2px solid var(--line); border-radius:1rem;
        box-shadow:var(--sombra-card);
        transition:transform .2s var(--rebote), box-shadow .2s, border-color .15s;
    }
    .dash-card:hover{ transform:translateY(-4px); box-shadow:var(--sombra-hover); border-color:var(--azul); }
    .dash-card:focus-visible{ outline:3px solid var(--azul); outline-offset:3px; }
    .dash-card[hidden]{ display:none; }
    .dash-card.actual{ border-color:var(--azul); box-shadow:0 0 0 3px rgba(0,86,179,.12), var(--sombra-card); }
    .dash-card.hecha{ border-color:#BFE5C9; }

    .dash-media{ position:relative; aspect-ratio:16/10; background:var(--azul-cl); }
    .dash-media img{ width:100%; height:100%; object-fit:cover; display:block; }
    .dash-media .dash-etq{
        position:absolute; top:.65rem; left:.65rem;
        padding:.28rem .6rem; border-radius:99px; background:rgba(255,255,255,.94);
        color:var(--ink); font:700 .72rem 'Inter',sans-serif; letter-spacing:.02em;
        box-shadow:0 2px 6px rgba(4,41,77,.16);
    }
    .dash-media .dash-check{
        position:absolute; top:.65rem; right:.65rem; display:flex; align-items:center; gap:.25rem;
        padding:.28rem .55rem; border-radius:99px; background:var(--verde); color:#fff;
        font:700 .72rem 'Inter',sans-serif; box-shadow:0 2px 6px rgba(4,41,77,.18);
    }
    .dash-body{ display:flex; flex-direction:column; gap:.4rem; flex:1; padding:1rem 1.1rem 1.15rem; }
    .dash-body h3{ margin:0; font-size:1.08rem; }
    .dash-desc{
        margin:0; color:var(--muted); font-size:.86rem; line-height:1.45;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .dash-cta{
        margin-top:auto; padding-top:.55rem; display:inline-flex; align-items:center; gap:.35rem;
        color:var(--azul); font-weight:700; font-size:.9rem;
    }
    .dash-card.actual .dash-cta{ color:var(--azul-oscuro); }
    .dash-card.hecha .dash-cta{ color:var(--verde-oscuro); }
    .dash-card.actual .dash-cta::after{ content:"→"; transition:transform .15s; }
    .dash-card:hover .dash-cta::after{ transform:translateX(3px); }

    .dash-vacio{
        text-align:center; padding:2.4rem 1.2rem; border:2px dashed var(--line);
        border-radius:1rem; background:var(--paper); color:var(--muted);
    }
    .dash-vacio .gran{ display:block; font-size:2.4rem; margin-bottom:.4rem; }
    .dash-vacio h3{ margin:0 0 .3rem; color:var(--ink); }
    .dash-vacio p{ margin:0; }

    /* ---------- Recursos y herramientas ---------- */
    .dash-recursos{ margin-top:2.4rem; }
    .dash-recursos .dash-herr{ margin-top:0; }
    .dash-recursos-grid{
        display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.25rem;
    }
    .recurso-card{
        display:flex; align-items:flex-start; gap:1rem;
        padding:1.15rem 1.2rem; text-decoration:none; color:inherit;
        background:var(--paper); border:2px solid var(--line); border-left-width:5px;
        border-radius:1rem; box-shadow:var(--sombra-card);
        transition:transform .2s var(--rebote), box-shadow .2s, border-color .15s;
    }
    .recurso-card:hover{ transform:translateY(-3px); box-shadow:var(--sombra-hover); border-color:var(--azul); }
    .recurso-card:focus-visible{ outline:3px solid var(--azul); outline-offset:3px; }
    .recurso-card.recurso-camara{ border-left-color:var(--azul); }
    .recurso-card.recurso-juegos{ border-left-color:var(--sol); }
    .recurso-icono{
        flex:0 0 auto; display:grid; place-items:center;
        width:56px; height:56px; border-radius:.9rem; font-size:1.8rem; line-height:1;
        background:var(--azul-cl);
    }
    .recurso-juegos .recurso-icono{ background:var(--sol-cl); }
    .recurso-etq{
        display:block; margin-bottom:.15rem;
        font:700 .7rem 'Inter',sans-serif; letter-spacing:.07em;
        text-transform:uppercase; color:var(--azul);
    }
    .recurso-juegos .recurso-etq{ color:#8A4B00; }
    .recurso-texto h3{ margin:0 0 .25rem; font-size:1.05rem; }
    .recurso-texto p{ margin:0 0 .5rem; color:var(--muted); font-size:.86rem; line-height:1.45; }
    .recurso-cta{
        display:inline-flex; align-items:center; gap:.3rem;
        color:var(--azul); font-size:.88rem; font-weight:700;
    }
    .recurso-card:hover .recurso-cta{ color:var(--azul-oscuro); }

    @media (max-width:760px){
        .dash-hero{ grid-template-columns:1fr; padding:1.6rem 1.3rem; }
        .dash-progreso{ min-width:0; }
        .dash-herr{ align-items:flex-start; }
        .dash-buscador{ max-width:none; }
        .dash-juegos{ grid-template-columns:1fr; padding:1.25rem; }
        .dash-juegos-emoji{ display:none; }
        .dash-recursos-grid{ grid-template-columns:1fr; }
    }
</style>
</head>
<body>
<?php require __DIR__ . '/partials/topbar.php'; ?>

<main class="contenedor" id="contenido">

<?php if ($error_bd !== ''): ?>
    <div class="aviso-error" role="alert" style="background:var(--danger-bg); color:var(--danger); border-radius:1rem; padding:1.1rem 1.3rem; margin-bottom:1.4rem;">
        <strong style="display:block; font-family:'Baloo 2',sans-serif;">No pudimos cargar tus lecciones</strong>
        <?= htmlspecialchars($error_bd) ?>
    </div>
<?php endif; ?>

<?php if ($error_bd === ''): ?>
    <section class="dash-hero" aria-labelledby="titulo-progreso">
        <div>
            <h1 id="titulo-progreso">¡Hola, <?= htmlspecialchars($primer) ?>! <span class="dash-ola" aria-hidden="true">👋</span></h1>
            <p><?= htmlspecialchars($hero_subtitulo) ?></p>
            <?php if ($siguiente_id !== null): ?>
                <a class="btn btn-verde" href="leccion.php?id=<?= (int) $siguiente_id ?>">
                    <?= $completadas === 0 ? 'Empezar lección' : 'Continuar lección' ?>
                </a>
            <?php else: ?>
                <a class="btn btn-verde" href="practica.php">Repasar con juegos</a>
            <?php endif; ?>
        </div>

        <div class="dash-progreso" role="group" aria-labelledby="etq-progreso">
            <div class="fila">
                <span class="etq" id="etq-progreso">Progreso general</span>
                <strong><?= (int) $porcentaje ?>%</strong>
            </div>
            <div class="dash-barra" role="progressbar" aria-labelledby="etq-progreso"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $porcentaje ?>"
                 aria-valuetext="<?= (int) $completadas ?> de <?= (int) $total ?> lecciones completadas">
                <span style="--pct:<?= (int) $porcentaje ?>%"></span>
            </div>
            <small><?= (int) $completadas ?> de <?= (int) $total ?> lecciones completadas</small>
        </div>
    </section>

    <section class="dash-juegos" aria-labelledby="titulo-juegos">
        <div>
            <span class="dash-juegos-etq">Minijuegos</span>
            <h2 id="titulo-juegos">Practica jugando</h2>
            <p>Refuerza lo aprendido con retos rápidos de quiz, memoria y alfabeto. Cada partida suma XP y te ayuda a mantener la racha.</p>
            <a class="btn btn-primario" href="practica.php">Ir a los juegos <span aria-hidden="true">→</span></a>
        </div>
        <div class="dash-juegos-emoji" aria-hidden="true">🎮</div>
    </section>

    <?php if ($total > 0): ?>
        <div class="dash-herr">
            <h2>Mis lecciones</h2>
            <label class="visually-hidden" for="buscador">Buscar lección</label>
            <input type="search" id="buscador" class="dash-buscador" placeholder="🔎 Buscar lección…" autocomplete="off">
        </div>

        <div class="dash-filtros" role="group" aria-label="Filtrar lecciones por nivel">
            <button type="button" class="dash-filtro" data-nivel="todos" aria-pressed="true">Todas</button>
            <?php foreach ($niveles as $clave => $etq): ?>
                <button type="button" class="dash-filtro" data-nivel="<?= htmlspecialchars($clave) ?>" aria-pressed="false"><?= htmlspecialchars($etq) ?></button>
            <?php endforeach; ?>
        </div>

        <p class="dash-resultados" id="dash-resultados" aria-live="polite"><?= (int) $total ?> lecciones</p>

        <div class="dash-grid" id="dash-grid">
            <?php foreach ($lecciones as $l): ?>
                <?php
                $lid     = (int) $l['id'];
                $hecha   = in_array($lid, $completados_ids, true);
                $actual  = !$hecha && $siguiente_id === $lid;
                $slug    = strtolower((string) ($l['nivel'] ?? 'basico'));
                if (!isset($niveles[$slug])) {
                    $slug = 'basico';
                }
                $nivel_txt = $niveles[$slug];
                $imagen    = seva_imagen_leccion($l['ruta_imagen'] ?? '');
                $clases    = 'dash-card' . ($hecha ? ' hecha' : '') . ($actual ? ' actual' : '');
                $texto     = $l['titulo'] . ' ' . ($l['descripcion'] ?? '') . ' ' . $nivel_txt;
                ?>
                <a class="<?= $clases ?>" href="leccion.php?id=<?= $lid ?>"
                   data-nivel="<?= htmlspecialchars($slug) ?>"
                   data-texto="<?= htmlspecialchars($texto) ?>">
                    <div class="dash-media">
                        <img src="<?= htmlspecialchars($imagen) ?>" alt="Ilustración de la lección <?= htmlspecialchars($l['titulo']) ?>" loading="lazy" decoding="async">
                        <span class="dash-etq"><?= htmlspecialchars($nivel_txt) ?></span>
                        <?php if ($hecha): ?>
                            <span class="dash-check">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                Completada
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="dash-body">
                        <h3><?= htmlspecialchars($l['titulo']) ?></h3>
                        <?php if (!empty($l['descripcion'])): ?>
                            <p class="dash-desc"><?= htmlspecialchars($l['descripcion']) ?></p>
                        <?php endif; ?>
                        <span class="dash-cta">
                            <?= $hecha ? 'Repasar lección' : ($actual ? ($completadas === 0 ? 'Empezar' : 'Continuar') : 'Ver lección') ?>
                            <?php if (!$actual): ?>
                                <span aria-hidden="true">→</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dash-vacio" id="dash-vacio" hidden>
            <span class="gran" aria-hidden="true">🔍</span>
            <h3>No encontramos esa lección</h3>
            <p>Prueba con otra palabra o cambia el nivel.</p>
        </div>
    <?php else: ?>
        <div class="dash-vacio">
            <span class="gran" aria-hidden="true">📚</span>
            <h3>Todavía no hay lecciones cargadas</h3>
            <p>Cuando se agreguen contenidos a «contenidos_educativos», aparecerán aquí.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<section class="dash-recursos" aria-labelledby="titulo-recursos">
    <div class="dash-herr">
        <h2 id="titulo-recursos">Recursos y herramientas</h2>
    </div>
    <div class="dash-recursos-grid">
        <a class="recurso-card recurso-camara" href="camara/detector.php">
            <span class="recurso-icono" aria-hidden="true">📷</span>
            <div class="recurso-texto">
                <span class="recurso-etq">Cámara + IA</span>
                <h3>Detector de señas</h3>
                <p>Activa tu cámara y comprueba en tiempo real si tu seña coincide con la letra objetivo.</p>
                <span class="recurso-cta">Abrir detector <span aria-hidden="true">→</span></span>
            </div>
        </a>
        <a class="recurso-card recurso-juegos" href="practica.php">
            <span class="recurso-icono" aria-hidden="true">🎮</span>
            <div class="recurso-texto">
                <span class="recurso-etq">Minijuegos</span>
                <h3>Practica jugando</h3>
                <p>Quiz, memoria y alfabeto interactivo para repasar sin presión.</p>
                <span class="recurso-cta">Jugar ahora <span aria-hidden="true">→</span></span>
            </div>
        </a>
    </div>
</section>

</main>

<script src="js/seva-ui.js"></script>
<script>
    if (window.SEVA) {
        SEVA.init(<?= (int) $usuario_id ?>);
        SEVA.menu();
    }

    /* Buscador + filtro por nivel (sin recargar la página) */
    (function () {
        var buscador = document.getElementById('buscador');
        if (!buscador) return;

        var tarjetas = Array.prototype.slice.call(document.querySelectorAll('.dash-card'));
        var botones  = Array.prototype.slice.call(document.querySelectorAll('.dash-filtro'));
        var contador = document.getElementById('dash-resultados');
        var vacio    = document.getElementById('dash-vacio');
        var nivel    = 'todos';

        function normalizar(texto) {
            return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        }

        function aplicar() {
            var consulta = normalizar(buscador.value.trim());
            var visibles = 0;

            tarjetas.forEach(function (tarjeta) {
                var coincideNivel = nivel === 'todos' || tarjeta.dataset.nivel === nivel;
                var coincideTexto = consulta === '' || normalizar(tarjeta.dataset.texto || '').indexOf(consulta) !== -1;
                var visible = coincideNivel && coincideTexto;
                tarjeta.hidden = !visible;
                if (visible) visibles++;
            });

            if (contador) {
                contador.textContent = visibles + (visibles === 1 ? ' lección' : ' lecciones');
            }
            if (vacio) {
                vacio.hidden = visibles > 0;
            }
        }

        buscador.addEventListener('input', aplicar);

        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                nivel = boton.dataset.nivel;
                botones.forEach(function (b) {
                    b.setAttribute('aria-pressed', b === boton ? 'true' : 'false');
                });
                aplicar();
            });
        });
    })();
</script>
</body>
</html>
