<?php
require 'conexion.php';
require 'seguridad.php';

seguridad_iniciar_sesion();

// Si el usuario ya está autenticado, redirigir directamente al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email_val = '';

if (csrf_error_en_url()) {
    $error = 'La solicitud expiró o no es válida. Inténtalo de nuevo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toda petición POST exige un token CSRF válido (D05)
    seguridad_validar_csrf('login.php');

    $email      = trim($_POST['email'] ?? '');
    $contrasena = $_POST['password'] ?? '';
    $email_val  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    $ip_cliente = seguridad_ip_cliente();
    $segundos_bloqueo = seguridad_login_bloqueo($pdo, $ip_cliente, $email);

    if (honeypot_accionado()) {
        // Bot detectado: respuesta neutra idéntica a un fallo real, sin tocar la BD
        error_log('SEVA honeypot activado en login desde IP ' . $ip_cliente);
        $error = 'Correo o contraseña incorrectos.';
    } elseif ($segundos_bloqueo > 0) {
        // Límite superado: mensaje neutro, sin revelar si la cuenta existe
        $minutos = (int) ceil($segundos_bloqueo / 60);
        $error = 'Demasiados intentos fallidos. Espera ' . $minutos
            . ' minuto(s) e inténtalo de nuevo.';
    } elseif ($email === '' || $contrasena === '') {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare('SELECT id_usuario, nombre, email, password FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $fila = $stmt->fetch();

        if ($fila && password_verify($contrasena, $fila['password'])) {
            // Login correcto: limpiar el contador de intentos (D09)
            seguridad_login_exito($pdo, $ip_cliente, $email);

            // Actualiza el hash si el algoritmo o el coste cambiaron (D07)
            seguridad_rehash_password($pdo, (int) $fila['id_usuario'], $contrasena, $fila['password']);

            // Previene la fijación de sesión
            seguridad_regenerar_sesion();

            $_SESSION['usuario_id'] = $fila['id_usuario'];
            $_SESSION['nombre']     = $fila['nombre'];
            $_SESSION['email']      = $fila['email'];
            if (!headers_sent()) {
                header('Location: dashboard.php');
                exit;
            }
        } else {
            // Fallo de autenticación: contar el intento (D09)
            seguridad_login_fallo($pdo, $ip_cliente, $email);
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · SEVA</title>
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
    *{box-sizing:border-box;}
    body{
        margin:0;
        font-family:'Inter', sans-serif;
        color:var(--text);
        background:var(--paper);
    }
    /* Campo trampa anti-bots: fuera de pantalla, invisible para personas
       y lectores de pantalla (D10). */
    .hp-trampa{
        position:absolute;
        left:-9999px;
        top:auto;
        width:1px;
        height:1px;
        overflow:hidden;
    }
    .layout{
        display:grid;
        grid-template-columns:1fr 1fr;
        min-height:100vh;
    }

    /* ---------- IZQUIERDA: ilustración ---------- */
    .panel-senas{
        position:relative;
        background:radial-gradient(circle at 80% 20%, #0A3D75 0%, var(--ink) 55%, #021A33 100%);
        color:#F3F6FF;
        padding:3.5rem 3rem;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        overflow:hidden;
    }
    .panel-senas::before{
        content:"";
        position:absolute;
        inset:0;
        background-image:radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
        background-size:26px 26px;
        opacity:0.5;
    }
    .marca{
        position:relative;
        display:flex;
        align-items:center;
        gap:0.6rem;
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:1.25rem;
    }
    .marca .punto{
        width:10px;height:10px;border-radius:50%;
        background:var(--verde-seva);
        display:inline-block;
    }
    .hero-texto{
        position:relative;
        max-width:420px;
        margin-top:1rem;
    }
    .hero-texto h1{
        font-family:'Baloo 2', sans-serif;
        font-weight:800;
        font-size:2.5rem;
        line-height:1.12;
        margin:0 0 0.9rem;
    }
    .hero-texto h1 span{color:var(--verde-seva);}
    .hero-texto p{
        font-size:1rem;
        color:#C9D6EC;
        line-height:1.55;
        margin:0;
    }

    .escena{
        position:relative;
        flex:1;
        display:flex;
        align-items:center;
        justify-content:center;
        min-height:260px;
        margin:1.5rem 0;
    }
    .escena svg{ width:100%; max-width:320px; height:auto; }

    @keyframes senaOK{
        0%,100%{ transform:rotate(0deg) translateY(0); }
        50%{ transform:rotate(-6deg) translateY(-4px); }
    }
    .mano-ok{
        transform-origin:50% 60%;
        animation:senaOK 2.8s ease-in-out infinite;
    }
    @media (prefers-reduced-motion: reduce){
        .mano-ok{ animation:none; }
    }

    .chip-flotante{
        position:absolute;
        font-family:'Space Mono', monospace;
        font-size:0.75rem;
        font-weight:700;
        background:rgba(255,255,255,0.08);
        border:1px solid rgba(255,255,255,0.18);
        color:#F3F6FF;
        padding:0.35rem 0.6rem;
        border-radius:0.5rem;
    }
    .chip-1{ top:10%; left:6%; }
    .chip-2{ bottom:14%; right:4%; }

    .alfabeto p.etiqueta{
        font-family:'Space Mono', monospace;
        font-size:0.72rem;
        letter-spacing:0.14em;
        text-transform:uppercase;
        color:var(--verde-seva);
        margin:0 0 0.6rem;
    }
    .letras{ display:flex; flex-wrap:wrap; gap:0.4rem; }
    .letras span{
        width:34px; height:34px;
        display:flex; align-items:center; justify-content:center;
        font-family:'Space Mono', monospace;
        font-weight:700;
        font-size:0.85rem;
        border-radius:0.5rem;
        background:rgba(255,255,255,0.07);
        border:1px solid rgba(255,255,255,0.14);
    }
    .letras span.activa{
        background:var(--verde-seva);
        border-color:var(--verde-seva);
        color:#04294d;
    }

    /* ---------- DERECHA: formulario ---------- */
    .panel-form{
        display:flex;
        align-items:center;
        justify-content:center;
        padding:2.5rem 2rem;
        background:var(--paper);
    }
    .tarjeta{ width:100%; max-width:380px; }
    .ojo{
        font-family:'Space Mono', monospace;
        font-size:0.72rem;
        font-weight:700;
        letter-spacing:0.16em;
        text-transform:uppercase;
        color:var(--azul-seva);
        margin:0 0 0.5rem;
    }
    .tarjeta h2{
        font-family:'Baloo 2', sans-serif;
        font-weight:700;
        font-size:2rem;
        margin:0 0 0.35rem;
        color:var(--ink);
    }
    .tarjeta p.sub{
        color:var(--muted);
        margin:0 0 1.8rem;
        font-size:0.95rem;
    }
    .alerta{
        padding:0.9rem 1.1rem;
        border-radius:0.7rem;
        font-size:0.92rem;
        margin-bottom:1.3rem;
        background:var(--danger-bg);
        color:var(--danger);
        border:1.5px solid rgba(214, 70, 91, 0.35);
        display:flex;
        align-items:flex-start;
        gap:0.75rem;
    }

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
        min-height:44px;
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
        box-shadow:0 0 0 3px rgba(0,86,179,0.18);
    }
    .campo input:focus-visible{
        outline:3px solid var(--azul-seva);
        outline-offset:2px;
    }

    .fila-extra{
        display:flex;
        justify-content:flex-end;
        margin:-0.4rem 0 1.2rem;
    }
    .fila-extra a{
        font-size:0.85rem;
        color:var(--muted);
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        min-height:44px;
        padding:0.25rem 0.5rem;
        border-radius:0.4rem;
    }
    .fila-extra a:hover{ color:var(--azul-oscuro); text-decoration:underline; }
    .fila-extra a:focus-visible{
        outline:3px solid var(--azul-seva);
        outline-offset:2px;
    }

    .btn-entrar{
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
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .btn-entrar:hover{ background:var(--azul-oscuro); }
    .btn-entrar:active{ transform:translateY(1px); }
    .btn-entrar:focus-visible{ outline:3px solid var(--verde-seva); outline-offset:2px; }

    .pie{
        text-align:center;
        margin-top:1.5rem;
        font-size:0.9rem;
        color:var(--muted);
    }
    .pie a{
        color:var(--verde-oscuro);
        font-weight:600;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        min-height:44px;
        padding:0.25rem 0.5rem;
        border-radius:0.4rem;
    }
    .pie a:hover{ text-decoration:underline; }
    .pie a:focus-visible{
        outline:3px solid var(--azul-seva);
        outline-offset:2px;
    }

    @media (max-width: 860px){
        .layout{ grid-template-columns:1fr; }
        .panel-senas{ padding:2.2rem 1.6rem; min-height:320px; }
        .hero-texto h1{ font-size:1.9rem; }
        .escena{ min-height:160px; margin:1rem 0; }
        .chip-flotante{ display:none; }
        .panel-form{ padding:2.2rem 1.4rem 3rem; }
    }
</style>
</head>
<body>
<div class="layout">

    <!-- ================= IZQUIERDA: ilustración ================= -->
    <div class="panel-senas">
        <div class="marca"><span class="punto"></span>SEVA</div>

        <div class="hero-texto">
            <h1>Qué bueno <span>verte</span> otra vez.</h1>
            <p>Retoma tus lecciones de Lengua de Señas justo donde las dejaste.</p>
        </div>

        <div class="escena">
            <span class="chip-flotante chip-1">BIENVENIDO</span>
            <span class="chip-flotante chip-2">DE NUEVO</span>

            <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
                <circle cx="150" cy="150" r="128" fill="rgba(255,255,255,0.05)"/>
                <circle cx="150" cy="150" r="98" fill="rgba(255,255,255,0.04)"/>

                <!-- mano en seña de "correcto / OK" -->
                <g class="mano-ok">
                    <path d="M110 200 C100 175 98 150 106 128 C110 118 120 114 127 119 C132 123 132 130 129 137 L128 108 C128 99 136 93 144 95 C151 97 155 105 154 113 L155 100 C156 91 165 85 173 88 C180 91 184 99 182 107 L183 96 C185 88 194 83 201 87 C208 91 211 99 208 107 L205 145 C202 172 190 192 172 203 C153 214 128 213 110 200 Z"
                          fill="#FBEFE4" stroke="#28a745" stroke-width="2"/>
                    <circle cx="150" cy="210" r="10" fill="none" stroke="#28a745" stroke-width="3"/>
                    <path d="M144 210 L149 216 L158 203" stroke="#28a745" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </g>

                <path d="M70 90 Q85 78 100 90" stroke="#28a745" stroke-width="3" fill="none" stroke-linecap="round"/>
                <path d="M60 112 Q77 98 94 112" stroke="#0056b3" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.8"/>
            </svg>
        </div>

        <div class="alfabeto">
            <p class="etiqueta">Recuerda tu seña del día</p>
            <div class="letras">
                <span class="activa">O</span><span class="activa">T</span><span class="activa">R</span><span class="activa">A</span>
                <span>V</span><span>E</span><span>Z</span>
            </div>
        </div>
    </div>

    <!-- ================= DERECHA: formulario ================= -->
    <div class="panel-form">
        <div class="tarjeta">
            <p class="ojo">Acceso</p>
            <h2>Inicia sesión</h2>
            <p class="sub">Ingresa con tu correo y contraseña.</p>

            <?php if ($error): ?>
                <div class="alerta" role="alert" aria-live="assertive">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0; margin-top:1px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <?= csrf_campo() ?>
                <?= honeypot_campo() ?>
                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?= $email_val ?>" required autofocus>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="fila-extra">
                    <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-entrar">Entrar</button>
            </form>

            <p class="pie">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
        </div>
    </div>
</div>
</body>
</html>
