<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$lecciones = $pdo->query('SELECT id, titulo, ruta_imagen FROM contenidos_educativos ORDER BY id')->fetchAll();

$total = $pdo->query('SELECT COUNT(*) AS c FROM contenidos_educativos')->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM progreso_usuario WHERE usuario_id = ? AND completado = 1");
$stmt->execute([$usuario_id]);
$completadas = $stmt->fetch()['c'];

$porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;

$stmt = $pdo->prepare("SELECT contenido_id FROM progreso_usuario WHERE usuario_id = ? AND completado = 1");
$stmt->execute([$usuario_id]);
$completados_ids = array_column($stmt->fetchAll(), 'contenido_id');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #ffffff; }
    .navbar { background-color: #0d6efd; }
    .navbar-brand, .nav-link { color: #ffffff !important; }
    .progress { height: 1.5rem; border-radius: 1rem; }
    .progress-bar { background-color: #0d6efd; }
    .card { border: 1px solid #cfe2ff; border-radius: 1rem; transition: transform .15s; }
    .card:hover { transform: translateY(-4px); }
    .card-img-top { height: 160px; object-fit: cover; border-radius: 1rem 1rem 0 0; }
    .badge-completado { background-color: #0d6efd; }
    .btn-primary { background-color: #0d6efd; border: none; }
    #buscador { max-width: 320px; border-radius: 0.6rem; padding: 0.6rem 1rem; }
</style>
</head>
<body>
<nav class="navbar navbar-expand px-4 py-3">
    <span class="navbar-brand fw-bold">Mi Aprendizaje</span>
    <a href="login.php" class="nav-link">Salir</a>
</nav>

<div class="container py-4">
    <div class="mb-4">
        <div class="d-flex justify-content-between mb-1">
            <span class="fw-semibold">Progreso general</span>
            <span class="fw-semibold"><?= $porcentaje ?>%</span>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width: <?= $porcentaje ?>%"></div>
        </div>
    </div>

    <input type="text" id="buscador" class="form-control mb-4" placeholder="Buscar lección...">

    <div class="row g-4" id="contenedor-lecciones">
        <?php foreach ($lecciones as $l): ?>
        <?php $hecha = in_array($l['id'], $completados_ids); ?>
        <div class="col-md-4 tarjeta-leccion">
            <div class="card h-100">
                <img src="<?= htmlspecialchars($l['ruta_imagen']) ?>" class="card-img-top" alt="">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title titulo-leccion"><?= htmlspecialchars($l['titulo']) ?></h5>
                    <?php if ($hecha): ?>
                        <span class="badge badge-completado align-self-start mb-2">Completada</span>
                    <?php endif; ?>
                    <button class="btn btn-primary mt-auto btn-ver" data-id="<?= $l['id'] ?>">Ver lección</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const texto = this.value.toLowerCase();
    document.querySelectorAll('.tarjeta-leccion').forEach(function (tarjeta) {
        const titulo = tarjeta.querySelector('.titulo-leccion').textContent.toLowerCase();
        tarjeta.style.display = titulo.includes(texto) ? '' : 'none';
    });
});

document.querySelectorAll('.btn-ver').forEach(function (btn) {
    btn.addEventListener('click', function () {
        window.location.href = 'leccion.php?id=' + this.dataset.id;
    });
});
</script>
</body>
</html>
