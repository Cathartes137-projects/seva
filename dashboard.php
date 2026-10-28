<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';

$error_bd = '';
$lecciones = [];
$total = 0;
$completadas = 0;
$porcentaje = 0;
$completados_ids = [];

try {
    $lecciones = $pdo->query('SELECT id, titulo, ruta_imagen FROM contenidos_educativos ORDER BY id')->fetchAll();
    $total = $pdo->query('SELECT COUNT(*) AS c FROM contenidos_educativos')->fetch()['c'];

    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt->execute([$usuario_id]);
    $completadas = $stmt->fetch()['c'];

    $porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;

    $stmt = $pdo->prepare('SELECT contenido_id FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt->execute([$usuario_id]);
    $completados_ids = array_column($stmt->fetchAll(), 'contenido_id');
} catch (PDOException $e) {
    // No interrumpimos la página con un fatal error: mostramos un aviso claro
    // y registramos el detalle técnico solo en el log del servidor.
    error_log('Dashboard - error de base de datos: ' . $e->getMessage());
    $error_bd = 'No se pudieron cargar las lecciones. Verifica que las tablas "contenidos_educativos" y "progreso_usuario" existan en la base de datos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi aprendizaje · Señas</title>
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

    .contenedor{
        max-width:1080px;
        margin:0 auto;
        padding:2.2rem 1.5rem 3.5rem;
    }

    .aviso-error{
        background:var(--danger-bg);
        color:var(--danger);
        border-radius:0.9rem;
        padding:1.1rem 1.3rem;
        margin-bottom:1.6rem;
        font-size:0.95rem;
        line-height:1.5;
    }
    .aviso-error strong{ display:block; margin-bottom:0.2rem; font-family:'Baloo 2', sans-serif; }

    .panel-progreso{
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:1rem;
        padding:1.4rem 1.6rem;
        margin-bottom:1.8rem;
    }
    .fila-progreso{
        display:flex;
        justify-content:space-between;
        align-items:baseline;
        margin-bottom:0.7rem;
    }
    .fila-progreso .etiqueta{
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:1.05rem;
    }
    .fila-progreso .cifra{
        font-family:'Space Mono', monospace;
        font-weight:700;
        color:var(--coral-dark);
        font-size:1.1rem;
    }
    .barra{
        height:0.9rem;
        border-radius:1rem;
        background:var(--line);
        overflow:hidden;
    }
    .barra-relleno{
        height:100%;
        border-radius:1rem;
        background:linear-gradient(90deg, var(--coral) 0%, var(--gold) 100%);
        transition:width 0.4s ease;
    }
    .sub-progreso{
        margin:0.6rem 0 0;
        font-size:0.85rem;
        color:var(--muted);
    }

    #buscador{
        width:100%;
        max-width:340px;
        padding:0.75rem 1rem;
        border:1.5px solid var(--line);
        border-radius:0.7rem;
        font-family:'Inter', sans-serif;
        font-size:0.95rem;
        margin-bottom:1.6rem;
        background:var(--paper);
    }
    #buscador:focus{
        outline:none;
        border-color:var(--coral);
        box-shadow:0 0 0 4px rgba(255,107,84,0.14);
    }

    .grid-lecciones{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));
        gap:1.4rem;
    }
    .tarjeta{
        background:var(--paper);
        border:1px solid var(--line);
        border-radius:1rem;
        overflow:hidden;
        display:flex;
        flex-direction:column;
        transition:transform 0.15s ease, box-shadow 0.15s ease;
    }
    .tarjeta:hover{
        transform:translateY(-4px);
        box-shadow:0 12px 24px -12px rgba(22,27,51,0.25);
    }
    .tarjeta img{
        width:100%;
        height:150px;
        object-fit:cover;
        background:var(--line);
    }
    .tarjeta-cuerpo{
        padding:1.1rem 1.2rem 1.3rem;
        display:flex;
        flex-direction:column;
        flex:1;
    }
    .tarjeta-cuerpo h3{
        font-family:'Baloo 2', sans-serif;
        font-size:1.05rem;
        margin:0 0 0.6rem;
        color:var(--ink);
    }
    .insignia{
        align-self:flex-start;
        font-family:'Space Mono', monospace;
        font-size:0.7rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.05em;
        color:#0F1329;
        background:var(--teal);
        padding:0.25rem 0.6rem;
        border-radius:0.4rem;
        margin-bottom:0.7rem;
    }
    .btn-ver{
        margin-top:auto;
        border:none;
        background:var(--coral);
        color:#fff;
        font-weight:700;
        font-family:'Inter', sans-serif;
        padding:0.65rem;
        border-radius:0.6rem;
        cursor:pointer;
        transition:background 0.15s ease;
    }
    .btn-ver:hover{ background:var(--coral-dark); }
    .btn-ver:focus-visible{ outline:3px solid var(--gold); outline-offset:2px; }

    .estado-vacio{
        text-align:center;
        padding:3rem 1.5rem;
        border:1.5px dashed var(--line);
        border-radius:1rem;
        color:var(--muted);
    }
    .estado-vacio h3{
        font-family:'Baloo 2', sans-serif;
        color:var(--ink);
        margin:0 0 0.4rem;
    }

    @media (max-width: 560px){
        .topbar{ padding:1rem 1.2rem; flex-wrap:wrap; gap:0.6rem; }
        .topbar-derecha{ gap:0.8rem; }
    }

    /* ---------- Menú hamburguesa (configuración y opciones) ---------- */
    .menu-opciones{
        position:relative;
    }
    .btn-hamburguesa{
        display:flex;
        align-items:center;
        justify-content:center;
        width:40px;
        height:40px;
        padding:0;
        background:transparent;
        border:1.5px solid rgba(255,255,255,0.3);
        border-radius:0.6rem;
        color:#fff;
        cursor:pointer;
        transition:background 0.15s ease, border-color 0.15s ease;
    }
    .btn-hamburguesa:hover{ background:rgba(255,255,255,0.1); }
    .btn-hamburguesa svg{ display:block; }

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
    .panel-opciones .encabezado-opciones .nombre-usuario{
        display:block;
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:0.95rem;
        color:var(--ink);
    }
    .panel-opciones .encabezado-opciones .correo-usuario{
        display:block;
        font-size:0.78rem;
        color:var(--muted);
        margin-top:0.1rem;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }

    .panel-opciones .opcion{
        display:flex;
        align-items:center;
        gap:0.7rem;
        width:100%;
        padding:0.6rem 0.6rem;
        border:none;
        background:none;
        border-radius:0.6rem;
        font-family:'Inter', sans-serif;
        font-size:0.9rem;
        font-weight:600;
        color:var(--text);
        text-decoration:none;
        cursor:pointer;
        text-align:left;
    }
    .panel-opciones .opcion svg{ flex-shrink:0; color:var(--muted); }
    .panel-opciones .opcion:hover{ background:var(--bg); }
    .panel-opciones .opcion:hover svg{ color:var(--coral-dark); }

    .panel-opciones .separador-opciones{
        border:none;
        border-top:1px solid var(--line);
        margin:0.4rem 0.2rem;
    }

    .panel-opciones .opcion.salir{ color:var(--danger); }
    .panel-opciones .opcion.salir svg{ color:var(--danger); }
    .panel-opciones .opcion.salir:hover{ background:var(--danger-bg); }
</style>
</head>
<body>
<header class="topbar">
    <div class="marca"><span class="punto"></span>Señas</div>
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

                <a href="practica.php" class="opcion" role="menuitem">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="4"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/></svg>
                    Practicar (juegos)
                </a>
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

        <a href="login.php?salir=1">Salir</a>
    </div>
</header>

<div class="contenedor">

    <?php if ($error_bd): ?>
        <div class="aviso-error">
            <strong>No pudimos cargar tus lecciones</strong>
            <?= htmlspecialchars($error_bd) ?>
        </div>
    <?php endif; ?>

    <?php if (!$error_bd): ?>
        <div class="panel-progreso">
            <div class="fila-progreso">
                <span class="etiqueta">Progreso general</span>
                <span class="cifra"><?= $porcentaje ?>%</span>
            </div>
            <div class="barra">
                <div class="barra-relleno" style="width: <?= $porcentaje ?>%"></div>
            </div>
            <p class="sub-progreso"><?= $completadas ?> de <?= $total ?> lecciones completadas.</p>
        </div>

        <?php if (count($lecciones) > 0): ?>
            <input type="text" id="buscador" placeholder="Buscar lección...">

            <div class="grid-lecciones" id="contenedor-lecciones">
                <?php foreach ($lecciones as $l): ?>
                <?php $hecha = in_array($l['id'], $completados_ids); ?>
                <div class="tarjeta tarjeta-leccion">
                    <img src="<?= htmlspecialchars($l['ruta_imagen'] ?: 'img/lecciones/default.jpg') ?>" alt="">
                    <div class="tarjeta-cuerpo">
                        <?php if ($hecha): ?>
                            <span class="insignia">Completada</span>
                        <?php endif; ?>
                        <h3 class="titulo-leccion"><?= htmlspecialchars($l['titulo']) ?></h3>
                        <button class="btn-ver" data-id="<?= $l['id'] ?>">Ver lección</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="estado-vacio">
                <h3>Todavía no hay lecciones cargadas</h3>
                <p>Cuando se agreguen contenidos a "contenidos_educativos", aparecerán aquí.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
    const buscador = document.getElementById('buscador');
    if (buscador) {
        buscador.addEventListener('input', function () {
            const texto = this.value.toLowerCase();
            document.querySelectorAll('.tarjeta-leccion').forEach(function (tarjeta) {
                const titulo = tarjeta.querySelector('.titulo-leccion').textContent.toLowerCase();
                tarjeta.style.display = titulo.includes(texto) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.btn-ver').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.location.href = 'leccion.php?id=' + this.dataset.id;
        });
    });

    // Menú hamburguesa de opciones (configuración, perfil, ayuda, cerrar sesión)
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
</script>
</body>
</html>