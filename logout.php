<?php
session_start();
session_unset();  // Destruir todas las variables de sesión
session_destroy();  // Destruir la sesión
header("Location: login.php");  // Redirigir a la página de inicio de sesión
exit;
?>


