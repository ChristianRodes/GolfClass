<?php
require_once '../../includes/db.php';
session_start();

// Si se envía el formulario (POST) para reactivar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_to_reactivate = $_POST['email'];
    
    try {
        $sql = "UPDATE usuarios SET activo = 1 WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email_to_reactivate]);
        
        // Redirigimos al login con éxito
        header("Location: login.php?reactivated=1");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Si solo está viendo la página (GET)
$email = $_GET['email'] ?? '';
if (empty($email)) {
    header("Location: login.php"); // Si llega aquí sin email, lo devolvemos al login
    exit();
}

include '../../includes/header.php';
?>

<div class="container mt-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-warning shadow-sm p-4">
                <h2 class="text-warning mb-3">Cuenta Desactivada</h2>
                <p class="fs-5">Tu cuenta asociada al correo <strong><?php echo htmlspecialchars($email); ?></strong> se encuentra inactiva.</p>
                <p class="text-muted mb-4">¿Deseas reactivar tu cuenta para volver a reservar clases en GolfClass?</p>
                
                <form action="reactivate.php" method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-2">Sí, ¡Reactivar mi cuenta!</button>
                    <a href="login.php" class="btn btn-outline-secondary w-100">No, volver al inicio</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>