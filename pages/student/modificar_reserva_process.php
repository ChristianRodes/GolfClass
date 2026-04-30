<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../auth/login.php"); exit();
}

$id_reserva  = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
$fecha_clase = trim($_POST['fecha_clase'] ?? '');
$hora_inicio = trim($_POST['hora_inicio'] ?? '');

if (!$id_reserva || !$fecha_clase || !$hora_inicio) {
    header("Location: mis_reservas.php"); exit();
}

// Verificar que la reserva pertenece al alumno y sigue siendo Pendiente
$stmt = $pdo->prepare("SELECT r.id_profesor, r.fecha_clase, r.hora_inicio FROM reservas r WHERE r.id_reserva=? AND r.id_alumno=? AND r.estado='Pendiente' AND r.activo=1");
$stmt->execute([$id_reserva, $_SESSION['user_id']]);
$reserva = $stmt->fetch();

if (!$reserva) { header("Location: mis_reservas.php"); exit(); }

// Plazo >24h
$clase_ts = strtotime($reserva['fecha_clase'].' '.$reserva['hora_inicio']);
if ($clase_ts - time() <= 86400) { header("Location: mis_reservas.php?error=plazo"); exit(); }

// Fecha futura
if ($fecha_clase <= date('Y-m-d')) { header("Location: modificar_reserva.php?id={$id_reserva}&error=1"); exit(); }

$hora_fin = date('H:i', strtotime($hora_inicio . ' +1 hour'));

// Comprobar disponibilidad (excluyendo la propia reserva)
$stmt = $pdo->prepare("
    SELECT id_reserva FROM reservas
    WHERE id_profesor=? AND fecha_clase=? AND estado!='Cancelada' AND activo=1
      AND hora_inicio < ? AND hora_fin > ? AND id_reserva != ?
");
$stmt->execute([$reserva['id_profesor'], $fecha_clase, $hora_fin, $hora_inicio, $id_reserva]);
if ($stmt->fetch()) { header("Location: modificar_reserva.php?id={$id_reserva}&error=1"); exit(); }

// Comprobar slots bloqueados
$stmt = $pdo->prepare("SELECT id_bloqueo FROM slots_bloqueados WHERE id_profesor=? AND fecha=? AND hora_inicio<? AND hora_fin>?");
$stmt->execute([$reserva['id_profesor'], $fecha_clase, $hora_fin, $hora_inicio]);
if ($stmt->fetch()) { header("Location: modificar_reserva.php?id={$id_reserva}&error=1"); exit(); }

$pdo->prepare("UPDATE reservas SET fecha_clase=?, hora_inicio=?, hora_fin=? WHERE id_reserva=?")
    ->execute([$fecha_clase, $hora_inicio, $hora_fin, $id_reserva]);

header("Location: mis_reservas.php?modified=1"); exit();
