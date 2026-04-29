<?php
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // Solo se permite registrar como alumno (3) o profesor (2), nunca admin (1)
    $role_raw = (int) ($_POST['role_id'] ?? 3);
    $role = in_array($role_raw, [2, 3]) ? $role_raw : 3;

    if (strlen($pass) < 6) {
        die("La contraseña debe tener al menos 6 caracteres.");
    }
    if (empty($name) || empty($email)) {
        die("El nombre y el email son obligatorios.");
    }

    $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);

    try {
        $sql  = "INSERT INTO usuarios (id_rol, nombre, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $name, $email, $hashed_pass]);

        header("Location: login.php?success=1");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die("Este email ya está registrado.");
        }
        die("Error al registrar: " . $e->getMessage());
    }
}
