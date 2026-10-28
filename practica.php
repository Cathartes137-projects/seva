<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$nombre = $_SESSION['nombre'] ?? 'Estudiante';

function cargar_json(string $f): array {
    $r = __DIR__ . '/data/' . $f;
    $d = is_file($r) ? json_decode(file_get_contents($r), true) : [];
    return is_array($d) ? $d : [];
}
$alfabeto = cargar_json('alfabeto.json');
$guia     = cargar_json('guia.json');
$modo     = $_GET['modo'] ?? 'quiz';
if (!in_array($modo, ['quiz', 'memoria', 'alfabeto'], true)) $modo = 'quiz';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Practicar · SEVA</title>
<link rel="stylesheet" href="css/estilos.css">
<link rel="stylesheet" href="css/personaje-sevy.css">
<style>
    body{background:#f3f8ff}
    .top{background:#fff;border-bottom:1px solid #e4eaf3;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
    .top a{font-weight:700}
    .wrap{max-width:900px;margin:0 auto;padding:20px 16px 60px}
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}
    .tab{padding:10px 18px;border-radius:99px;border:2px solid #0056b3;background:#fff;color:#0056b3;font-weight:700;cursor:pointer;font-size:16px}
    .tab[aria-selected="true"]{background:#0056b3;color:#fff}
    .panel{display:none;background:#fff;border-radius:14px;padding:20px;box-shadow:0 6px 16px rgba(0,0,0,.08)}
    .panel.on{display:block}
    .stats{display:flex;gap:16px;flex-wrap:wrap;font-weight:700;color:#003d80}
    .sena-img{display:block;margin:10px auto;max-width:260px;width:100%;border-radius:12px;border:1px solid #e4eaf3;background:#f4f8ff}
    .opciones{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:12px}
    .op{padding:12px;border-radius:10px;border:2px solid #cfd9ea;background:#fff;font-size:17px;font-weight:600;cursor:pointer}
    .op:hover:not(:disabled){border-color:#0056b3}
    .op.ok{background:#e6f6ea;border-color:#28a745}
    .op.mal{background:#fdecef;border-color:#d6465b}
    .msg{min-height:1.6em;font-weight:700;margin-top:10px}
    .grid-mem{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:12px}
    .carta{aspect-ratio:1;border-radius:10px;border:2px solid #cfd9ea;background:#0056b3;color:#fff;font-size:28px;font-weight:700;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:4px;text-align:center;overflow:hidden}
    .carta.vista{background:#fff;color:#003d80;font-size:15px}
    .carta.par{border-color:#28a745;background:#e6f6ea;color:#1e7e34;cursor:default}
    .carta img{width:100%;height:100%;object-fit:contain}
    .letras{display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:10px}
    .letra{padding:12px 0;font-size:22px;font-weight:700;background:#fff;border:2px solid #cfd9ea;border-radius:10px;cursor:pointer}
    .letra:hover{background:#caf0f8}
    .detalle{margin-top:16px;text-align:center}
    .mov{display:inline-block;font-size:13px;font-weight:700;color:#1e7e34;background:#e6f6ea;border-radius:99px;padding:2px 10px}
</style>
</head>
<body>
<header class="top">
    <a href="dashboard.php">← Mis lecciones</a>
    <span>Hola, <strong><?= htmlspecialchars($nombre) ?></strong></span>
</header>

<main class="wrap">
    <h1>Practicar señas</h1>
    <div class="stats">
        <span>🎯 Puntaje: <span id="puntaje">0</span></span>
        <span>🔥 Racha: <span id="racha">1 día</span></span>
    </div>

    <div class="tabs" role="tablist">
        <button class="tab" role="tab" data-p="quiz"     aria-selected="<?= $modo==='quiz'?'true':'false' ?>">❓ Quiz</button>
        <button class="tab" role="tab" data-p="memoria"  aria-selected="<?= $modo==='memoria'?'true':'false' ?>">🧠 Memoria</button>
        <button class="tab" role="tab" data-p="alfabeto" aria-selected="<?= $modo==='alfabeto'?'true':'false' ?>">🔤 Alfabeto</button>
    </div>

    <!-- QUIZ -->
    <section id="p-quiz" class="panel <?= $modo==='quiz'?'on':'' ?>">
        <h2>¿Qué seña es esta?</h2>
        <p id="q-prog" style="color:#0056b3;font-weight:700"></p>
        <img id="q-img" class="sena-img" src="" alt="Seña a identificar">
        <div id="q-ops" class="opciones"></div>
        <p id="q-msg" class="msg" role="status"></p>
        <button class="btn" id="q-sig" type="button" style="display:none">Siguiente →</button>
    </section>

    <!-- MEMORIA -->
    <section id="p-memoria" class="panel <?= $modo==='memoria'?'on':'' ?>">
        <h2>Memoria: empareja la seña con la palabra</h2>
        <button class="btn" id="m-nuevo" type="button">🔀 Nueva partida</button>
        <div id="m-grid" class="grid-mem"></div>
        <p id="m-info" class="msg" role="status"></p>
    </section>

    <!-- ALFABETO -->
    <section id="p-alfabeto" class="panel <?= $modo==='alfabeto'?'on':'' ?>">
        <h2>Alfabeto dactilológico</h2>
        <p>Toca una letra para ver la seña y cómo hacerla.</p>
        <div id="letras" class="letras"></div>
        <div id="detalle" class="detalle" aria-live="polite"></div>
    </section>
</main>

<script>
const ALFABETO = <?= json_encode($alfabeto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const GUIA     = <?= json_encode($guia,     JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const UID = <?= (int) $_SESSION['usuario_id'] ?>;
const K_PTS = 'seva_puntaje_' + UID, K_DIAS = 'seva_dias_' + UID;

const $ = id => document.getElementById(id);
const mezclar = a => a.map(v => [Math.random(), v]).sort((x, y) => x[0] - y[0]).map(x => x[1]);
const norm = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

/* Puntaje y racha (guardados en este dispositivo) */
let puntaje = parseInt(localStorage.getItem(K_PTS) || '0', 10);
function sumar(p) { puntaje += p; localStorage.setItem(K_PTS, puntaje); $('puntaje').textContent = puntaje; }
$('puntaje').textContent = puntaje;
(function () {
    let d = []; try { d = JSON.parse(localStorage.getItem(K_DIAS) || '[]'); } catch (e) {}
    const hoy = new Date().toISOString().slice(0, 10);
    if (!d.includes(hoy)) { d.push(hoy); localStorage.setItem(K_DIAS, JSON.stringify(d)); }
    $('racha').textContent = d.length + (d.length === 1 ? ' día' : ' días') + ' de práctica';
})();

/* Pestañas */
document.querySelectorAll('.tab').forEach(t => t.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(x => x.setAttribute('aria-selected', x === t));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('on'));
    $('p-' + t.dataset.p).classList.add('on');
}));

/* ---------- QUIZ ---------- */
const POOL = GUIA.map(g => ({ img: g.archivo, palabra: g.palabra }));
let orden = [], qi = 0;
function nuevoQuiz() { orden = mezclar(POOL).slice(0, 10); qi = 0; mostrarQ(); }
function mostrarQ() {
    if (qi >= orden.length) {
        $('q-img').style.display = 'none'; $('q-ops').innerHTML = ''; $('q-prog').textContent = '';
        $('q-msg').textContent = '🎉 ¡Quiz completado! Puntaje total: ' + puntaje;
        $('q-sig').textContent = '🔁 Jugar de nuevo'; $('q-sig').style.display = 'inline-flex';
        $('q-sig').onclick = () => { $('q-img').style.display = ''; nuevoQuiz(); };
        return;
    }
    const it = orden[qi];
    $('q-prog').textContent = 'Pregunta ' + (qi + 1) + ' de ' + orden.length;
    $('q-img').src = it.img; $('q-msg').textContent = ''; $('q-sig').style.display = 'none';
    const ops = mezclar([it].concat(mezclar(POOL.filter(p => p.palabra !== it.palabra)).slice(0, 3)));
    $('q-ops').innerHTML = '';
    ops.forEach(o => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'op'; b.textContent = o.palabra;
        b.onclick = () => {
            const bien = o.palabra === it.palabra;
            document.querySelectorAll('#q-ops .op').forEach(x => {
                x.disabled = true;
                if (x.textContent === it.palabra) x.classList.add('ok');
            });
            if (bien) {
                sumar(10); $('q-msg').textContent = '✅ ¡Correcto! +10';
                if (window.Seva) window.Seva.celebrar();
            } else {
                b.classList.add('mal'); $('q-msg').textContent = '❌ Era “' + it.palabra + '”';
                if (window.Seva) window.Seva.gracias();
            }
            $('q-sig').textContent = 'Siguiente →'; $('q-sig').style.display = 'inline-flex';
            $('q-sig').onclick = () => { qi++; mostrarQ(); };
        };
        $('q-ops').appendChild(b);
    });
}

/* ---------- MEMORIA ---------- */
function nuevaMemoria() {
    const pares = mezclar(POOL).slice(0, 6);
    const cartas = mezclar(pares.flatMap(p => [
        { id: p.palabra, t: 'img', c: p.img }, { id: p.palabra, t: 'txt', c: p.palabra }
    ]));
    let a = null, b = null, bloqueo = false, aciertos = 0, intentos = 0;
    $('m-grid').innerHTML = ''; $('m-info').textContent = '';
    cartas.forEach(c => {
        const d = document.createElement('button');
        d.type = 'button'; d.className = 'carta'; d.textContent = '?'; d.setAttribute('aria-label', 'Carta oculta');
        d.onclick = () => {
            if (bloqueo || d.classList.contains('vista') || d.classList.contains('par')) return;
            d.classList.add('vista');
            d.innerHTML = c.t === 'img' ? '<img src="' + c.c + '" alt="Seña">' : c.c;
            if (!a) { a = { c, d }; return; }
            b = { c, d }; intentos++; bloqueo = true;
            if (a.c.id === b.c.id && a.c.t !== b.c.t) {
                a.d.classList.add('par'); b.d.classList.add('par'); aciertos++; sumar(15);
                if (window.Seva) window.Seva.bien();
                a = b = null; bloqueo = false;
                if (aciertos === pares.length) {
                    $('m-info').textContent = '🎉 ¡Completado en ' + intentos + ' intentos!';
                    if (window.Seva) window.Seva.aplauso();
                }
            } else {
                setTimeout(() => {
                    [a, b].forEach(x => { x.d.classList.remove('vista'); x.d.textContent = '?'; });
                    a = b = null; bloqueo = false;
                }, 900);
            }
            if (aciertos < pares.length) $('m-info').textContent = 'Intentos: ' + intentos + ' · Aciertos: ' + aciertos;
        };
        $('m-grid').appendChild(d);
    });
}
$('m-nuevo').onclick = nuevaMemoria;

/* ---------- ALFABETO ---------- */
ALFABETO.forEach(it => {
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'letra'; b.textContent = it.letra;
    b.setAttribute('aria-label', 'Letra ' + it.letra);
    b.onclick = () => {
        const d = $('detalle'); d.innerHTML = '';
        const h = document.createElement('h3'); h.textContent = 'Letra ' + it.letra;
        const im = document.createElement('img'); im.className = 'sena-img'; im.src = it.archivo; im.alt = 'Seña de la letra ' + it.letra;
        const p = document.createElement('p'); p.textContent = it.descripcion;
        d.append(h, im, p);
        if (it.con_movimiento) {
            const m = document.createElement('p');
            m.innerHTML = '<span class="mov">↻ Con movimiento</span> ';
            m.append(it.instruccion_movimiento || ''); d.appendChild(m);
        }
        if (window.Seva) window.Seva.saludar();
        d.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
    $('letras').appendChild(b);
});

nuevoQuiz(); nuevaMemoria();
</script>
<script src="js/personaje-sevy.js"></script>
</body>
</html>
