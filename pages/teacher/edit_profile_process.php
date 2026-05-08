<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /index.php");
    exit();
}

$id_usuario       = (int) $_SESSION['user_id'];
$ubicacion        = trim($_POST['ubicacion'] ?? '');
$bio              = trim($_POST['bio'] ?? '');
$titulaciones     = trim($_POST['titulaciones'] ?? '');
$clases_online    = isset($_POST['clases_online']) ? 1 : 0;
$precio_hora      = filter_input(INPUT_POST, 'precio_hora', FILTER_VALIDATE_FLOAT);
$experiencia_anos = filter_input(INPUT_POST, 'experiencia_anos', FILTER_VALIDATE_INT);
$ciudad           = trim($_POST['ciudad'] ?? '');
$puntuacion       = filter_input(INPUT_POST, 'puntuacion_simulada', FILTER_VALIDATE_FLOAT);

if (empty($ubicacion) || $precio_hora === false || $precio_hora <= 0) {
    header("Location: edit_profile.php?error=datos");
    exit();
}

$puntuacion       = max(1.0, min(5.0, (float) $puntuacion));
$experiencia_anos = max(0, (int) $experiencia_anos);

// ── Foto de perfil ────────────────────────────────────────────────────────
$uploads_dir  = __DIR__ . '/../../uploads/';
$foto_nueva   = null;     // null = no cambiar; string = nueva ruta

// Obtener foto actual (si el perfil ya existe)
$stmt_foto = $pdo->prepare("SELECT foto_perfil FROM profesores WHERE id_usuario = ?");
$stmt_foto->execute([$id_usuario]);
$foto_actual = $stmt_foto->fetchColumn() ?: null;

// 1. Eliminar foto actual si se marcó el checkbox
if (isset($_POST['eliminar_foto']) && $foto_actual) {
    $ruta_old = $uploads_dir . $foto_actual;
    if (file_exists($ruta_old)) unlink($ruta_old);
    $foto_nueva = '';   // vacío → NULL en BD
}

// 2. Nueva foto subida
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto_perfil'];

    $tipos_ok = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $ext_ok   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $max_size = 3 * 1024 * 1024;

    // Doble validación: MIME type del servidor + extensión
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($mime, $tipos_ok) || !in_array($ext, $ext_ok)) {
        header("Location: edit_profile.php?error=tipo_foto");
        exit();
    }
    if ($file['size'] > $max_size) {
        header("Location: edit_profile.php?error=foto_grande");
        exit();
    }

    $nombre_archivo = 'prof_' . $id_usuario . '_' . time() . '.' . $ext;
    $ruta_destino   = $uploads_dir . $nombre_archivo;

    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        // Borrar foto anterior si existía y no se eliminó antes
        if ($foto_actual && $foto_nueva === null) {
            $ruta_old = $uploads_dir . $foto_actual;
            if (file_exists($ruta_old)) unlink($ruta_old);
        }
        $foto_nueva = $nombre_archivo;
    }
}

// ── Guardar en BD ─────────────────────────────────────────────────────────
try {
    if ($foto_nueva !== null) {
        // INSERT o UPDATE incluyendo foto_perfil
        $stmt = $pdo->prepare("
            INSERT INTO profesores
                (id_usuario, ubicacion, bio, titulaciones, clases_online,
                 precio_hora, experiencia_anos, ciudad, puntuacion_simulada, foto_perfil)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ubicacion           = VALUES(ubicacion),
                bio                 = VALUES(bio),
                titulaciones        = VALUES(titulaciones),
                clases_online       = VALUES(clases_online),
                precio_hora         = VALUES(precio_hora),
                experiencia_anos    = VALUES(experiencia_anos),
                ciudad              = VALUES(ciudad),
                puntuacion_simulada = VALUES(puntuacion_simulada),
                foto_perfil         = VALUES(foto_perfil)
        ");
        $fp = $foto_nueva === '' ? null : $foto_nueva;
        $stmt->execute([
            $id_usuario, $ubicacion, $bio, $titulaciones, $clases_online,
            $precio_hora, $experiencia_anos, $ciudad, $puntuacion, $fp,
        ]);
    } else {
        // Sin cambios en la foto
        $stmt = $pdo->prepare("
            INSERT INTO profesores
                (id_usuario, ubicacion, bio, titulaciones, clases_online,
                 precio_hora, experiencia_anos, ciudad, puntuacion_simulada)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ubicacion           = VALUES(ubicacion),
                bio                 = VALUES(bio),
                titulaciones        = VALUES(titulaciones),
                clases_online       = VALUES(clases_online),
                precio_hora         = VALUES(precio_hora),
                experiencia_anos    = VALUES(experiencia_anos),
                ciudad              = VALUES(ciudad),
                puntuacion_simulada = VALUES(puntuacion_simulada)
        ");
        $stmt->execute([
            $id_usuario, $ubicacion, $bio, $titulaciones, $clases_online,
            $precio_hora, $experiencia_anos, $ciudad, $puntuacion,
        ]);
    }

    header("Location: edit_profile.php?success=1");
    exit();
} catch (\PDOException $e) {
    header("Location: edit_profile.php?error=db");
    exit();
}
