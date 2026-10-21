<?php
// logout.php - Cierre seguro de sesión para el sistema SEVA
require 'seguridad.php';

seguridad_iniciar_sesion();
seguridad_cerrar_sesion();

// Redirigir al formulario de inicio de sesión
header('Location: login.php');
exit;
