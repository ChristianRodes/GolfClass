<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /index.php");
    exit();
}

$id_usuario  = (int) $_SESSION['user_id'];
$id_bloqueo  = filter_input(INPUT_POST, 'id_bloqueo', FILTER_VALIDATE_INT);
$semana      = (int) ($_POST['semana'] ?? 0);

if (!$id_bloqueo) {
    header("Location: dashboard.php?semana={$semana}");
    exit();
}

// Verificar que el bloqueo pertenece a este profesor
$stmt = $pdo->prepare("
    SELECT sb.id_bloqueo FROM slots_bloqueados sb
    JOIN profesores p ON sb.id_profesor = p.id_profesor
    WHERE sb.id_bloqueo = ? AND p.id_usuario = ?
");
$stmt->execute([$id_bloqueo, $id_usuario]);

if ($stmt->fetch()) {
    $del = $pdo->prepare("DELETE FROM slots_bloqueados WHERE id_bloqueo = ?");
    $del->execute([$id_bloqueo]);
}

header("Location: dashboard.php?semana={$semana}&ok=desbloqueado");
exit();
