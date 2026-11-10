<?php
// logout.php - Cierre seguro de sesión para el sistema SEVA
require '../config/conexion.php';
require '../config/seguridad.php';

seguridad_iniciar_sesion();
seguridad_cerrar_sesion();

// Invalidar el token de "Recordarme" y la cookie asociada
$usuario_id_logout = (int) ($_SESSION['usuario_id'] ?? 0);
seguridad_borrar_recordatorio($pdo, $usuario_id_logout);

// Redirigir al formulario de inicio de sesión
header('Location: login.php');
exit;
