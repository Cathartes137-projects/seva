<?php
/*
 * Barra superior compartida para las páginas autenticadas.
 * Reutiliza la sesión ya validada por seguridad.php.
 * $base: prefijo de ruta respecto a la raíz del proyecto ('' o '../').
 */
$nombre_tb = $nombre ?? 'Estudiante';
$email_tb  = $_SESSION['email'] ?? '';
$base_tb   = $base ?? '';
$script_tb = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

$marcar_actual = static function (string $archivo) use ($script_tb): string {
    return $script_tb === $archivo ? ' aria-current="page"' : '';
};
?>
<a href="#contenido" class="skip-link">Saltar al contenido</a>
<header class="topbar">
    <a href="<?= $base_tb ?>dashboard.php" class="marca" aria-label="SEVA, ir a mi camino de aprendizaje">
        <span class="punto" aria-hidden="true"></span>SEVA
    </a>

    <div class="topbar-derecha">
        <span class="chip chip-racha" title="Días seguidos practicando (guardado en este dispositivo)">
            <span aria-hidden="true">🔥</span><b data-racha>0</b>
            <span class="visually-hidden">días de racha</span>
        </span>
        <span class="chip" title="Puntos de experiencia guardados en este dispositivo">
            <span aria-hidden="true">⭐</span><b data-xp>0</b> XP
        </span>

        <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre_tb) ?></strong></span>

        <nav class="topbar-accesos" aria-label="Accesos rápidos">
            <a href="<?= $base_tb ?>practica.php" class="acceso-topbar" aria-label="Practicar con juegos"<?= $marcar_actual('practica.php') ?>>
                <span aria-hidden="true">🎮</span><span class="acceso-texto">Juegos</span>
            </a>
            <a href="<?= $base_tb ?>camara/detector.php" class="acceso-topbar" aria-label="Abrir detector de señas"<?= $marcar_actual('detector.php') ?>>
                <span aria-hidden="true">📷</span><span class="acceso-texto">Cámara</span>
            </a>
        </nav>

        <div class="menu-opciones">
            <button type="button" class="btn-hamburguesa" id="btn-opciones" aria-label="Abrir menú de opciones" aria-haspopup="true" aria-expanded="false" aria-controls="panel-opciones">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            <div class="panel-opciones" id="panel-opciones" role="menu">
                <div class="encabezado-opciones">
                    <span class="nombre-usuario"><?= htmlspecialchars($nombre_tb) ?></span>
                    <span class="correo-usuario"><?= htmlspecialchars($email_tb) ?></span>
                </div>

                <a href="<?= $base_tb ?>dashboard.php" class="opcion" role="menuitem"<?= $marcar_actual('dashboard.php') ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
                    Mi camino
                </a>
                <a href="<?= $base_tb ?>practica.php" class="opcion" role="menuitem"<?= $marcar_actual('practica.php') ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><circle cx="15.5" cy="15.5" r="1.5"/><path d="M8.5 15.5h.01M15.5 8.5h.01"/></svg>
                    Practicar con juegos
                </a>
                <a href="<?= $base_tb ?>camara/detector.php" class="opcion" role="menuitem"<?= $marcar_actual('detector.php') ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Detector de señas
                </a>

                <hr class="separador-opciones">

                <a href="<?= $base_tb ?>perfil.php" class="opcion" role="menuitem"<?= $marcar_actual('perfil.php') ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                    Mi perfil
                </a>
                <a href="<?= $base_tb ?>configuracion.php" class="opcion" role="menuitem"<?= $marcar_actual('configuracion.php') ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Configuración de cuenta
                </a>

                <hr class="separador-opciones">

                <a href="<?= $base_tb ?>logout.php" class="opcion salir" role="menuitem">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Cerrar sesión
                </a>
            </div>
        </div>

        <a href="<?= $base_tb ?>logout.php" class="btn-salir-directo" title="Cerrar sesión">Salir</a>
    </div>
</header>
