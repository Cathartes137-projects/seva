<?php
// perfil.php - Gestión del Perfil del Estudiante en SEVA
require '../config/conexion.php';
require '../config/seguridad.php';

seguridad_iniciar_sesion();

// Validación estricta de sesión de usuario
seguridad_requiere_login();

$usuario_id = (int) $_SESSION['usuario_id'];
$mensaje_exito = '';
$mensaje_error = '';

// Aviso cuando una petición POST llega sin token CSRF válido (D05)
if (csrf_error_en_url()) {
    $mensaje_error = 'La solicitud expiró o no es válida. Inténtalo de nuevo.';
}

// Procesamiento de actualización de datos personales (Nombre)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_nombre') {
    seguridad_validar_csrf('perfil.php');

    $nuevo_nombre = trim($_POST['nombre'] ?? '');

    if (mb_strlen($nuevo_nombre, 'UTF-8') < 3) {
        $mensaje_error = 'El nombre de usuario debe contener al menos 3 caracteres.';
    } elseif (mb_strlen($nuevo_nombre, 'UTF-8') > 100) {
        $mensaje_error = 'El nombre no puede superar los 100 caracteres.';
    } else {
        try {
            $stmt_update = $pdo->prepare('UPDATE usuarios SET nombre = ? WHERE id_usuario = ?');
            $stmt_update->execute([$nuevo_nombre, $usuario_id]);

            // Sincronizar variable de sesión activa
            $_SESSION['nombre'] = $nuevo_nombre;
            $mensaje_exito = '¡Tus datos de perfil se actualizaron con éxito!';
        } catch (PDOException $e) {
            error_log('Error al actualizar nombre en perfil.php: ' . $e->getMessage());
            $mensaje_error = 'Ocurrió un error al guardar los cambios. Inténtalo de nuevo más tarde.';
        }
    }
}

// Consultar datos actualizados del usuario
try {
    $stmt_user = $pdo->prepare('SELECT id_usuario, nombre, email, fecha_registro FROM usuarios WHERE id_usuario = ?');
    $stmt_user->execute([$usuario_id]);
    $usuario = $stmt_user->fetch();

    if (!$usuario) {
        header('Location: logout.php');
        exit;
    }

    $nombre    = $usuario['nombre'];
    $email     = $usuario['email'];
    $fecha_reg = $usuario['fecha_registro'];

    // Consultas de estadísticas educativas
    $stmt_total = $pdo->query('SELECT COUNT(*) AS total FROM contenidos_educativos');
    $total_lecciones = (int) ($stmt_total->fetch()['total'] ?? 0);

    $stmt_comp = $pdo->prepare('SELECT COUNT(*) AS completadas FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_comp->execute([$usuario_id]);
    $lecciones_completadas = (int) ($stmt_comp->fetch()['completadas'] ?? 0);

    $porcentaje_avance = $total_lecciones > 0 ? round(($lecciones_completadas / $total_lecciones) * 100) : 0;

    // Cálculo dinámico del nivel del estudiante
    if ($lecciones_completadas >= 9) {
        $nivel_actual = 'Avanzado';
        $clase_nivel = 'nivel-avanzado';
        $icono_color = 'morado';
    } elseif ($lecciones_completadas >= 5) {
        $nivel_actual = 'Intermedio';
        $clase_nivel = 'nivel-intermedio';
        $icono_color = 'azul';
    } else {
        $nivel_actual = 'Básico';
        $clase_nivel = 'nivel-basico';
        $icono_color = 'verde';
    }

    // Inicial del avatar
    $inicial_avatar = mb_strtoupper(mb_substr(trim($nombre), 0, 1, 'UTF-8'), 'UTF-8');

    // Formato amigable de fecha
    $fecha_formateada = date('d/m/Y', strtotime($fecha_reg));
} catch (PDOException $e) {
    error_log('Error al cargar datos en perfil.php: ' . $e->getMessage());
    $mensaje_error = 'No se pudieron recuperar las estadísticas de tu perfil.';
    $total_lecciones = 0;
    $lecciones_completadas = 0;
    $porcentaje_avance = 0;
    $nivel_actual = 'Básico';
    $clase_nivel = 'nivel-basico';
    $icono_color = 'verde';
    $inicial_avatar = 'E';
    $fecha_formateada = '--/--/----';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil · SEVA</title>
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

                    <a href="perfil.php" class="opcion" role="menuitem" aria-current="page">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                        Mi perfil
                    </a>
                    <a href="configuracion.php" class="opcion" role="menuitem">
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
            <span aria-current="page">Mi Perfil</span>
        </nav>

        <!-- Mensajes de Estado Accesibles -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alerta alerta-exito" role="alert" aria-live="polite">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div><?= htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alerta alerta-error" role="alert" aria-live="polite">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Tarjeta de Identidad del Usuario -->
        <section class="cabecera-perfil" aria-label="Información del estudiante">
            <div class="avatar-perfil" aria-hidden="true">
                <?= htmlspecialchars($inicial_avatar, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="datos-perfil-texto">
                <h1><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="correo-perfil"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                <span class="badge-antiguedad">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Estudiante activo desde el <?= htmlspecialchars($fecha_formateada, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </section>

        <!-- Sección de Estadísticas de Aprendizaje -->
        <section aria-labelledby="titulo-estadisticas">
            <h2 id="titulo-estadisticas" class="visually-hidden">Tus Estadísticas de Aprendizaje</h2>
            <div class="grid-estadisticas">
                <!-- Tarjeta 1: Lecciones Completadas -->
                <article class="tarjeta-stat" aria-label="Lecciones completadas: <?= $lecciones_completadas ?> de <?= $total_lecciones ?>">
                    <div class="tarjeta-stat-icono azul" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    <div class="tarjeta-stat-cuerpo">
                        <h2>Lecciones Superadas</h2>
                        <div class="stat-valor"><?= $lecciones_completadas ?> <span style="font-size: 1rem; color: var(--muted); font-weight: 500;">/ <?= $total_lecciones ?></span></div>
                        <div class="stat-meta">Objetivos completados</div>
                    </div>
                </article>

                <!-- Tarjeta 2: Porcentaje de Avance -->
                <article class="tarjeta-stat" aria-label="Porcentaje de avance total: <?= $porcentaje_avance ?> por ciento">
                    <div class="tarjeta-stat-icono verde" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="tarjeta-stat-cuerpo">
                        <h2>Avance General</h2>
                        <div class="stat-valor"><?= $porcentaje_avance ?>%</div>
                        <div class="stat-meta">Progreso en la plataforma</div>
                    </div>
                </article>

                <!-- Tarjeta 3: Nivel Actual -->
                <article class="tarjeta-stat" aria-label="Nivel actual alcanzado: <?= htmlspecialchars($nivel_actual, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="tarjeta-stat-icono <?= $icono_color ?>" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <div class="tarjeta-stat-cuerpo">
                        <h2>Nivel Actual</h2>
                        <div class="stat-valor">
                            <span class="insignia-nivel <?= $clase_nivel ?>" style="position: static; display: inline-block;">
                                <?= htmlspecialchars($nivel_actual, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <div class="stat-meta">Ruta de Lengua de Señas</div>
                    </div>
                </article>
            </div>
        </section>

        <!-- Formulario de Actualización de Datos Personales -->
        <section class="tarjeta-panel" aria-labelledby="titulo-datos-personales">
            <h2 id="titulo-datos-personales" style="font-size: 1.45rem; margin-bottom: 0.5rem;">Editar Datos de Perfil</h2>
            <p style="color: var(--muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                Mantén actualizada la información de tu cuenta para tus reportes de avance y certificados de logro.
            </p>

            <form method="POST" action="perfil.php" novalidate>
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="actualizar_nombre">

                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" required minlength="3" maxlength="100" autocomplete="name" aria-describedby="ayuda-nombre">
                    <small id="ayuda-nombre" class="ayuda-campo">Este nombre se mostrará en tu saludo, tarjetas de avance y reportes.</small>
                </div>

                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" readonly disabled aria-describedby="ayuda-email">
                    <small id="ayuda-email" class="ayuda-campo">El correo electrónico está vinculado a tu autenticación institucional y no puede modificarse directamente.</small>
                </div>

                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1.75rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primario">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Guardar cambios
                    </button>
                    <a href="dashboard.php" class="btn btn-contorno">
                        Volver al camino
                    </a>
                </div>
            </form>
        </section>
    </main>

    <!-- Script Accesible de Menú Desplegable -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
</body>
</html>
