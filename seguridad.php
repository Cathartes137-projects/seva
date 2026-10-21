<?php
// =============================================================================
// seguridad.php - Núcleo de seguridad de SEVA
// =============================================================================
// Centraliza la gestión de sesión, la protección CSRF y las utilidades de
// contraseñas. Debe incluirse SIEMPRE antes de cualquier salida y después de
// conexion.php.
//
// Uso típico en una página:
//   require 'conexion.php';
//   require 'seguridad.php';
//   seguridad_iniciar_sesion();
//   seguridad_requiere_login();            // si la página es privada
//   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//       seguridad_validar_csrf();          // aborta si el token no es válido
//       ...
//   }
// =============================================================================

// -----------------------------------------------------------------------------
// 1. SESIÓN SEGURA
// -----------------------------------------------------------------------------

/**
 * Indica si la petición actual viaja por HTTPS.
 */
function seguridad_es_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

/**
 * Inicia la sesión con cookies endurecidas.
 *  - HttpOnly: la cookie no es accesible desde JavaScript.
 *  - SameSite=Lax: mitiga CSRF en navegaciones de terceros.
 *  - Secure: solo se envía por HTTPS (automático cuando el sitio usa HTTPS).
 *  - strict mode: rechaza identificadores de sesión no generados por el servidor.
 */
function seguridad_iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent()) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => seguridad_es_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Regenera el identificador de sesión (previene la fijación de sesión).
 * Conserva los datos de la sesión actual.
 */
function seguridad_regenerar_sesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }
    session_regenerate_id(true);
}

/**
 * Cierra por completo la sesión: vacía datos, invalida la cookie y destruye
 * la sesión en el servidor.
 */
function seguridad_cerrar_sesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Exige una sesión autenticada. Redirige al login si no la hay.
 */
function seguridad_requiere_login(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// 2. PROTECCIÓN CSRF
// -----------------------------------------------------------------------------

/**
 * Devuelve el token CSRF de la sesión, generándolo si aún no existe.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Devuelve el campo oculto listo para insertar dentro de un formulario POST.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida el token recibido por POST contra el de la sesión.
 * Comparación en tiempo constante con hash_equals().
 */
function csrf_es_valido(): bool
{
    $recibido = $_POST['csrf_token'] ?? '';
    if (!is_string($recibido) || $recibido === '') {
        return false;
    }
    $esperado = $_SESSION['csrf_token'] ?? '';
    if (!is_string($esperado) || $esperado === '') {
        return false;
    }
    return hash_equals($esperado, $recibido);
}

/**
 * Aborta la petición si el token CSRF no es válido.
 * Responde 403 en peticiones AJAX y redirige con aviso en peticiones normales.
 *
 * @param string $destino Página a la que volver cuando falla una petición normal.
 */
function seguridad_validar_csrf(string $destino = ''): void
{
    if (csrf_es_valido()) {
        return;
    }

    $es_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($es_ajax) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(403);
        }
        echo json_encode(['exito' => false, 'error' => 'csrf']);
        exit;
    }

    if ($destino !== '' && !headers_sent()) {
        header('Location: ' . $destino . '?error=csrf');
    } elseif (!headers_sent()) {
        http_response_code(403);
    }
    exit;
}

/**
 * Indica si la petición actual llegó con un error de CSRF en la URL,
 * para que la página pueda mostrar el aviso correspondiente.
 */
function csrf_error_en_url(): bool
{
    return isset($_GET['error']) && $_GET['error'] === 'csrf';
}

// -----------------------------------------------------------------------------
// 3. CONTRASEÑAS
// -----------------------------------------------------------------------------

/**
 * Rehashea una contraseña almacenada si el algoritmo o coste han cambiado.
 * Debe invocarse tras un inicio de sesión exitoso, con el ID del usuario.
 * Devuelve true si el hash fue actualizado.
 */
function seguridad_rehash_password(PDO $pdo, int $usuario_id, string $password_plano, string $hash_actual): bool
{
    if (!password_needs_rehash($hash_actual, PASSWORD_DEFAULT)) {
        return false;
    }

    try {
        $nuevo_hash = password_hash($password_plano, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
        $stmt->execute([$nuevo_hash, $usuario_id]);
        return true;
    } catch (PDOException $e) {
        error_log('Error al rehashear contraseña en SEVA: ' . $e->getMessage());
        return false;
    }
}
