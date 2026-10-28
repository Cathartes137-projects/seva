<?php
session_start();
require 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$cid = (int) ($_POST['contenido_id'] ?? 0);
if ($cid <= 0) { http_response_code(400); echo json_encode(['ok' => false]); exit; }

try {
    $chk = $pdo->prepare('SELECT id FROM contenidos_educativos WHERE id = ?');
    $chk->execute([$cid]);
    if (!$chk->fetch()) { http_response_code(404); echo json_encode(['ok' => false]); exit; }

    $st = $pdo->prepare('INSERT INTO progreso_usuario (usuario_id, contenido_id, completado, fecha_completado)
                         VALUES (?, ?, 1, NOW())
                         ON DUPLICATE KEY UPDATE completado = 1, fecha_completado = NOW()');
    $st->execute([$_SESSION['usuario_id'], $cid]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
