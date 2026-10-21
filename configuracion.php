<?php
// configuracion.php - Panel de Configuraciones de Accesibilidad y Seguridad en SEVA
require 'conexion.php';
require 'seguridad.php';

seguridad_iniciar_sesion();

// Validación estricta de sesión de usuario
seguridad_requiere_login();

$usuario_id = (int) $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$email      = $_SESSION['email'] ?? '';

$mensaje_exito_seguridad = '';
$mensaje_error_seguridad = '';

// Aviso cuando una petición POST llega sin token CSRF válido (D05)
if (csrf_error_en_url()) {
    $mensaje_error_seguridad = 'La solicitud expiró o no es válida. Inténtalo de nuevo.';
}

// Procesamiento del cambio seguro de contraseña
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_password') {
    seguridad_validar_csrf('configuracion.php');

    $pass_actual    = $_POST['pass_actual'] ?? '';
    $pass_nueva     = $_POST['pass_nueva'] ?? '';
    $pass_confirmar = $_POST['pass_confirmar'] ?? '';

    if ($pass_actual === '' || $pass_nueva === '' || $pass_confirmar === '') {
        $mensaje_error_seguridad = 'Todos los campos de contraseña son obligatorios.';
    } elseif (mb_strlen($pass_nueva) < 8) {
        $mensaje_error_seguridad = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass_nueva !== $pass_confirmar) {
        $mensaje_error_seguridad = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif ($pass_actual === $pass_nueva) {
        $mensaje_error_seguridad = 'La nueva contraseña debe ser diferente a la contraseña actual.';
    } else {
        try {
            // Consultar hash actual en la base de datos
            $stmt = $pdo->prepare('SELECT password FROM usuarios WHERE id_usuario = ?');
            $stmt->execute([$usuario_id]);
            $fila = $stmt->fetch();

            if (!$fila || !password_verify($pass_actual, $fila['password'])) {
                $mensaje_error_seguridad = 'La contraseña actual ingresada es incorrecta.';
            } else {
                // Generar hash seguro y actualizar
                $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
                $stmt_up = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
                $stmt_up->execute([$nuevo_hash, $usuario_id]);

                $mensaje_exito_seguridad = '¡Tu contraseña ha sido actualizada exitosamente!';
            }
        } catch (PDOException $e) {
            error_log('Error al cambiar contraseña en configuracion.php: ' . $e->getMessage());
            $mensaje_error_seguridad = 'Ocurrió un error al procesar el cambio. Inténtalo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración de Cuenta · SEVA</title>
    <!-- Fuentes Web Accesibles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Oficiales SEVA -->
    <link rel="stylesheet" href="css/estilos.css">
    <!-- Aplicar preferencias de accesibilidad guardadas antes de pintar el DOM -->
    <script>
        (function() {
            const tamano = localStorage.getItem('seva_tamano_fuente');
            if (tamano === 'mediana') document.documentElement.classList.add('fuente-mediana');
            else if (tamano === 'grande') document.documentElement.classList.add('fuente-grande');
            
            if (localStorage.getItem('seva_reducir_movimiento') === 'true') {
                document.documentElement.classList.add('reducir-movimiento');
            }
        })();
    </script>
</head>
<body>
    <a href="#contenido-principal" class="skip-link">Saltar al contenido principal</a>

    <!-- Topbar Oficial SEVA -->
    <header class="topbar">
        <a href="dashboard.php" class="marca" aria-label="Inicio SEVA - Sistema Educativo de Lengua de Señas">
            <span class="punto" aria-hidden="true"></span>SEVA
        </a>

        <div class="topbar-derecha">
            <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong></span>

            <!-- Menú Desplegable Accesible -->
            <div class="menu-opciones">
                <button type="button" class="btn-hamburguesa" id="btn-opciones" aria-label="Abrir menú de usuario" aria-haspopup="true" aria-expanded="false" aria-controls="panel-opciones">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>

                <div class="panel-opciones" id="panel-opciones" role="menu">
                    <div class="encabezado-opciones">
                        <span class="nombre-usuario"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="correo-usuario"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <a href="perfil.php" class="opcion" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                        Mi perfil
                    </a>
                    <a href="configuracion.php" class="opcion" role="menuitem" aria-current="page">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Configuración de cuenta
                    </a>

                    <hr class="separador-opciones">

                    <a href="logout.php" class="opcion salir" role="menuitem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        Cerrar sesión
                    </a>
                </div>
            </div>

            <a href="logout.php" class="btn-salir-directo" title="Cerrar sesión">Salir</a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main id="contenido-principal" class="contenedor">
        <!-- Migas de Pan -->
        <nav aria-label="Ruta de navegación" class="migas">
            <a href="dashboard.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Inicio
            </a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Configuración de cuenta</span>
        </nav>

        <header style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.2rem; margin: 0 0 0.4rem;">Configuración y Preferencias</h1>
            <p style="color: var(--muted); font-size: 1.05rem; margin: 0;">
                Personaliza la accesibilidad de la interfaz educativa y administra la seguridad de tu acceso.
            </p>
        </header>

        <!-- Región para anuncios en vivo de accesibilidad -->
        <div id="anuncio-accesibilidad" class="visually-hidden" role="status" aria-live="polite"></div>

        <!-- ========================================================
             MÓDULO 1: AJUSTES DE ACCESIBILIDAD (DUA y WCAG 2.1 AA)
             ======================================================== -->
        <section class="modulo-ajuste" aria-labelledby="titulo-modulo-accesibilidad">
            <h2 id="titulo-modulo-accesibilidad">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: var(--azul);"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="7" r="1"/><path d="M12 9v5"/><path d="M9 12h6"/><path d="M10 18l2-4 2 4"/></svg>
                Ajustes de Accesibilidad Visual y Cognitiva
            </h2>
            <p class="descripcion-modulo">
                Adapta el entorno de SEVA a tus necesidades visuales y de confort. Tus preferencias se guardarán en este navegador.
            </p>

            <!-- Control 1: Tamaño de Texto -->
            <div class="control-accesible-fila">
                <div class="info-control-accesible">
                    <h3 id="etiqueta-tamano-fuente">Tamaño de letra</h3>
                    <p>Aumenta el tamaño del texto para mejorar la legibilidad de las lecciones.</p>
                </div>
                <div class="grupo-botones-opcion" role="group" aria-labelledby="etiqueta-tamano-fuente">
                    <button type="button" class="btn-opcion-accesible" id="btn-fuente-normal" data-tamano="normal" aria-pressed="false">
                        Normal (16px)
                    </button>
                    <button type="button" class="btn-opcion-accesible" id="btn-fuente-mediana" data-tamano="mediana" aria-pressed="false">
                        Mediana (18px)
                    </button>
                    <button type="button" class="btn-opcion-accesible" id="btn-fuente-grande" data-tamano="grande" aria-pressed="false">
                        Grande (20px)
                    </button>
                </div>
            </div>

            <!-- Control 2: Reducción de Movimiento -->
            <div class="control-accesible-fila">
                <div class="info-control-accesible">
                    <h3 id="etiqueta-movimiento">Reducción de movimiento</h3>
                    <p id="desc-movimiento">Desactiva transiciones y animaciones visuales para prevenir fatiga o mareos.</p>
                </div>
                <div>
                    <button type="button" class="btn-switch-accesible" id="btn-toggle-movimiento" role="switch" aria-checked="false" aria-describedby="desc-movimiento">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                        <span id="texto-estado-movimiento">Animaciones activadas</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ========================================================
             MÓDULO 2: SEGURIDAD (Cambio Seguro de Contraseña)
             ======================================================== -->
        <section class="modulo-ajuste" aria-labelledby="titulo-modulo-seguridad">
            <h2 id="titulo-modulo-seguridad">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: var(--verde-oscuro);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Seguridad de la Cuenta
            </h2>
            <p class="descripcion-modulo">
                Actualiza periódicamente tu contraseña para proteger tu información de aprendizaje y trayectoria formativa.
            </p>

            <!-- Alertas de estado de seguridad -->
            <?php if (!empty($mensaje_exito_seguridad)): ?>
                <div class="alerta alerta-exito" role="alert" aria-live="polite">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <div><?= htmlspecialchars($mensaje_exito_seguridad, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_error_seguridad)): ?>
                <div class="alerta alerta-error" role="alert" aria-live="polite">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div><?= htmlspecialchars($mensaje_error_seguridad, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="configuracion.php" novalidate style="max-width: 520px;">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="cambiar_password">

                <div class="campo">
                    <label for="pass_actual">Contraseña actual</label>
                    <input type="password" id="pass_actual" name="pass_actual" required autocomplete="current-password" aria-describedby="ayuda-pass-actual">
                    <small id="ayuda-pass-actual" class="ayuda-campo">Ingresa la clave con la que iniciaste sesión.</small>
                </div>

                <div class="campo">
                    <label for="pass_nueva">Nueva contraseña</label>
                    <input type="password" id="pass_nueva" name="pass_nueva" required minlength="8" autocomplete="new-password" aria-describedby="ayuda-pass-nueva">
                    <small id="ayuda-pass-nueva" class="ayuda-campo">Debe contener un mínimo de 8 caracteres.</small>
                </div>

                <div class="campo">
                    <label for="pass_confirmar">Confirmar nueva contraseña</label>
                    <input type="password" id="pass_confirmar" name="pass_confirmar" required minlength="8" autocomplete="new-password" aria-describedby="ayuda-pass-conf">
                    <small id="ayuda-pass-conf" class="ayuda-campo">Escribe nuevamente tu nueva contraseña.</small>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primario">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </section>

        <!-- ========================================================
             MÓDULO 3: SESIÓN Y SALIDA DESTACADA
             ======================================================== -->
        <section class="modulo-ajuste modulo-ajuste-peligro" aria-labelledby="titulo-modulo-sesion">
            <h2 id="titulo-modulo-sesion">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Cerrar Sesión Activa
            </h2>
            <p class="descripcion-modulo" style="margin-bottom: 1.25rem;">
                Si estás utilizando un computador compartido o deseas finalizar tu jornada de estudio, te recomendamos cerrar la sesión para proteger tus avances y datos personales.
            </p>

            <a href="logout.php" class="btn" style="background-color: var(--danger); color: #fff; min-height: 44px; display: inline-flex; align-items: center; gap: 0.6rem; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Cerrar sesión ahora
            </a>
        </section>
    </main>

    <!-- Scripts de Accesibilidad e Interacción -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Menú desplegable accesible de usuario
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
                        btnOpciones.focus();
                    }
                });
            }

            // 2. Control Accesible de Tamaño de Fuente
            const anuncio = document.getElementById('anuncio-accesibilidad');
            const botonesFuente = {
                normal: document.getElementById('btn-fuente-normal'),
                mediana: document.getElementById('btn-fuente-mediana'),
                grande: document.getElementById('btn-fuente-grande')
            };

            function anunciar(mensaje) {
                if (anuncio) {
                    anuncio.textContent = mensaje;
                }
            }

            function sincronizarBotonesFuente(tamano) {
                document.documentElement.classList.remove('fuente-mediana', 'fuente-grande');
                if (tamano === 'mediana') {
                    document.documentElement.classList.add('fuente-mediana');
                } else if (tamano === 'grande') {
                    document.documentElement.classList.add('fuente-grande');
                }

                Object.keys(botonesFuente).forEach(function(key) {
                    const btn = botonesFuente[key];
                    if (btn) {
                        const esActivo = (key === tamano);
                        btn.setAttribute('aria-pressed', esActivo ? 'true' : 'false');
                        if (esActivo) {
                            btn.classList.add('activo');
                        } else {
                            btn.classList.remove('activo');
                        }
                    }
                });
            }

            const tamanoGuardado = localStorage.getItem('seva_tamano_fuente') || 'normal';
            sincronizarBotonesFuente(tamanoGuardado);

            Object.keys(botonesFuente).forEach(function(key) {
                const btn = botonesFuente[key];
                if (btn) {
                    btn.addEventListener('click', function() {
                        localStorage.setItem('seva_tamano_fuente', key);
                        sincronizarBotonesFuente(key);
                        const nombres = { normal: 'Normal', mediana: 'Mediana', grande: 'Grande' };
                        anunciar('Tamaño de letra modificado a ' + nombres[key]);
                    });
                }
            });

            // 3. Control de Reducción de Movimiento
            const btnMovimiento = document.getElementById('btn-toggle-movimiento');
            const textoEstadoMov = document.getElementById('texto-estado-movimiento');

            function sincronizarEstadoMovimiento(activado) {
                if (activado) {
                    document.documentElement.classList.add('reducir-movimiento');
                    btnMovimiento.setAttribute('aria-checked', 'true');
                    btnMovimiento.classList.add('activo');
                    if (textoEstadoMov) textoEstadoMov.textContent = 'Animaciones reducidas (activado)';
                } else {
                    document.documentElement.classList.remove('reducir-movimiento');
                    btnMovimiento.setAttribute('aria-checked', 'false');
                    btnMovimiento.classList.remove('activo');
                    if (textoEstadoMov) textoEstadoMov.textContent = 'Animaciones normales';
                }
            }

            const movimientoGuardado = localStorage.getItem('seva_reducir_movimiento') === 'true';
            sincronizarEstadoMovimiento(movimientoGuardado);

            if (btnMovimiento) {
                btnMovimiento.addEventListener('click', function() {
                    const estadoActual = localStorage.getItem('seva_reducir_movimiento') === 'true';
                    const nuevoEstado = !estadoActual;
                    localStorage.setItem('seva_reducir_movimiento', nuevoEstado ? 'true' : 'false');
                    sincronizarEstadoMovimiento(nuevoEstado);
                    anunciar(nuevoEstado ? 'Reducción de movimiento activada' : 'Reducción de movimiento desactivada');
                });
            }
        });
    </script>
</body>
</html>
