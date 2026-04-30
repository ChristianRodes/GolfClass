<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/strikes.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /GolfClass/index.php"); exit();
}

$id_usuario   = (int) $_SESSION['user_id'];
$id_reserva   = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
$nuevo_estado = $_POST['nuevo_estado'] ?? '';
$semana       = (int) ($_POST['semana'] ?? 0);

$estados_validos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'No presentado'];

if (!$id_reserva || !in_array($nuevo_estado, $estados_validos)) {
    header("Location: dashboard.php?semana={$semana}"); exit();
}

// Verificar que la reserva es de este profesor y obtener el id_alumno
$stmt = $pdo->prepare("
    SELECT r.id_alumno, r.estado AS estado_actual
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    WHERE r.id_reserva = ? AND p.id_usuario = ?
");
$stmt->execute([$id_reserva, $id_usuario]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header("Location: dashboard.php?semana={$semana}"); exit();
}

// Actualizar estado
$pdo->prepare("UPDATE reservas SET estado = ? WHERE id_reserva = ?")
    ->execute([$nuevo_estado, $id_reserva]);

// Si se marca como "No presentado" y no tenía este estado antes → strike
if ($nuevo_estado === 'No presentado' && $reserva['estado_actual'] !== 'No presentado') {
    $resultado = registrar_strike($pdo, (int)$reserva['id_alumno'], $id_reserva, 'no_presentado');
    $extra = match($resultado['tipo']) {
        'permanente' => '&strike=permanente',
        'temporal'   => '&strike=temporal',
        default      => '&strike=1',
    };
    header("Location: dashboard.php?semana={$semana}&ok=nopresentado{$extra}"); exit();
}

header("Location: dashboard.php?semana={$semana}&ok=estado"); exit();
