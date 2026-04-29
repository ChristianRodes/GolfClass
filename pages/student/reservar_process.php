<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../auth/login.php");
    exit();
}

$id_profesor  = filter_input(INPUT_POST, 'id_profesor', FILTER_VALIDATE_INT);
$fecha_clase  = trim($_POST['fecha_clase'] ?? '');
$hora_inicio  = trim($_POST['hora_inicio'] ?? '');

if (!$id_profesor || !$fecha_clase || !$hora_inicio) {
    header("Location: reservar.php?id={$id_profesor}&error=datos");
    exit();
}

// Validar fecha futura
if ($fecha_clase <= date('Y-m-d')) {
    header("Location: reservar.php?id={$id_profesor}&error=pasado");
    exit();
}

// Calcular hora_fin (+1 hora)
$hora_fin = date('H:i', strtotime($hora_inicio . ' +1 hour'));

// Verificar que no haya conflicto para ese profesor en ese horario
$stmt = $pdo->prepare("
    SELECT id_reserva FROM reservas
    WHERE id_profesor = ?
      AND fecha_clase = ?
      AND estado != 'Cancelada'
      AND activo = 1
      AND hora_inicio < ? AND hora_fin > ?
");
$stmt->execute([$id_profesor, $fecha_clase, $hora_fin, $hora_inicio]);

if ($stmt->fetch()) {
    header("Location: reservar.php?id={$id_profesor}&error=ocupado");
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO reservas (id_alumno, id_profesor, fecha_clase, hora_inicio, hora_fin, estado, activo)
        VALUES (?, ?, ?, ?, ?, 'Pendiente', 1)
    ");
    $stmt->execute([$_SESSION['user_id'], $id_profesor, $fecha_clase, $hora_inicio, $hora_fin]);

    header("Location: mis_reservas.php?success=1");
    exit();
} catch (\PDOException $e) {
    header("Location: reservar.php?id={$id_profesor}&error=db");
    exit();
}
