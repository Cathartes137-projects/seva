<?php
require 'conexion.php';
require 'seguridad.php';

seguridad_iniciar_sesion();

// Validación estricta de sesión de usuario
seguridad_requiere_login();

$usuario_id = $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';

$error_bd = '';
$lecciones = [];
$total = 0;
$completadas = 0;
$porcentaje = 0;
$completados_ids = [];

try {
    // Consulta extendida con nivel y orden para la ruta gamificada
    $stmt_lec = $pdo->query('SELECT id, titulo, descripcion, ruta_imagen, ruta_video, nivel, orden FROM contenidos_educativos ORDER BY orden ASC, id ASC');
    $lecciones = $stmt_lec->fetchAll();
    $total = count($lecciones);

    $stmt_comp = $pdo->prepare('SELECT COUNT(*) AS c FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_comp->execute([$usuario_id]);
    $completadas = (int) $stmt_comp->fetch()['c'];

    $porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;

    $stmt_ids = $pdo->prepare('SELECT contenido_id FROM progreso_usuario WHERE usuario_id = ? AND completado = 1');
    $stmt_ids->execute([$usuario_id]);
    $completados_ids = array_column($stmt_ids->fetchAll(), 'contenido_id');
} catch (PDOException $e) {
    error_log('Dashboard - error de base de datos: ' . $e->getMessage());
    $error_bd = 'No se pudieron cargar las lecciones. Verifica la conexión a la base de datos de SEVA.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Camino de Aprendizaje · SEVA</title>
    <!-- Fuentes Web Accesibles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Oficiales SEVA -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <a href="#contenido-principal" class="skip-link">Saltar al contenido principal</a>

    <!-- Encabezado Superior -->
    <header class="topbar">
        <a href="dashboard.php" class="marca" aria-label="Inicio SEVA - Sistema Educativo de Lengua de Señas">
            <span class="punto" aria-hidden="true"></span>SEVA
        </a>

        <div class="topbar-derecha">
            <span class="saludo">Hola, <strong><?= htmlspecialchars($nombre) ?></strong></span>

            <!-- Menú Desplegable Accesible -->
            <div class="menu-opciones">
                <button type="button" class="btn-hamburguesa" id="btn-opciones" aria-label="Abrir menú de usuario" aria-haspopup="true" aria-expanded="false" aria-controls="panel-opciones">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>

                <div class="panel-opciones" id="panel-opciones" role="menu">
                    <div class="encabezado-opciones">
                        <span class="nombre-usuario"><?= htmlspecialchars($nombre) ?></span>
                        <span class="correo-usuario"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                    </div>

                    <a href="perfil.php" class="opcion" role="menuitem">
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

    <main class="contenedor" id="contenido-principal">
        <?php if ($error_bd): ?>
            <div class="alerta alerta-error" role="alert">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <strong>Error de conexión</strong>
                    <p style="margin:0;"><?= htmlspecialchars($error_bd) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$error_bd): ?>
            <!-- 1. Tarjeta de Progreso General -->
            <section class="panel-progreso" aria-labelledby="titulo-progreso">
                <div class="fila-progreso">
                    <span class="etiqueta" id="titulo-progreso">Progreso general</span>
                    <span class="cifra" aria-label="<?= $porcentaje ?> por ciento completado"><?= $porcentaje ?>%</span>
                </div>
                <div class="barra" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso acumulado de lecciones">
                    <div class="barra-relleno" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <p class="sub-progreso">
                    <strong><?= $completadas ?></strong> de <strong><?= $total ?></strong> lecciones completadas. ¡Continúa practicando a tu ritmo!
                </p>
            </section>

            <!-- 2. Ruta de Aprendizaje "Tu Camino" -->
            <section class="seccion-camino" aria-labelledby="titulo-camino">
                <!-- Barra de herramientas: Buscador y Pestañas de Nivel -->
                <div class="barra-herramientas">
                    <div class="contenedor-buscador">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="buscador" placeholder="Buscar lección por título..." aria-label="Buscar lecciones en tiempo real">
                    </div>

                    <!-- Pestañas de filtro por nivel -->
                    <div class="filtros-camino" role="tablist" aria-label="Filtrar lecciones por nivel">
                        <button type="button" class="btn-filtro activo" data-filtro="todos" role="tab" aria-selected="true" id="tab-todos">Todas</button>
                        <button type="button" class="btn-filtro" data-filtro="basico" role="tab" aria-selected="false" id="tab-basico">Básico</button>
                        <button type="button" class="btn-filtro" data-filtro="intermedio" role="tab" aria-selected="false" id="tab-intermedio">Intermedio</button>
                        <button type="button" class="btn-filtro" data-filtro="avanzado" role="tab" aria-selected="false" id="tab-avanzado">Avanzado</button>
                    </div>
                </div>

                <div class="encabezado-seccion-camino">
                    <h2 id="titulo-camino">
                        Tu Camino
                        <span class="badge-contador" id="contador-visibles"><?= $total ?> disponibles</span>
                    </h2>
                </div>

                <?php if (count($lecciones) > 0): ?>
                    <div class="grid-lecciones" id="contenedor-lecciones">
                        <?php foreach ($lecciones as $l): ?>
                            <?php
                                $hecha = in_array($l['id'], $completados_ids);
                                $nivel_raw = strtolower($l['nivel'] ?? 'basico');
                                $nivel_nom = ($nivel_raw === 'intermedio') ? 'Intermedio' : (($nivel_raw === 'avanzado') ? 'Avanzado' : 'Básico');
                                $img_src = !empty($l['ruta_imagen']) ? htmlspecialchars($l['ruta_imagen']) : 'img/lecciones/default.jpg';
                            ?>
                            <article class="tarjeta-leccion" data-nivel="<?= htmlspecialchars($nivel_raw) ?>" data-titulo="<?= htmlspecialchars(strtolower($l['titulo'])) ?>">
                                <div class="portada-leccion">
                                    <span class="insignia-nivel nivel-<?= htmlspecialchars($nivel_raw) ?>"><?= $nivel_nom ?></span>
                                    
                                    <?php if ($hecha): ?>
                                        <span class="insignia-completada" aria-label="Lección completada">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                            Completada
                                        </span>
                                    <?php endif; ?>

                                    <img src="<?= $img_src ?>" alt="Portada de la lección: <?= htmlspecialchars($l['titulo']) ?>" loading="lazy" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22300%22%20height%3D%22165%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20300%20165%22%20preserveAspectRatio%3D%22none%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23E8EEF5%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20fill%3D%22%230056b3%22%20font-family%3D%22sans-serif%22%20font-weight%3D%22bold%22%20font-size%3D%2216%22%20text-anchor%3D%22middle%22%20dy%3D%22.3em%22%3ESEVA%20LSE%3C%2Ftext%3E%3C%2Fsvg%3E'">
                                </div>

                                <div class="tarjeta-cuerpo">
                                    <h3 class="titulo-leccion"><?= htmlspecialchars($l['titulo']) ?></h3>
                                    <p class="descripcion-leccion">
                                        <?= htmlspecialchars(!empty($l['descripcion']) ? $l['descripcion'] : 'Práctica interactiva de Lengua de Señas.') ?>
                                    </p>
                                    <a href="leccion.php?id=<?= (int)$l['id'] ?>" class="btn btn-primario btn-ver" aria-label="Ver lección: <?= htmlspecialchars($l['titulo']) ?>">
                                        Ver lección
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mensaje para cuando ningún resultado coincida -->
                    <div class="estado-vacio" id="sin-resultados" style="display:none;" role="status" aria-live="polite">
                        <h3>No se encontraron lecciones</h3>
                        <p>No hay lecciones que coincidan con los criterios de búsqueda o filtro seleccionados.</p>
                    </div>
                <?php else: ?>
                    <div class="estado-vacio">
                        <h3>Todavía no hay lecciones cargadas</h3>
                        <p>Cuando se agreguen contenidos educativos en la base de datos, aparecerán en tu camino.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        // Filtrado en tiempo real: Buscador y Pestañas de nivel
        const buscador = document.getElementById('buscador');
        const botonesFiltro = document.querySelectorAll('.btn-filtro');
        const tarjetas = document.querySelectorAll('.tarjeta-leccion');
        const sinResultados = document.getElementById('sin-resultados');
        const contadorVisibles = document.getElementById('contador-visibles');
        let filtroNivelActual = 'todos';

        function aplicarFiltros() {
            const termino = buscador ? buscador.value.trim().toLowerCase() : '';
            let visibles = 0;

            tarjetas.forEach(function (tarjeta) {
                const titulo = tarjeta.dataset.titulo || '';
                const nivel = tarjeta.dataset.nivel || '';

                const coincideTexto = termino === '' || titulo.includes(termino);
                const coincideNivel = filtroNivelActual === 'todos' || nivel === filtroNivelActual;

                if (coincideTexto && coincideNivel) {
                    tarjeta.style.display = '';
                    visibles++;
                } else {
                    tarjeta.style.display = 'none';
                }
            });

            if (sinResultados) {
                sinResultados.style.display = (visibles === 0 && tarjetas.length > 0) ? 'block' : 'none';
            }
            if (contadorVisibles) {
                contadorVisibles.textContent = visibles + (visibles === 1 ? ' disponible' : ' disponibles');
            }
        }

        if (buscador) {
            buscador.addEventListener('input', aplicarFiltros);
        }

        botonesFiltro.forEach(function (btn) {
            btn.addEventListener('click', function () {
                botonesFiltro.forEach(function (b) {
                    b.classList.remove('activo');
                    b.setAttribute('aria-selected', 'false');
                });
                this.classList.add('activo');
                this.setAttribute('aria-selected', 'true');
                filtroNivelActual = this.dataset.filtro;
                aplicarFiltros();
            });
        });

        // Menú accesible de opciones de usuario
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
    </script>
</body>
</html>
