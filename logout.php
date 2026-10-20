<?php
// logout.php - Cierre seguro de sesión para el sistema SEVA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar arreglo de variables de sesión
$_SESSION = [];

// Invalidar cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión en el servidor
session_destroy();

// Redirigir al formulario de inicio de sesión
header('Location: login.php');
exit;
