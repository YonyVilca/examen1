<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Aplicación</title>
    <!-- Importar CSS de SB Admin 2 -->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Contenedor de la página -->
    <div id="wrapper">
        <!-- Barra lateral -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Marca de la barra lateral -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-text mx-3">Mi Aplicación</div>
            </a>
            <!-- Divisor -->
            <hr class="sidebar-divider my-0">
            <!-- Elementos del menú -->
            <!-- Puedes agregar enlaces a diferentes tablas o funcionalidades -->
            <li class="nav-item">
                <a class="nav-link" href="crud.php?table=users">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <!-- Agrega más elementos según tus tablas -->
        </ul>
        <!-- Contenido de la página -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Barra superior -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Barra superior (Topbar) -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Información del usuario -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['email']); ?>
                                </span>
                                <i class="fas fa-user-circle fa-lg"></i>
                            </a>
                            <!-- Menú desplegable del usuario -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar sesión
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- Inicio del contenido principal -->
                <div class="container-fluid">

