<?php
session_start();
session_unset();   // Elimina las variables de sesión
session_destroy(); // Destruye la sesión por completo

// Redirigir al inicio
header("Location: ../../index.php");
exit();
?>