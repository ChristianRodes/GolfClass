<?php
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $role = 3; // Alumno por defecto

    // 1. Validaciones básicas
    if (strlen($pass) < 6) {
        die("La contraseña debe tener al menos 6 caracteres.");
    }

    // 2. Hashear contraseña (SEGURIDAD)
    $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);

    try {
        $sql = "INSERT INTO usuarios (id_rol, nombre, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $name, $email, $hashed_pass]);

        header("Location: login.php?success=1");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die("Este email ya está registrado.");
        }
        die("Error: " . $e->getMessage());
    }
}