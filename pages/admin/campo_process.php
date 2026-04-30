<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /GolfClass/index.php"); exit();
}

$accion   = $_POST['accion'] ?? '';
$id_campo = filter_input(INPUT_POST, 'id_campo', FILTER_VALIDATE_INT);

if ($accion === 'eliminar' && $id_campo) {
    $pdo->prepare("UPDATE campos_golf SET activo = 0 WHERE id_campo = ?")->execute([$id_campo]);
    header("Location: recursos.php?tab=campos&ok_del=1"); exit();
}

$nombre_campo = trim($_POST['nombre_campo'] ?? '');
$ciudad       = trim($_POST['ciudad'] ?? '');
$direccion    = trim($_POST['direccion'] ?? '');
$activo       = (int)($_POST['activo'] ?? 1);

if (!$nombre_campo || !$ciudad) {
    header("Location: recursos.php?tab=campos&error=datos"); exit();
}

if ($id_campo) {
    $pdo->prepare("UPDATE campos_golf SET nombre_campo=?, ciudad=?, direccion=?, activo=? WHERE id_campo=?")
        ->execute([$nombre_campo, $ciudad, $direccion ?: null, $activo, $id_campo]);
} else {
    $pdo->prepare("INSERT INTO campos_golf (nombre_campo, ciudad, direccion, activo) VALUES (?,?,?,1)")
        ->execute([$nombre_campo, $ciudad, $direccion ?: null]);
}

header("Location: recursos.php?tab=campos&ok_campo=1"); exit();
