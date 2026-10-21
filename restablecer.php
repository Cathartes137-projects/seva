<?php
require 'conexion.php';
require 'seguridad.php';

seguridad_iniciar_sesion();

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$token = trim($token);

$error          = '';
$enlace_valido  = false;
$motivo_invalido = '';

$fila_token = null;

if (preg_match('/^[a-f0-9]{64}$/i', $token) === 1) {
    try {
        // La vigencia se compara en SQL para evitar desajustes de zona horaria
        $stmt = $pdo->prepare(
            'SELECT id, id_usuario, usado, TIMESTAMPDIFF(SECOND, NOW(), expira) AS restante
             FROM recuperacion_password
             WHERE token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $fila_token = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('SEVA restablecer contraseña: error de base de datos: ' . $e->getMessage());
        $motivo_invalido = 'No pudimos verificar el enlace en este momento. Inténtalo de nuevo más tarde.';
    }
}

if ($fila_token) {
    if ((int) $fila_token['usado'] === 1) {
        $motivo_invalido = 'Este enlace ya fue utilizado. Solicita uno nuevo para continuar.';
    } elseif ((int) $fila_token['restante'] <= 0) {
        $motivo_invalido = 'Este enlace ha caducado. Los enlaces son válidos durante 30 minutos.';
    } else {
        $enlace_valido = true;
    }
} elseif ($motivo_invalido === '') {
    $motivo_invalido = 'El enlace no es válido. Comprueba que lo copiaste completo o solicita uno nuevo.';
}

// -----------------------------------------------------------------------------
// Procesar el formulario
// -----------------------------------------------------------------------------
if ($enlace_valido && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_es_valido()) {
        $error = 'La solicitud expiró o no es válida. Inténtalo de nuevo.';
    } else {
        $contrasena = (string) ($_POST['password'] ?? '');
        $confirmar  = (string) ($_POST['password_confirm'] ?? '');

        if ($contrasena === '' || $confirmar === '') {
            $error = 'Todos los campos son obligatorios.';
        } elseif (strlen($contrasena) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($contrasena !== $confirmar) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt_clave = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
                $stmt_clave->execute([
                    password_hash($contrasena, PASSWORD_DEFAULT),
                    (int) $fila_token['id_usuario'],
                ]);

                // Consume el token actual e invalida cualquier otro pendiente
                $stmt_usado = $pdo->prepare(
                    'UPDATE recuperacion_password SET usado = 1 WHERE id_usuario = ? AND usado = 0'
                );
                $stmt_usado->execute([(int) $fila_token['id_usuario']]);

                $pdo->prepare(
                    'DELETE FROM recuperacion_password
                     WHERE expira < DATE_SUB(NOW(), INTERVAL 1 DAY)
                        OR (usado = 1 AND creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY))'
                )->execute();

                $pdo->commit();

                // La contraseña cambió: se cierra la sesión actual por precaución
                seguridad_cerrar_sesion();

                if (!headers_sent()) {
                    header('Location: login.php?recuperado=1');
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('SEVA restablecer contraseña: error al guardar: ' . $e->getMessage());
                $error = 'No se pudo actualizar la contraseña. Inténtalo de nuevo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Restablecer contraseña · SEVA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root{
        --azul-seva:#0056b3;
        --azul-oscuro:#003d80;
        --verde-seva:#28a745;
        --verde-oscuro:#1e7e34;
        --ink:#04294d;
        --paper:#FFFFFF;
        --text:#1B1F3B;
        --muted:#6B7690;
        --line:#E4EAF3;
        --danger:#D6465B;
        --danger-bg:#FDECEF;
    }
    *{ box-sizing:border-box; }
    body{
        margin:0;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:2rem 1.25rem;
        font-family:'Inter', sans-serif;
        color:var(--text);
        background:
            radial-gradient(circle at 15% 10%, #0A3D75 0%, var(--ink) 45%, #021A33 100%);
    }
    .tarjeta{
        width:100%;
        max-width:440px;
        background:var(--paper);
        border-radius:1.2rem;
        padding:2.4rem 2rem;
        box-shadow:0 24px 60px rgba(2, 16, 33, 0.35);
    }
    .marca{
        display:flex;
        align-items:center;
        gap:0.6rem;
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:1.25rem;
        color:var(--ink);
        margin-bottom:1.6rem;
    }
    .marca .punto{
        width:10px; height:10px; border-radius:50%;
        background:var(--verde-seva);
        display:inline-block;
    }
    .ojo{
        font-family:'Space Mono', monospace;
        font-size:0.72rem;
        font-weight:700;
        letter-spacing:0.16em;
        text-transform:uppercase;
        color:var(--azul-seva);
        margin:0 0 0.4rem;
    }
    h1{
        font-family:'Baloo 2', sans-serif;
        font-size:1.9rem;
        line-height:1.15;
        margin:0 0 0.35rem;
        color:var(--ink);
    }
    .sub{ color:var(--muted); font-size:0.95rem; margin:0 0 1.6rem; }

    .alerta{
        display:flex;
        gap:0.6rem;
        padding:0.85rem 1rem;
        border-radius:0.7rem;
        font-size:0.9rem;
        margin-bottom:1.3rem;
        line-height:1.4;
    }
    .alerta-error{ background:var(--danger-bg); color:var(--danger); }

    .campo{ margin-bottom:1.15rem; }
    .campo label{
        display:block;
        font-size:0.85rem;
        font-weight:600;
        color:var(--ink);
        margin-bottom:0.4rem;
    }
    .campo input{
        width:100%;
        padding:0.75rem 0.95rem;
        border:1.5px solid var(--line);
        border-radius:0.7rem;
        font-family:'Inter', sans-serif;
        font-size:0.95rem;
        color:var(--text);
        background:#FBFCFF;
        transition:border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .campo input:focus{
        outline:none;
        border-color:var(--azul-seva);
        box-shadow:0 0 0 4px rgba(0,86,179,0.14);
    }
    .ayuda{
        font-size:0.8rem;
        color:var(--muted);
        margin:0.35rem 0 0;
    }
    .btn-principal{
        width:100%;
        min-height:44px;
        padding:0.85rem;
        border:none;
        border-radius:0.7rem;
        background:var(--azul-seva);
        color:#fff;
        font-size:1rem;
        font-weight:700;
        font-family:'Inter', sans-serif;
        cursor:pointer;
        transition:background 0.15s ease, transform 0.1s ease;
    }
    .btn-principal:hover{ background:var(--azul-oscuro); }
    .btn-principal:active{ transform:translateY(1px); }
    .btn-principal:focus-visible{ outline:3px solid var(--verde-seva); outline-offset:2px; }

    .pie{
        text-align:center;
        margin-top:1.5rem;
        font-size:0.9rem;
        color:var(--muted);
    }
    .pie a{ color:var(--verde-oscuro); font-weight:600; text-decoration:none; }
    .pie a:hover{ text-decoration:underline; }
</style>
</head>
<body>
<main class="tarjeta">
    <div class="marca"><span class="punto"></span>SEVA</div>

    <?php if (!$enlace_valido): ?>
        <p class="ojo">Enlace no disponible</p>
        <h1>No podemos usar este enlace</h1>
        <p class="sub"><?= htmlspecialchars($motivo_invalido, ENT_QUOTES, 'UTF-8') ?></p>

        <a class="btn-principal" href="recuperar.php"
           style="display:block; text-align:center; text-decoration:none; line-height:1.4;">
            Solicitar un enlace nuevo
        </a>

        <p class="pie"><a href="login.php">Volver a iniciar sesión</a></p>
    <?php else: ?>
        <p class="ojo">Nueva contraseña</p>
        <h1>Crea una contraseña nueva</h1>
        <p class="sub">Al guardarla, el enlace dejará de funcionar y deberás iniciar sesión de nuevo.</p>

        <?php if ($error !== ''): ?>
            <div class="alerta alerta-error" role="alert" aria-live="assertive">
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="restablecer.php" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="campo">
                <label for="password">Contraseña nueva</label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" minlength="8" required autofocus
                       aria-describedby="ayuda-password">
                <p class="ayuda" id="ayuda-password">Mínimo 8 caracteres.</p>
            </div>

            <div class="campo">
                <label for="password_confirm">Repite la contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       autocomplete="new-password" minlength="8" required>
            </div>

            <button type="submit" class="btn-principal">Guardar contraseña</button>
        </form>

        <p class="pie"><a href="login.php">Cancelar y volver al inicio de sesión</a></p>
    <?php endif; ?>
</main>
</body>
</html>
