/* SEVA · utilidades de interfaz: racha, XP, confeti, toasts y revelado.
   Integrado desde seva-mary V-2-2.1 y recortado: el menú superior se inicializa
   aparte con SEVA.menu() para no duplicar los manejadores de las páginas que ya
   tienen su propio script. */
(function () {
    'use strict';

    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SEVA = window.SEVA = { uid: 0 };

    function leer(k, def) {
        try {
            var v = localStorage.getItem(k);
            return v === null ? def : JSON.parse(v);
        } catch (e) {
            return def;
        }
    }

    function guardar(k, v) {
        try {
            localStorage.setItem(k, JSON.stringify(v));
        } catch (e) {
            /* Almacenamiento no disponible: la interfaz sigue funcionando. */
        }
    }

    function fmt(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    SEVA.leer = leer;
    SEVA.guardar = guardar;

    /* ---- Racha (días seguidos) y XP, por usuario y por dispositivo ---- */
    SEVA.marcarDia = function () {
        var k = 'seva_dias_' + SEVA.uid;
        var d = leer(k, []);
        var h = fmt(new Date());
        if (!Array.isArray(d)) d = [];
        if (d.indexOf(h) === -1) {
            d.push(h);
            guardar(k, d);
        }
    };

    SEVA.racha = function () {
        var d = leer('seva_dias_' + SEVA.uid, []);
        var s = {};
        var c = new Date();
        var n = 0;
        (Array.isArray(d) ? d : []).forEach(function (x) { s[x] = 1; });
        if (!s[fmt(c)]) c.setDate(c.getDate() - 1);
        while (s[fmt(c)]) {
            n++;
            c.setDate(c.getDate() - 1);
        }
        return n;
    };

    SEVA.xp = function () {
        return parseInt(leer('seva_puntaje_' + SEVA.uid, 0), 10) || 0;
    };

    SEVA.sumarXP = function (n) {
        guardar('seva_puntaje_' + SEVA.uid, SEVA.xp() + n);
        SEVA.pintarStats(true);
    };

    SEVA.pintarStats = function (animar) {
        document.querySelectorAll('[data-racha]').forEach(function (e) {
            e.textContent = SEVA.racha();
        });
        document.querySelectorAll('[data-xp]').forEach(function (e) {
            e.textContent = SEVA.xp();
            var chip = e.closest('.chip');
            if (animar && chip && !reduce) {
                chip.classList.remove('sube');
                void chip.offsetWidth;
                chip.classList.add('sube');
            }
        });
    };

    /* ---- Contador animado (si el usuario reduce movimiento, salta al valor) ---- */
    SEVA.contar = function (el, destino, dur, sufijo) {
        if (!el) return;
        sufijo = sufijo || '';
        dur = dur || 1100;
        if (reduce) {
            el.textContent = destino + sufijo;
            return;
        }
        var t0 = performance.now();
        (function paso(t) {
            var p = Math.min((t - t0) / dur, 1);
            var e = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(destino * e) + sufijo;
            if (p < 1) requestAnimationFrame(paso);
        })(t0);
    };

    /* ---- Toast accesible ---- */
    SEVA.toast = function (msg, tipo) {
        var cont = document.querySelector('.toasts');
        if (!cont) {
            cont = document.createElement('div');
            cont.className = 'toasts';
            cont.setAttribute('role', 'status');
            cont.setAttribute('aria-live', 'polite');
            document.body.appendChild(cont);
        }
        var t = document.createElement('div');
        t.className = 'toast ' + (tipo || '');
        t.textContent = msg;
        cont.appendChild(t);
        setTimeout(function () {
            t.classList.add('sale');
            setTimeout(function () { t.remove(); }, 320);
        }, 2800);
    };

    /* ---- Confeti (canvas decorativo, se limpia solo) ---- */
    SEVA.confeti = function (cantidad) {
        if (reduce) return;
        var cv = document.createElement('canvas');
        cv.className = 'confeti';
        cv.setAttribute('aria-hidden', 'true');
        document.body.appendChild(cv);
        var ctx = cv.getContext('2d');
        var w = cv.width = innerWidth;
        var h = cv.height = innerHeight;
        var cols = ['#0056b3', '#28a745', '#FFB400', '#4DA3FF', '#7CF29B', '#ffffff'];
        var ps = [];
        var n = cantidad || 140;
        for (var i = 0; i < n; i++) {
            ps.push({
                x: w / 2 + (Math.random() - 0.5) * 120,
                y: h * 0.55,
                vx: (Math.random() - 0.5) * 16,
                vy: -Math.random() * 15 - 5,
                r: Math.random() * 6 + 4,
                a: Math.random() * 6.28,
                va: (Math.random() - 0.5) * 0.4,
                c: cols[i % cols.length],
                f: Math.random() > 0.5
            });
        }
        var t0 = performance.now();
        (function dib(t) {
            var vida = t - t0;
            ctx.clearRect(0, 0, w, h);
            ps.forEach(function (p) {
                p.vy += 0.38;
                p.vx *= 0.992;
                p.x += p.vx;
                p.y += p.vy;
                p.a += p.va;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.a);
                ctx.globalAlpha = Math.max(0, 1 - vida / 3200);
                ctx.fillStyle = p.c;
                if (p.f) {
                    ctx.fillRect(-p.r, -p.r / 2, p.r * 2, p.r);
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, p.r / 1.6, 0, 6.28);
                    ctx.fill();
                }
                ctx.restore();
            });
            if (vida < 3200) requestAnimationFrame(dib); else cv.remove();
        })(t0);
    };

    /* ---- Reinicia una animación CSS puntual ---- */
    SEVA.anim = function (el, clase) {
        if (!el || reduce) return;
        el.classList.remove(clase);
        void el.offsetWidth;
        el.classList.add(clase);
    };

    /* ---- Menú hamburguesa (llamada explícita para no duplicar manejadores) ---- */
    function iniciarMenu() {
        var b = document.getElementById('btn-opciones');
        var p = document.getElementById('panel-opciones');
        if (!b || !p || b.dataset.menuIniciado === '1') return;
        b.dataset.menuIniciado = '1';

        function cerrar() {
            p.classList.remove('abierto');
            b.setAttribute('aria-expanded', 'false');
        }

        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var ab = p.classList.toggle('abierto');
            b.setAttribute('aria-expanded', ab ? 'true' : 'false');
        });
        p.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('click', cerrar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && p.classList.contains('abierto')) {
                cerrar();
                b.focus();
            }
        });
    }

    SEVA.menu = iniciarMenu;

    /* ---- Revelado al hacer scroll ---- */
    SEVA.revelar = function (raiz) {
        var els = (raiz || document).querySelectorAll('[data-reveal]:not(.visto)');
        if (reduce || !('IntersectionObserver' in window)) {
            els.forEach(function (e) { e.classList.add('visto'); });
            return;
        }
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (x) {
                if (x.isIntersecting) {
                    x.target.classList.add('visto');
                    io.unobserve(x.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
        els.forEach(function (e) { io.observe(e); });
    };

    SEVA.init = function (uid) {
        SEVA.uid = uid || 0;
        SEVA.marcarDia();
        SEVA.pintarStats(false);
        SEVA.revelar();
    };
})();
