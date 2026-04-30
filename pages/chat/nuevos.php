<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['mensajes' => []]); exit();
}

$yo      = (int) $_SESSION['user_id'];
$id_otro = filter_input(INPUT_GET, 'con',   FILTER_VALIDATE_INT);
$desde   = filter_input(INPUT_GET, 'desde', FILTER_VALIDATE_INT) ?: 0;

if (!$id_otro) {
    echo json_encode(['mensajes' => []]); exit();
}

// Marcar como leídos los mensajes recibidos del otro
$pdo->prepare("UPDATE mensajes SET leido = 1 WHERE id_emisor = ? AND id_receptor = ? AND leido = 0")
    ->execute([$id_otro, $yo]);

// Devolver mensajes nuevos desde el último id conocido
$stmt = $pdo->prepare("
    SELECT id_mensaje, id_emisor, mensaje, leido,
           DATE_FORMAT(fecha_envio, '%H:%i') AS hora
    FROM mensajes
    WHERE id_mensaje > ?
      AND (
          (id_emisor = ? AND id_receptor = ?)
       OR (id_emisor = ? AND id_receptor = ?)
      )
    ORDER BY id_mensaje ASC
");
$stmt->execute([$desde, $yo, $id_otro, $id_otro, $yo]);
$mensajes = $stmt->fetchAll();

// Convertir tipos para JSON
foreach ($mensajes as &$m) {
    $m['id_mensaje'] = (int) $m['id_mensaje'];
    $m['id_emisor']  = (int) $m['id_emisor'];
    $m['leido']      = (int) $m['leido'];
}

echo json_encode(['mensajes' => $mensajes]);
