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
</style>
</head>
<body>
<header class="topbar">
    <div class="marca"><span class="punto"></span>Señas</div>
    <div class="topbar-derecha">
        <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre) ?></strong></span>
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
</script>
</body>
</html>