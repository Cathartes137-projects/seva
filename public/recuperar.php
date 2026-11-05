<?php
require '../config/conexion.php';
require '../config/seguridad.php';

seguridad_iniciar_sesion();

const RECUPERACION_MINUTOS_VIGENCIA = 30;

$error        = '';
$exito        = '';
$email_val    = '';
$enlace_local = '';

$mensaje_generico = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña. '
    . 'Revisa tu bandeja de entrada y la carpeta de spam.';

if (csrf_error_en_url()) {
    $error = 'La solicitud expiró o no es válida. Inténtalo de nuevo.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Toda petición POST exige un token CSRF válido (D05)
    seguridad_validar_csrf('recuperar.php');

    $email     = trim((string) ($_POST['email'] ?? ''));
    $email_val = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    $ip    = seguridad_ip_cliente();
    $clave = seguridad_recuperacion_clave($email);

    // Rate limit independiente del login: mismo comportamiento exista o no la cuenta.
    $segundos_bloqueo = seguridad_login_bloqueo($pdo, $ip, $clave);

    if (honeypot_accionado()) {
        // Bot detectado: respuesta neutra, sin tocar la base de datos
        error_log('SEVA honeypot activado en recuperación desde IP ' . $ip);
        $exito = $mensaje_generico;
    } elseif ($segundos_bloqueo > 0) {
        $minutos = (int) ceil($segundos_bloqueo / 60);
        $error = 'Demasiadas solicitudes. Espera ' . $minutos
            . ' minuto(s) e inténtalo de nuevo.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Introduce un correo electrónico válido.';
    } else {
        // Se contabiliza SIEMPRE (exista o no el correo) para que el límite de
        // solicitudes no revele qué cuentas están registradas.
        seguridad_login_fallo($pdo, $ip, $clave);

        try {
            $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $id_usuario = (int) $usuario['id_usuario'];

                // Un token nuevo invalida los anteriores del mismo usuario
                $stmt_inv = $pdo->prepare(
                    'UPDATE recuperacion_password SET usado = 1 WHERE id_usuario = ? AND usado = 0'
                );
                $stmt_inv->execute([$id_usuario]);

                $token = bin2hex(random_bytes(32));
                $hash  = hash('sha256', $token);

                $stmt_ins = $pdo->prepare(
                    'INSERT INTO recuperacion_password (id_usuario, token_hash, expira)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
                );
                $stmt_ins->execute([$id_usuario, $hash, RECUPERACION_MINUTOS_VIGENCIA]);

                $enlace = seva_url_base() . 'restablecer.php?token=' . $token;

                // En local (sin SMTP) el enlace queda en el log para pruebas
                error_log(
                    'SEVA recuperación de contraseña: enlace generado para el usuario '
                    . $id_usuario . ' -> ' . $enlace
                );

                if (seva_es_local()) {
                    $enlace_local = $enlace;
                }
            } else {
                error_log(
                    'SEVA recuperación de contraseña: solicitud para un correo no registrado desde IP ' . $ip
                );
            }

            // Limpieza oportunista de tokens caducados (≈10 % de las solicitudes)
            if (random_int(1, 10) === 1) {
                $pdo->prepare(
                    'DELETE FROM recuperacion_password
                     WHERE expira < DATE_SUB(NOW(), INTERVAL 1 DAY)
                        OR (usado = 1 AND creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY))'
                )->execute();
            }

            // Respuesta idéntica con o sin cuenta (prevención de enumeración)
            $exito = $mensaje_generico;
        } catch (PDOException $e) {
            error_log('SEVA recuperación de contraseña: error de base de datos: ' . $e->getMessage());
            $error = 'No se pudo procesar la solicitud. Inténtalo de nuevo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña · SEVA</title>
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
        --exito-bg:#E9F7EE;
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
    .alerta-exito{ background:var(--exito-bg); color:var(--verde-oscuro); }

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

    .aviso-local{
        margin-top:1.35rem;
        padding:0.9rem 1rem;
        border:1px dashed var(--azul-seva);
        border-radius:0.7rem;
        background:#F3F8FF;
        font-size:0.85rem;
        color:var(--ink);
        word-break:break-all;
    }
    .aviso-local strong{ display:block; margin-bottom:0.35rem; }
    .aviso-local a{ color:var(--azul-seva); }

    .hp-trampa{
        position:absolute;
        left:-9999px;
        top:auto;
        width:1px;
        height:1px;
        overflow:hidden;
    }
</style>
</head>
<body>
<main class="tarjeta">
    <div class="marca"><span class="punto"></span>SEVA</div>

    <p class="ojo">Recuperación</p>
    <h1>¿Olvidaste tu contraseña?</h1>
    <p class="sub">Escribe el correo de tu cuenta y te daremos un enlace para crear una nueva.</p>

    <?php if ($error !== ''): ?>
        <div class="alerta alerta-error" role="alert" aria-live="assertive">
            <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($exito !== ''): ?>
        <div class="alerta alerta-exito" role="status" aria-live="polite">
            <span><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="recuperar.php" novalidate>
        <?= csrf_campo() ?>
        <?= honeypot_campo() ?>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= $email_val ?>"
                   autocomplete="email" required autofocus>
        </div>

        <button type="submit" class="btn-principal">Enviar enlace de recuperación</button>
    </form>

    <?php if ($enlace_local !== ''): ?>
        <aside class="aviso-local" aria-label="Enlace de prueba del entorno local">
            <strong>Modo local · enlace de prueba (no se envía correo)</strong>
            <a href="<?= htmlspecialchars($enlace_local, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($enlace_local, ENT_QUOTES, 'UTF-8') ?></a>
            <br>Caduca en <?= RECUPERACION_MINUTOS_VIGENCIA ?> minutos y solo puede usarse una vez.
        </aside>
    <?php endif; ?>

    <p class="pie"><a href="login.php">Volver a iniciar sesión</a></p>
</main>
</body>
</html>
