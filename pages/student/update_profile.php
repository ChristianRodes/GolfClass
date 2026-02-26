<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    
    // Saneamiento básico
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
    $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_SPECIAL_CHARS);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        $sql = "UPDATE usuarios SET nombre = ?, apellidos = ?, telefono = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellidos, $telefono, $_SESSION['user_id']]);

        // Actualizamos el nombre en la sesión por si lo ha cambiado
        $_SESSION['user_name'] = $nombre;

        header("Location: profile.php?success=1");
        exit();
    } catch (PDOException $e) {
        die("Error al actualizar el perfil: " . $e->getMessage());
    }
}