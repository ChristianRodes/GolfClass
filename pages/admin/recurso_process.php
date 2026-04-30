<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /GolfClass/index.php"); exit();
}

$accion     = $_POST['accion'] ?? '';
$id_profesor = filter_input(INPUT_POST, 'id_profesor', FILTER_VALIDATE_INT);

if ($accion === 'eliminar' && $id_profesor) {
    $pdo->prepare("UPDATE profesores SET activo = 0 WHERE id_profesor = ?")->execute([$id_profesor]);
    header("Location: recursos.php?tab=profesores&ok_del=1"); exit();
}

$id_usuario       = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
$id_campo         = filter_input(INPUT_POST, 'id_campo', FILTER_VALIDATE_INT) ?: null;
$ubicacion        = trim($_POST['ubicacion'] ?? '');
$precio_hora      = filter_input(INPUT_POST, 'precio_hora', FILTER_VALIDATE_FLOAT);
$capacidad        = max(1, (int)($_POST['capacidad'] ?? 1));
$experiencia_anos = max(0, (int)($_POST['experiencia_anos'] ?? 0));
$puntuacion       = max(1.0, min(5.0, (float)($_POST['puntuacion_simulada'] ?? 5.0)));
$bio              = trim($_POST['bio'] ?? '');
$titulaciones     = trim($_POST['titulaciones'] ?? '');
$clases_online    = isset($_POST['clases_online']) ? 1 : 0;
$activo           = (int)($_POST['activo'] ?? 1);

if ($id_profesor) {
    // UPDATE
    $pdo->prepare("
        UPDATE profesores SET
            id_campo=?, ubicacion=?, precio_hora=?, capacidad=?,
            experiencia_anos=?, puntuacion_simulada=?, bio=?,
            titulaciones=?, clases_online=?, activo=?
        WHERE id_profesor=?
    ")->execute([$id_campo, $ubicacion, $precio_hora, $capacidad,
                 $experiencia_anos, $puntuacion, $bio,
                 $titulaciones, $clases_online, $activo, $id_profesor]);
} else {
    // INSERT (requiere id_usuario)
    if (!$id_usuario || !$ubicacion || !$precio_hora) {
        header("Location: recursos.php?tab=profesores&error=datos"); exit();
    }
    $pdo->prepare("
        INSERT INTO profesores (id_usuario, id_campo, ubicacion, precio_hora, capacidad,
            experiencia_anos, puntuacion_simulada, bio, titulaciones, clases_online, activo)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([$id_usuario, $id_campo, $ubicacion, $precio_hora, $capacidad,
                 $experiencia_anos, $puntuacion, $bio, $titulaciones, $clases_online, 1]);
}

header("Location: recursos.php?tab=profesores&ok_prof=1"); exit();
