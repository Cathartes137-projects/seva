<?php
// =============================================================================
// practica.php - Juegos de práctica (Quiz, Memoria y Alfabeto)
// =============================================================================
// Integrado desde seva-mary V-2-2.1. Toda la lógica de juego es del lado del
// cliente: no escribe en la base de datos. El XP, la racha y los récords se
// guardan en localStorage (fase actual, sin migración de BD).
// =============================================================================

require '../config/conexion.php';
require '../config/seguridad.php';

seguridad_iniciar_sesion();
seguridad_requiere_login();

$usuario_id = (int) $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$primer     = explode(' ', trim($nombre))[0] ?: 'Estudiante';

/**
 * Carga un JSON de data/ y devuelve una lista. Ante cualquier error devuelve [].
 */
function seva_cargar_json(string $archivo): array
{
    $ruta = __DIR__ . '/data/' . $archivo;
    if (!is_file($ruta)) {
        return [];
    }
    $datos = json_decode((string) file_get_contents($ruta), true);
    return is_array($datos) ? array_values($datos) : [];
}

$alfabeto = seva_cargar_json('alfabeto.json');
$guia     = seva_cargar_json('guia.json');
$modo     = $_GET['modo'] ?? 'quiz';
if (!in_array($modo, ['quiz', 'memoria', 'alfabeto'], true)) {
    $modo = 'quiz';
}

$JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Practicar · SEVA</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css">
<link rel="stylesheet" href="css/juegos.css">
</head>
<body class="pagina-practica">
<?php $base = ''; require __DIR__ . '/partials/topbar.php'; ?>

<main class="contenedor" id="contenido">
    <h1 class="titulo-pag">Practicar señas <span aria-hidden="true">🎮</span></h1>
    <p class="nota-local">El XP, la racha y los récords se guardan en este dispositivo.</p>

    <div class="tabs" role="tablist" aria-label="Modo de práctica">
        <button class="tab" type="button" role="tab" id="tab-quiz" data-p="quiz"
                aria-controls="p-quiz" aria-selected="<?= $modo === 'quiz' ? 'true' : 'false' ?>"
                tabindex="<?= $modo === 'quiz' ? '0' : '-1' ?>">❓ Quiz</button>
        <button class="tab" type="button" role="tab" id="tab-memoria" data-p="memoria"
                aria-controls="p-memoria" aria-selected="<?= $modo === 'memoria' ? 'true' : 'false' ?>"
                tabindex="<?= $modo === 'memoria' ? '0' : '-1' ?>">🧠 Memoria</button>
        <button class="tab" type="button" role="tab" id="tab-alfabeto" data-p="alfabeto"
                aria-controls="p-alfabeto" aria-selected="<?= $modo === 'alfabeto' ? 'true' : 'false' ?>"
                tabindex="<?= $modo === 'alfabeto' ? '0' : '-1' ?>">🔤 Alfabeto</button>
    </div>

    <!-- QUIZ -->
    <section id="p-quiz" class="panel <?= $modo === 'quiz' ? 'on' : '' ?>" role="tabpanel" aria-labelledby="tab-quiz" tabindex="0"<?= $modo === 'quiz' ? '' : ' hidden' ?>>
        <h2>¿Qué palabra corresponde a esta descripción?</h2>
        <div id="q-juego">
            <div class="quiz-cab"><span id="q-prog" aria-live="polite"></span><span class="combo" id="q-combo" hidden></span></div>
            <div class="pista" aria-hidden="true"><div class="pista-fill" id="q-barra"></div></div>
            <div class="marco-sena"><img id="q-img" src="" alt="Tarjeta de referencia visual" aria-hidden="true"></div>
            <p id="q-pista" class="pista-texto" aria-live="polite"></p>
            <div id="q-ops" class="opciones"></div>
            <p id="q-msg" class="msg" role="status"></p>
            <div class="quiz-acciones"><button class="btn btn-primario" id="q-sig" type="button" hidden>Siguiente →</button></div>
        </div>
        <div id="q-fin" class="resultado" role="status" hidden></div>
    </section>

    <!-- MEMORIA -->
    <section id="p-memoria" class="panel <?= $modo === 'memoria' ? 'on' : '' ?>" role="tabpanel" aria-labelledby="tab-memoria" tabindex="0"<?= $modo === 'memoria' ? '' : ' hidden' ?>>
        <h2>Memoria: empareja la seña con la palabra</h2>
        <div class="mem-cab">
            <div class="mem-stats">
                <span>Intentos: <b id="m-int">0</b></span>
                <span>Aciertos: <b id="m-ac">0</b></span>
                <span>⏱ <b id="m-t">0</b> s</span>
                <span id="m-rec" hidden>🏆 Récord: <b id="m-rec-n">0</b></span>
            </div>
            <button class="btn btn-primario" id="m-nuevo" type="button">🔀 Nueva partida</button>
        </div>
        <div id="m-grid" class="grid-mem"></div>
        <p id="m-info" class="msg" role="status"></p>
    </section>

    <!-- ALFABETO -->
    <section id="p-alfabeto" class="panel <?= $modo === 'alfabeto' ? 'on' : '' ?>" role="tabpanel" aria-labelledby="tab-alfabeto" tabindex="0"<?= $modo === 'alfabeto' ? '' : ' hidden' ?>>
        <h2>Alfabeto dactilológico</h2>
        <p class="ayuda">Toca una letra para ver la seña y cómo hacerla.</p>
        <div id="letras" class="letras"></div>
        <div id="detalle" class="detalle" aria-live="polite"></div>

        <div class="deletreo-caja">
            <h2>Deletrea tu nombre ✍️</h2>
            <p class="ayuda">Escribe un nombre y mira cómo se deletrea con las manos.</p>
            <div class="deletreo-form">
                <label class="visually-hidden" for="nombre-in">Nombre a deletrear</label>
                <input type="text" id="nombre-in" maxlength="20" value="<?= htmlspecialchars($primer) ?>" placeholder="Tu nombre" autocomplete="given-name">
                <button class="btn btn-verde" id="btn-deletrear" type="button">Deletrear</button>
            </div>
            <div id="deletreo" class="deletreo" aria-live="polite"></div>
        </div>
    </section>
</main>

<script src="js/seva-ui.js"></script>
<script>
const ALFABETO = <?= json_encode($alfabeto, $JSON) ?>;
const GUIA     = <?= json_encode($guia, $JSON) ?>;
const UID      = <?= $usuario_id ?>;
if (window.SEVA) {
    SEVA.init(UID);
    SEVA.menu();
}

const $ = id => document.getElementById(id);
const mezclar = a => {
    a = a.slice();
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
};
const sinTildes = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

/* ---------- Pestañas accesibles ---------- */
const tabs = Array.from(document.querySelectorAll('.tab'));

function activarTab(t, moverFoco) {
    tabs.forEach(x => {
        const activo = x === t;
        x.setAttribute('aria-selected', activo ? 'true' : 'false');
        x.tabIndex = activo ? 0 : -1;
    });
    document.querySelectorAll('.panel').forEach(p => {
        p.classList.remove('on');
        p.hidden = true;
    });
    const panel = $('p-' + t.dataset.p);
    if (panel) {
        panel.hidden = false;
        panel.classList.add('on');
    }
    try {
        history.replaceState(null, '', '?modo=' + t.dataset.p);
    } catch (e) { /* history no disponible */ }
    if (moverFoco) t.focus();
}

tabs.forEach((t, i) => {
    t.addEventListener('click', () => activarTab(t, false));
    t.addEventListener('keydown', e => {
        let destino = null;
        if (e.key === 'ArrowRight') destino = (i + 1) % tabs.length;
        else if (e.key === 'ArrowLeft') destino = (i - 1 + tabs.length) % tabs.length;
        else if (e.key === 'Home') destino = 0;
        else if (e.key === 'End') destino = tabs.length - 1;
        if (destino !== null) {
            e.preventDefault();
            activarTab(tabs[destino], true);
        }
    });
});

const panelActivo = () => (document.querySelector('.panel.on') || {}).id;
const POOL = GUIA.map(g => ({ img: g.archivo, palabra: g.palabra, descripcion: g.descripcion || '' }));

/* ================= QUIZ ================= */
let orden = [], qi = 0, aciertos = 0, combo = 0, respondida = false;

function nuevoQuiz() {
    if (POOL.length < 4) {
        $('q-juego').innerHTML = '<p class="vacio-datos">Se necesitan al menos 4 señas en <code>data/guia.json</code> para jugar.</p>';
        return;
    }
    orden = mezclar(POOL).slice(0, 10);
    qi = 0;
    aciertos = 0;
    combo = 0;
    $('q-fin').hidden = true;
    $('q-juego').hidden = false;
    mostrarQ();
}

function pintarCombo() {
    const c = $('q-combo');
    if (combo >= 2) {
        c.hidden = false;
        c.textContent = '🔥 Racha x' + combo;
        SEVA.anim(c, 'sube');
    } else {
        c.hidden = true;
    }
}

function mostrarQ() {
    if (qi >= orden.length) return finQuiz();
    const it = orden[qi];
    respondida = false;
    $('q-prog').textContent = 'Pregunta ' + (qi + 1) + ' de ' + orden.length;
    $('q-barra').style.width = (qi / orden.length * 100) + '%';
    $('q-pista').textContent = it.descripcion || 'Sin descripción disponible.';
    const img = $('q-img');
    img.src = it.img;
    img.classList.remove('cambia');
    void img.offsetWidth;
    img.classList.add('cambia');
    $('q-msg').textContent = '';
    $('q-sig').hidden = true;
    pintarCombo();
    const ops = mezclar([it].concat(mezclar(POOL.filter(p => p.palabra !== it.palabra)).slice(0, 3)));
    $('q-ops').innerHTML = '';
    ops.forEach((o, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'op';
        b.dataset.palabra = o.palabra;
        const tecla = document.createElement('span');
        tecla.className = 'tecla';
        tecla.textContent = i + 1;
        b.append(tecla, document.createTextNode(o.palabra));
        b.onclick = () => responder(b, o, it);
        $('q-ops').appendChild(b);
    });
}

function responder(b, o, it) {
    if (respondida) return;
    respondida = true;
    document.querySelectorAll('#q-ops .op').forEach(x => {
        x.disabled = true;
        if (x.dataset.palabra === it.palabra) x.classList.add('ok');
    });
    if (o.palabra === it.palabra) {
        aciertos++;
        combo++;
        const extra = combo >= 3 ? 5 : 0;
        SEVA.sumarXP(10 + extra);
        $('q-msg').textContent = '✅ ¡Correcto! +' + (10 + extra) + ' XP' + (extra ? ' (bonus de racha)' : '');
    } else {
        combo = 0;
        b.classList.remove('ok');
        b.classList.add('mal');
        $('q-msg').textContent = '❌ Era «' + it.palabra + '»';
    }
    pintarCombo();
    $('q-sig').textContent = qi === orden.length - 1 ? 'Ver resultado' : 'Siguiente →';
    $('q-sig').hidden = false;
    $('q-sig').focus({ preventScroll: true });
}

$('q-sig').onclick = () => { qi++; mostrarQ(); };

function finQuiz() {
    $('q-barra').style.width = '100%';
    const n = orden.length;
    const est = aciertos >= Math.ceil(n * 0.9) ? 3 : aciertos >= Math.ceil(n * 0.6) ? 2 : aciertos >= Math.ceil(n * 0.3) ? 1 : 0;
    const msgs = ['¡Sigue practicando, tú puedes!', '¡Buen comienzo!', '¡Muy bien!', '¡Excelente, dominas estas señas!'];
    let e = '';
    for (let i = 0; i < 3; i++) {
        e += '<span class="' + (i < est ? 'on' : '') + '" style="animation-delay:' + (i * 0.25 + 0.2) + 's">⭐</span>';
    }
    $('q-juego').hidden = true;
    const f = $('q-fin');
    f.hidden = false;
    f.innerHTML = '<div class="estrellas" aria-hidden="true">' + e + '</div><h3>' + msgs[est] + '</h3><p>Acertaste ' + aciertos + ' de ' + n + ' señas.</p>' +
        '<div class="acciones"><button class="btn btn-verde" id="q-otra" type="button">🔁 Jugar de nuevo</button><a class="btn btn-ghost" href="dashboard.php">Mis lecciones</a></div>';
    $('q-otra').onclick = nuevoQuiz;
    if (est >= 2) SEVA.confeti(est === 3 ? 180 : 110);
}

/* Atajos de teclado del quiz: 1-4 para responder */
document.addEventListener('keydown', e => {
    if (panelActivo() !== 'p-quiz' || /INPUT|TEXTAREA/.test(e.target.tagName) || e.ctrlKey || e.metaKey) return;
    if (/^[1-4]$/.test(e.key) && !respondida) {
        const b = document.querySelectorAll('#q-ops .op')[+e.key - 1];
        if (b) b.click();
    }
});

/* ================= MEMORIA ================= */
let timerMem = null;
const K_REC = 'seva_' + UID + '_mem_record';

function nuevaMemoria() {
    clearInterval(timerMem);
    $('m-t').textContent = '0';
    $('m-int').textContent = '0';
    $('m-ac').textContent = '0';
    $('m-grid').innerHTML = '';
    $('m-info').textContent = '';
    const rec = SEVA.leer(K_REC, 0);
    $('m-rec').hidden = !rec;
    $('m-rec-n').textContent = rec;
    if (POOL.length < 2) {
        $('m-grid').innerHTML = '<p class="vacio-datos">Faltan señas en <code>data/guia.json</code>.</p>';
        return;
    }

    const pares = mezclar(POOL).slice(0, Math.min(6, POOL.length));
    const cartas = mezclar(pares.flatMap(p => [
        { id: p.palabra, t: 'img', c: p.img },
        { id: p.palabra, t: 'txt', c: p.palabra }
    ]));
    let a = null, bloqueo = false, ac = 0, intentos = 0, seg = 0, iniciado = false;

    cartas.forEach(c => {
        const d = document.createElement('button');
        d.type = 'button';
        d.className = 'carta';
        d.setAttribute('aria-label', 'Carta oculta');

        const dorso = document.createElement('span');
        dorso.className = 'cara dorso';
        dorso.setAttribute('aria-hidden', 'true');
        dorso.textContent = '?';

        const frente = document.createElement('span');
        frente.className = 'cara frente';
        if (c.t === 'img') {
            const im = document.createElement('img');
            im.alt = '';
            im.src = c.c;
            frente.appendChild(im);
        } else {
            frente.textContent = c.c;
        }
        d.append(dorso, frente);

        d.onclick = () => {
            if (bloqueo || d.classList.contains('volteada') || d.classList.contains('par')) return;
            if (!iniciado) {
                iniciado = true;
                timerMem = setInterval(() => {
                    seg++;
                    $('m-t').textContent = seg;
                }, 1000);
            }
            d.classList.add('volteada');
            d.setAttribute('aria-label', c.t === 'img' ? 'Seña de ' + c.id : 'Palabra ' + c.c);
            if (!a) {
                a = { c, d };
                return;
            }
            const b = { c, d };
            intentos++;
            bloqueo = true;
            $('m-int').textContent = intentos;
            if (a.c.id === b.c.id && a.c.t !== b.c.t) {
                [a, b].forEach(x => {
                    x.d.classList.add('par');
                    x.d.disabled = true;
                });
                ac++;
                $('m-ac').textContent = ac;
                SEVA.sumarXP(15);
                a = null;
                bloqueo = false;
                if (ac === pares.length) {
                    clearInterval(timerMem);
                    const mejor = SEVA.leer(K_REC, 0);
                    const nuevo = !mejor || intentos < mejor;
                    if (nuevo) {
                        SEVA.guardar(K_REC, intentos);
                        $('m-rec').hidden = false;
                        $('m-rec-n').textContent = intentos;
                    }
                    $('m-info').textContent = '🎉 ¡Completado en ' + intentos + ' intentos y ' + seg + ' s!' + (nuevo ? ' Nuevo récord 🏆' : '');
                    SEVA.confeti(150);
                }
            } else {
                const x = a;
                a = null;
                [x.d, d].forEach(el => el.classList.add('error'));
                setTimeout(() => {
                    [x.d, d].forEach(el => {
                        el.classList.remove('volteada', 'error');
                        el.setAttribute('aria-label', 'Carta oculta');
                    });
                    bloqueo = false;
                }, 950);
            }
        };
        $('m-grid').appendChild(d);
    });
}

$('m-nuevo').onclick = nuevaMemoria;

/* ================= ALFABETO ================= */
let letraIdx = -1;

function mostrarLetra(i) {
    if (i < 0 || i >= ALFABETO.length) return;
    letraIdx = i;
    const it = ALFABETO[i];
    document.querySelectorAll('.letra').forEach((b, k) => b.setAttribute('aria-pressed', k === i ? 'true' : 'false'));
    const d = $('detalle');
    d.innerHTML = '';
    const w = document.createElement('div');
    w.className = 'contenido';
    const h = document.createElement('h3');
    h.textContent = 'Letra ' + it.letra;
    const im = document.createElement('img');
    im.src = it.archivo;
    im.alt = 'Seña de la letra ' + it.letra;
    const p = document.createElement('p');
    p.textContent = it.descripcion || '';
    w.append(h, im, p);
    if (it.con_movimiento) {
        const m = document.createElement('p');
        const mov = document.createElement('span');
        mov.className = 'mov';
        mov.textContent = '↻ Con movimiento';
        m.append(mov, document.createTextNode(' ' + (it.instruccion_movimiento || '')));
        w.appendChild(m);
    }
    const nav = document.createElement('div');
    nav.className = 'nav';
    const ant = document.createElement('button');
    const sig = document.createElement('button');
    ant.type = sig.type = 'button';
    ant.className = sig.className = 'btn btn-ghost';
    ant.textContent = '← ' + (ALFABETO[i - 1] ? ALFABETO[i - 1].letra : '');
    sig.textContent = (ALFABETO[i + 1] ? ALFABETO[i + 1].letra : '') + ' →';
    ant.disabled = i === 0;
    sig.disabled = i === ALFABETO.length - 1;
    ant.onclick = () => mostrarLetra(i - 1);
    sig.onclick = () => mostrarLetra(i + 1);
    nav.append(ant, sig);
    w.appendChild(nav);
    d.appendChild(w);
    d.scrollIntoView({
        behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'nearest'
    });
}

if (!ALFABETO.length) {
    $('letras').innerHTML = '<p class="vacio-datos">No se encontró <code>data/alfabeto.json</code>.</p>';
}
ALFABETO.forEach((it, i) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'letra';
    b.textContent = it.letra;
    b.setAttribute('aria-pressed', 'false');
    b.setAttribute('aria-label', 'Letra ' + it.letra);
    b.onclick = () => mostrarLetra(i);
    $('letras').appendChild(b);
});

/* Deletrear un nombre con las imágenes del alfabeto */
function deletrear() {
    const mapa = {};
    ALFABETO.forEach(a => { mapa[String(a.letra).toUpperCase()] = a; });
    const txt = sinTildes(($('nombre-in').value || '').normalize('NFC').toUpperCase().replace(/Ñ/g, '\u0001')).replace(/\u0001/g, 'Ñ');
    const cont = $('deletreo');
    cont.innerHTML = '';
    let n = 0;
    Array.from(txt).forEach(ch => {
        if (ch === ' ') {
            const s = document.createElement('span');
            s.className = 'espacio';
            cont.appendChild(s);
            return;
        }
        const a = mapa[ch];
        if (!a) return;
        const f = document.createElement('figure');
        f.style.animationDelay = (n++ * 0.12) + 's';
        const im = document.createElement('img');
        im.src = a.archivo;
        im.alt = 'Seña de la letra ' + ch;
        const c = document.createElement('figcaption');
        c.textContent = ch;
        f.append(im, c);
        cont.appendChild(f);
    });
    if (!n) {
        cont.textContent = 'Escribe al menos una letra.';
    } else {
        SEVA.toast('¡Así se deletrea! ✋', 'ok');
    }
}

$('btn-deletrear').onclick = deletrear;
$('nombre-in').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        e.preventDefault();
        deletrear();
    }
});

nuevoQuiz();
nuevaMemoria();
</script>
</body>
</html>
