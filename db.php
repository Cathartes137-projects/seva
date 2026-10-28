<?php
// Configuración de parámetros de conexión [2]
$host = "localhost";
$user = "root";
$pass = "";
$db   = "seva_educativo"; // Base de datos del sistema SEVA [1]

// Crear conexión mediante la extensión mysqli
$conexion = new mysqli($host, $user, $pass, $db);

// Validar errores de conexión
if ($conexion->connect_error) {
    die("Fallo de conexión: " . $conexion->connect_error);
}

// Configurar charset a utf8mb4 para soporte completo de caracteres [1]
$conexion->set_charset("utf8mb4");

// La conexión está lista para interactuar con las tablas roles [3] y usuarios [4].
?>