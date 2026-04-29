<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /GolfClass/index.php");
    exit();
}

$id_usuario       = (int) $_SESSION['user_id'];
$id_campo         = filter_input(INPUT_POST, 'id_campo', FILTER_VALIDATE_INT);
$bio              = trim($_POST['bio'] ?? '');
$precio_hora      = filter_input(INPUT_POST, 'precio_hora', FILTER_VALIDATE_FLOAT);
$experiencia_anos = filter_input(INPUT_POST, 'experiencia_anos', FILTER_VALIDATE_INT);
$ciudad           = trim($_POST['ciudad'] ?? '');
$puntuacion       = filter_input(INPUT_POST, 'puntuacion_simulada', FILTER_VALIDATE_FLOAT);

if (!$id_campo || $precio_hora === false || $precio_hora <= 0) {
    header("Location: edit_profile.php?error=datos");
    exit();
}

// Clampar puntuación entre 1.0 y 5.0
$puntuacion = max(1.0, min(5.0, (float) $puntuacion));
$experiencia_anos = max(0, (int) $experiencia_anos);

try {
    // INSERT si no existe, UPDATE si ya existe (id_usuario es UNIQUE en profesores)
    $stmt = $pdo->prepare("
        INSERT INTO profesores (id_usuario, id_campo, bio, precio_hora, experiencia_anos, ciudad, puntuacion_simulada)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            id_campo          = VALUES(id_campo),
            bio               = VALUES(bio),
            precio_hora       = VALUES(precio_hora),
            experiencia_anos  = VALUES(experiencia_anos),
            ciudad            = VALUES(ciudad),
            puntuacion_simulada = VALUES(puntuacion_simulada)
    ");
    $stmt->execute([$id_usuario, $id_campo, $bio, $precio_hora, $experiencia_anos, $ciudad, $puntuacion]);

    header("Location: edit_profile.php?success=1");
    exit();
} catch (\PDOException $e) {
    header("Location: edit_profile.php?error=db");
    exit();
}
