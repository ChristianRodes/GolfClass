<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    try {
        // Baja lógica: Ponemos activo = 0 en lugar de hacer DELETE
        $sql = "UPDATE usuarios SET activo = 0 WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);

        // Destruimos la sesión porque el usuario ya no está activo
        session_unset();
        session_destroy();

        // Redirigimos a la página principal con un mensaje en la URL
        header("Location: ../../index.php?msg=deactivated");
        exit();
    } catch (PDOException $e) {
        die("Error al desactivar la cuenta: " . $e->getMessage());
    }
}