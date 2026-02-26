<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Buscamos al usuario por su email
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(); // Obtenemos la fila del usuario

    // 2. Verificamos si existe y si la contraseña es correcta
    if ($user && password_verify($password, $user['password'])) {
        
        // 3. Verificamos si la cuenta está activa (Baja lógica)
        if ($user['activo'] == 0) {
            die("Tu cuenta está desactivada. Contacta con soporte.");
        }

        // 4. Creamos las variables de sesión
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_role'] = $user['id_rol'];

        // 5. Redirigimos a la página principal
        header("Location: ../../index.php");
        exit();

    } else {
        // Error genérico por seguridad (no decir si el email existe o no)
        die("Email o contraseña incorrectos.");
    }
}