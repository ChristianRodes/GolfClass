<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /index.php");
    exit();
}

$id_usuario = (int) $_SESSION['user_id'];
$semana     = (int) ($_POST['semana'] ?? 0);

// Obtener id_profesor
$stmt = $pdo->prepare("SELECT id_profesor FROM profesores WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$row = $stmt->fetch();
if (!$row) { header("Location: dashboard.php?semana={$semana}"); exit(); }
$id_profesor = (int) $row['id_profesor'];

$fecha       = trim($_POST['fecha'] ?? '');
$hora_inicio = trim($_POST['hora_inicio'] ?? '');
$hora_fin    = trim($_POST['hora_fin'] ?? '');
$motivo      = trim($_POST['motivo'] ?? '');

// Validaciones básicas
if (!$fecha || !$hora_inicio || !$hora_fin) {
    header("Location: dashboard.php?semana={$semana}&error=datos");
    exit();
}
if ($hora_fin <= $hora_inicio) {
    header("Location: dashboard.php?semana={$semana}&error=horas");
    exit();
}

// Verificar que no haya ya una reserva activa en ese tramo
$stmt = $pdo->prepare("
    SELECT id_reserva FROM reservas
    WHERE id_profesor = ? AND fecha_clase = ?
      AND estado != 'Cancelada' AND activo = 1
      AND hora_inicio < ? AND hora_fin > ?
");
$stmt->execute([$id_profesor, $fecha, $hora_fin, $hora_inicio]);
if ($stmt->fetch()) {
    header("Location: dashboard.php?semana={$semana}&error=reserva_existente");
    exit();
}

// Verificar que no haya ya un bloqueo solapado
$stmt = $pdo->prepare("
    SELECT id_bloqueo FROM slots_bloqueados
    WHERE id_profesor = ? AND fecha = ?
      AND hora_inicio < ? AND hora_fin > ?
");
$stmt->execute([$id_profesor, $fecha, $hora_fin, $hora_inicio]);
if ($stmt->fetch()) {
    header("Location: dashboard.php?semana={$semana}&error=ya_bloqueado");
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO slots_bloqueados (id_profesor, fecha, hora_inicio, hora_fin, motivo)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$id_profesor, $fecha, $hora_inicio, $hora_fin, $motivo ?: null]);

header("Location: dashboard.php?semana={$semana}&ok=bloqueado");
exit();
