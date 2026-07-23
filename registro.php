<?php
require 'conexion.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === '' || $contrasena === '') {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'El usuario ya existe.';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, contrasena) VALUES (?, ?)');
            $stmt->execute([$usuario, $hash]);
            $exito = 'Registro exitoso. Ya puedes iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }
    .tarjeta {
        width: 100%;
        max-width: 420px;
        border: 1px solid #cfe2ff;
        border-radius: 1rem;
        padding: 2.5rem;
    }
    h1 {
        color: #0d6efd;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .form-control {
        padding: 0.75rem 1rem;
        border-radius: 0.6rem;
    }
    .btn-primary {
        background-color: #0d6efd;
        border: none;
        padding: 0.75rem;
        font-size: 1.1rem;
        border-radius: 0.6rem;
        width: 100%;
    }
    a {
        color: #0d6efd;
    }
</style>
</head>
<body>
<div class="tarjeta">
    <h1>Crear cuenta</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
        <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <form method="POST" action="registro.php">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" name="contrasena" class="form-control" required>
        </div>
        <div class="mb-5">
            <label class="form-label">E-mail</label>
            <input type="password" name="email" class="form-control" required>
        </div>
        <div class="mb-6">
            <label class="form-label">Número de ID</label>
            <input type="password" name="id-usuario" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <p class="text-center mt-3">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </p>
</div>
</body>
</html>
