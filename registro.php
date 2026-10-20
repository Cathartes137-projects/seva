<?php
require 'conexion.php';

$error = '';
$exito = false;
$nombre_val = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $contrasena = $_POST['password'] ?? '';
    $confirmar  = $_POST['confirmar'] ?? '';

    // Conservar datos ingresados para evitar reescritura
    $nombre_val = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $email_val  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if ($nombre === '' || $email === '' || $contrasena === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($contrasena) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta registrada con ese correo electrónico.';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$nombre, $email, $hash]);
            $exito = true;
            $nombre_val = '';
            $email_val = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta · SEVA</title>
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
        --success:#1e7e34;
        --success-bg:#E9F8EE;
    }
    *{box-sizing:border-box;}
    body{
        margin:0;
        font-family:'Inter', sans-serif;
        color:var(--text);
        background:var(--paper);
    }
    .layout{
        display:grid;
        grid-template-columns:1fr 1fr;
        min-height:100vh;
    }

    /* ---------- LEFT: illustration panel ---------- */
    .panel-senas{
        position:relative;
        background:radial-gradient(circle at 20% 15%, #0A3D75 0%, var(--ink) 55%, #021A33 100%);
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
        letter-spacing:0.01em;
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
    .escena svg{ width:100%; max-width:340px; height:auto; }

    @keyframes saludo{
        0%,100%{ transform:rotate(0deg); }
        25%{ transform:rotate(-14deg); }
        50%{ transform:rotate(4deg); }
        75%{ transform:rotate(-8deg); }
    }
    .mano-saludo{
        transform-origin:70% 85%;
        animation:saludo 3.2s ease-in-out infinite;
    }
    @media (prefers-reduced-motion: reduce){
        .mano-saludo{ animation:none; }
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
        backdrop-filter:blur(2px);
    }
    .chip-1{ top:8%; left:4%; }
    .chip-2{ top:18%; right:6%; }
    .chip-3{ bottom:12%; left:2%; }

    .alfabeto{
        position:relative;
    }
    .alfabeto p.etiqueta{
        font-family:'Space Mono', monospace;
        font-size:0.72rem;
        letter-spacing:0.14em;
        text-transform:uppercase;
        color:var(--verde-seva);
        margin:0 0 0.6rem;
    }
    .letras{
        display:flex;
        flex-wrap:wrap;
        gap:0.4rem;
    }
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
        background:var(--azul-seva);
        border-color:var(--azul-seva);
        color:#F3F6FF;
    }

    /* ---------- RIGHT: form panel ---------- */
    .panel-form{
        display:flex;
        align-items:center;
        justify-content:center;
        padding:2.5rem 2rem;
        background:var(--paper);
    }
    .tarjeta{ width:100%; max-width:400px; }
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
        display:flex;
        align-items:flex-start;
        gap:0.75rem;
    }
    .alerta.error{
        background:var(--danger-bg);
        color:var(--danger);
        border:1.5px solid rgba(214, 70, 91, 0.35);
    }
    .alerta.exito{
        background:var(--success-bg);
        color:var(--verde-oscuro);
        border:1.5px solid var(--verde-seva);
        flex-direction:column;
        align-items:stretch;
        gap:0.7rem;
        padding:1.2rem;
    }
    .alerta.exito .alerta-header{
        display:flex;
        align-items:center;
        gap:0.6rem;
        font-weight:700;
        font-size:1.05rem;
        color:var(--verde-oscuro);
    }
    .alerta.exito p{
        margin:0;
        font-size:0.95rem;
        line-height:1.4;
        color:var(--text);
    }
    .btn-enlace-login{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:44px;
        padding:0.75rem 1.25rem;
        background:var(--verde-seva);
        color:#ffffff !important;
        font-weight:700;
        font-size:0.95rem;
        text-decoration:none;
        border-radius:0.7rem;
        margin-top:0.4rem;
        transition:background 0.15s ease, transform 0.1s ease;
        text-align:center;
    }
    .btn-enlace-login:hover{
        background:var(--verde-oscuro);
        text-decoration:none;
    }
    .btn-enlace-login:active{
        transform:translateY(1px);
    }
    .btn-enlace-login:focus-visible{
        outline:3px solid var(--azul-seva);
        outline-offset:2px;
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
    .campo small{
        display:block;
        margin-top:0.35rem;
        font-size:0.78rem;
        color:var(--muted);
    }
    .campo small.no-coincide{ color:var(--danger); }

    .btn-crear{
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
        margin-top:0.4rem;
        transition:background 0.15s ease, transform 0.1s ease;
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .btn-crear:hover{ background:var(--azul-oscuro); }
    .btn-crear:active{ transform:translateY(1px); }
    .btn-crear:focus-visible{ outline:3px solid var(--verde-seva); outline-offset:2px; }

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
        .panel-senas{ padding:2.2rem 1.6rem; min-height:340px; }
        .hero-texto h1{ font-size:1.9rem; }
        .escena{ min-height:170px; margin:1rem 0; }
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
            <h1>Cada seña <span>abre</span> una conversación.</h1>
            <p>Únete a la comunidad que aprende y practica Lengua de Señas todos los días, un gesto a la vez.</p>
        </div>

        <div class="escena">
            <span class="chip-flotante chip-1">HOLA</span>
            <span class="chip-flotante chip-2">GRACIAS</span>
            <span class="chip-flotante chip-3">AMIGO</span>

            <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
                <circle cx="150" cy="150" r="128" fill="rgba(255,255,255,0.05)"/>
                <circle cx="150" cy="150" r="98" fill="rgba(255,255,255,0.04)"/>

                <!-- mano saludando -->
                <g class="mano-saludo">
                    <path d="M120 230 C110 200 108 175 112 150 C114 138 122 132 130 134 C136 136 138 143 137 150 L136 120 C136 110 144 103 152 104 C160 105 165 113 165 122 L166 112 C167 103 175 96 183 98 C191 100 196 108 195 117 L197 128 C198 120 206 114 214 117 C221 120 225 128 223 136 L215 178 C210 205 198 224 180 234 C160 245 136 246 120 230 Z"
                          fill="#FBEFE4" stroke="#0056b3" stroke-width="2"/>
                </g>

                <!-- segunda mano, forma de "amigo" apoyando -->
                <path d="M180 260 C170 250 168 238 174 228 C182 216 200 214 212 222 C222 229 226 240 222 250 C216 262 196 268 180 260 Z"
                      fill="#D9ECFF" stroke="#0056b3" stroke-width="2" opacity="0.9"/>

                <!-- líneas de movimiento -->
                <path d="M60 90 Q75 80 90 90" stroke="#28a745" stroke-width="3" fill="none" stroke-linecap="round"/>
                <path d="M55 110 Q72 98 88 110" stroke="#28a745" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.8"/>
            </svg>
        </div>

        <div class="alfabeto">
            <p class="etiqueta">Se deletrea así en el alfabeto dactilológico</p>
            <div class="letras">
                <span class="activa">H</span><span class="activa">O</span><span class="activa">L</span><span class="activa">A</span>
                <span>B</span><span>I</span><span>E</span><span>N</span><span>V</span><span>E</span><span>N</span><span>I</span><span>D</span><span>O</span>
            </div>
        </div>
    </div>

    <!-- ================= DERECHA: formulario ================= -->
    <div class="panel-form">
        <div class="tarjeta">
            <p class="ojo">Registro</p>
            <h2>Crea tu cuenta</h2>
            <p class="sub">Empieza a aprender lengua de señas hoy mismo.</p>

            <?php if ($error): ?>
                <div class="alerta error" role="alert" aria-live="assertive">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0; margin-top:1px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="alerta exito" role="status" aria-live="polite">
                    <div class="alerta-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>¡Registro completado con éxito!</span>
                    </div>
                    <p>Redirigiendo al inicio de sesión en 3 segundos...</p>
                    <a href="login.php" class="btn-enlace-login" id="btn-login-manual">Ir al inicio de sesión ahora</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="registro.php" id="form-registro" novalidate>
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?= $nombre_val ?>" required>
                </div>

                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?= $email_val ?>" required>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                    <small>Mínimo 8 caracteres.</small>
                </div>

                <div class="campo">
                    <label for="confirmar">Confirmar contraseña</label>
                    <input type="password" id="confirmar" name="confirmar" minlength="8" required>
                    <small id="mensaje-confirmar"></small>
                </div>

                <button type="submit" class="btn-crear">Crear cuenta</button>
            </form>

            <p class="pie">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
</div>

<script>
    const pass = document.getElementById('password');
    const confirmar = document.getElementById('confirmar');
    const mensaje = document.getElementById('mensaje-confirmar');
    const form = document.getElementById('form-registro');

    function validarCoincidencia(){
        if (confirmar.value.length === 0){
            mensaje.textContent = '';
            mensaje.classList.remove('no-coincide');
            return true;
        }
        if (pass.value !== confirmar.value){
            mensaje.textContent = 'Las contraseñas no coinciden.';
            mensaje.classList.add('no-coincide');
            return false;
        }
        mensaje.textContent = 'Coinciden.';
        mensaje.classList.remove('no-coincide');
        return true;
    }

    pass.addEventListener('input', validarCoincidencia);
    confirmar.addEventListener('input', validarCoincidencia);

    form.addEventListener('submit', function(e){
        if (!validarCoincidencia()){
            e.preventDefault();
        }
    });

    <?php if ($exito): ?>
    setTimeout(function(){
        window.location.href = 'login.php';
    }, 3000);
    <?php endif; ?>
</script>
</body>
</html>
