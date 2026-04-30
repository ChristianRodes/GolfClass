<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'no_auth']); exit();
}

$yo          = (int) $_SESSION['user_id'];
$id_receptor = filter_input(INPUT_POST, 'id_receptor', FILTER_VALIDATE_INT);
$mensaje     = trim($_POST['mensaje'] ?? '');

if (!$id_receptor || $id_receptor === $yo || empty($mensaje)) {
    echo json_encode(['ok' => false, 'error' => 'datos_invalidos']); exit();
}

// Verificar que el receptor existe y está activo
$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND activo = 1");
$stmt->execute([$id_receptor]);
if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'receptor_no_existe']); exit();
}

// Limitar longitud
$mensaje = mb_substr($mensaje, 0, 2000);

$stmt = $pdo->prepare("INSERT INTO mensajes (id_emisor, id_receptor, mensaje) VALUES (?,?,?)");
$stmt->execute([$yo, $id_receptor, $mensaje]);
$id_mensaje = (int) $pdo->lastInsertId();

echo json_encode([
    'ok'  => true,
    'msg' => [
        'id_mensaje' => $id_mensaje,
        'id_emisor'  => $yo,
        'mensaje'    => $mensaje,
        'leido'      => 0,
        'hora'       => date('H:i'),
    ],
]);
