<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Procesamiento del formulario de inicio de sesión
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Obtener la instancia de la base de datos
    $db = Database::getInstance();
    $conexion = $db->getConnection();

    // Consulta para obtener el hash de la contraseña
    $stmt = $conexion->prepare("SELECT id, email, password, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user_data = $result->fetch_assoc();

        // Verificar la contraseña
        if ($password === $user_data['password']) {
            if ($user_data['status'] == 1) {
                // Usuario activo, iniciar sesión
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['email'] = $user_data['email'];

                // Redirigir al CRUD o página de inicio
                header("Location: index.php");
                exit;
            } else {
                $error_message = "Su cuenta está inactiva. Por favor, contacte al administrador.";
            }
        } else {
            $error_message = "Contraseña incorrecta.";
        }
    } else {
        $error_message = "Correo electrónico no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <!-- Incluir los estilos de SB Admin 2 -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Fila central -->
        <div class="row justify-content-center">

            <div class="col-xl-6 col-lg-6 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Fila anidada dentro del cuerpo de la tarjeta -->
                        <div class="row">
                            <!-- <div class="col-lg-6 d-none d-lg-block bg-login-image"></div> -->
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">¡Bienvenido de nuevo!</h1>
                                    </div>

                                    <?php if (isset($error_message)) : ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form class="user" method="POST" action="login.php">
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-control form-control-user" placeholder="Correo electrónico" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user" placeholder="Contraseña" required>
                                        </div>
                                        <input type="submit" value="Iniciar sesión" class="btn btn-primary btn-user btn-block">
                                    </form>
                                    <hr>
                                    <!-- Puedes agregar enlaces o información adicional aquí -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Incluir los scripts de SB Admin 2 -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Script personalizado para todas las páginas-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>

