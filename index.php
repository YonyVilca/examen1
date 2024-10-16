

<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Mostrar las tablas de la base de datos
$db = Database::getInstance();
$conexion = $db->getConnection();

$result = $conexion->query("SHOW TABLES");

// Obtener el nombre de usuario para mostrar en la interfaz
$user_email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <!-- Incluir los estilos de SB Admin 2 -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Contenedor principal -->
    <div id="wrapper">

        <!-- Barra lateral -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Marca de la barra lateral -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-database"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Mi CRUD</div>
            </a>

            <!-- Divisor -->
            <hr class="sidebar-divider my-0">

            <!-- Elementos del menú -->
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Tablas</span></a>
            </li>

            <!-- Divisor -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Botón para ocultar la barra lateral -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- Fin de la barra lateral -->

        <!-- Contenido principal -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Contenido -->
            <div id="content">

                <!-- Barra superior -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Botón de menú para dispositivos móviles -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Barra superior derecha -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Divisor -->
                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Información del usuario -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($user_email); ?></span>
                                <i class="fas fa-user-circle fa-lg"></i>
                            </a>
                            <!-- Menú desplegable del usuario -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="logout.php" onclick="confirmarAccion(event, '¿Estás seguro de que deseas cerrar sesión?')">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar sesión
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- Fin de la barra superior -->

                <!-- Contenido de la página -->
                <div class="container-fluid">

                    <!-- Encabezado de la página -->
                    <h1 class="h3 mb-4 text-gray-800">Tablas disponibles en la base de datos</h1>

                    <!-- Lista de tablas -->
                    <div class="row">
                        <?php
                        while ($row = $result->fetch_array()) {
                            $table_name = $row[0];
                            echo '<div class="col-lg-3 col-md-4 col-sm-6 mb-4">';
                            echo '<div class="card border-left-primary shadow h-100 py-2">';
                            echo '<div class="card-body">';
                            echo '<div class="row no-gutters align-items-center">';
                            echo '<div class="col mr-2">';
                            echo '<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">' . htmlspecialchars($table_name) . '</div>';
                            echo '</div>';
                            echo '<div class="col-auto">';
                            echo '<a href="crud.php?table=' . urlencode($table_name) . '" class="btn btn-primary btn-circle">';
                            echo '<i class="fas fa-arrow-right"></i>';
                            echo '</a>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>

                </div>
                <!-- Fin del contenido de la página -->

            </div>
            <!-- Fin del contenido -->

            <!-- Pie de página -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span> Ulasalle &copy; <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- Fin del pie de página -->

        </div>
        <!-- Fin del contenido principal -->

    </div>
    <!-- Fin del contenedor principal -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Scripts de SB Admin 2 -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Plugin principal JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Scripts personalizados para todas las páginas-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Agregar el script de confirmación -->
    <script>
        function confirmarAccion(event, mensaje) {
            if (!confirm(mensaje)) {
                event.preventDefault();
            }
        }
    </script>

</body>

</html>






