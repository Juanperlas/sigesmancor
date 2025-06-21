<?php
// Iniciar sesión
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Incluir conexión a la base de datos y funciones
require_once 'db/conexion.php';
require_once 'db/funciones.php';

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizar($_POST['nombre_usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($username) || empty($contrasena)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        $conexion = new Conexion();
        // Consultamos solo id, username, nombre_completo y contrasena, verificando esta_activo
        $usuario = $conexion->selectOne(
            "SELECT id, username, nombre_completo, contrasena
             FROM usuarios
             WHERE username = ? AND esta_activo = 1",
            [$username]
        );

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            // Login exitoso
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_username'] = $usuario['username'];

            // Obtener roles del usuario
            $roles = $conexion->getUserRoles($usuario['id']);
            $_SESSION['usuario_roles'] = $roles;

            // Registrar inicio de sesión
            registrarInicioSesion($usuario['id']);

            // Redirigir a la página solicitada o al dashboard
            $redirigir = $_SESSION['redirigir_despues_login'] ?? 'dashboard.php';
            unset($_SESSION['redirigir_despues_login']);

            header("Location: $redirigir");
            exit;
        } else {
            $error = 'Credenciales incorrectas o usuario no activo';
        }
    }
}

// Para demostración, permitir login sin verificar credenciales
if (isset($_GET['demo'])) {
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nombre'] = 'Administrador Demo';
    $_SESSION['usuario_username'] = 'admin';
    $_SESSION['usuario_roles'] = ['admin'];

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Login | SIGESMAN CORDIAL</title>
    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="SIGESMAN CORDIAL - Sistema de Gestión y Mantenimiento de Cordial" />
    <meta name="keywords" content="mantenimiento, equipos, gestión, control" />
    <meta name="author" content="SIGESMANCOR" />

    <!-- Favicon -->
    <link rel="icon" href="assets/img/logo-icon.png" type="image/png" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Principal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/hover.css@2.3.2/css/hover-min.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/spinkit@2.0.1/spinkit.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css" />
</head>

<body>
    <!-- Preloader más sutil -->
    <div class="preloader">
        <div class="spinner">
            <div class="double-bounce1"></div>
            <div class="double-bounce2"></div>
        </div>
    </div>

    <!-- Canvas para Three.js -->
    <canvas id="bg-canvas"></canvas>

    <!-- Contenedor principal -->
    <div class="login-container">
        <!-- Partículas interactivas -->
        <div id="particles-container"></div>

        <!-- Orbe flotante -->
        <div class="floating-orb"></div>
        <div class="floating-orb orb-secondary"></div>

        <!-- Tarjeta de login -->
        <div class="login-card">
            <!-- Efecto de brillo -->
            <div class="glow-effect"></div>

            <!-- Logo y branding -->
            <div class="brand-section">
                <div class="logo-container">
                    <img src="assets/img/logo.png" alt="SIGESMANCOR" class="logo">
                </div>
                <h1 class="brand-title">SIGESMAN CORDIAL</h1>
                <p class="brand-subtitle">Sistema de Gestión y Mantenimiento de Cordial</p>
            </div>

            <!-- Formulario de login -->
            <div class="form-section">
                <div class="welcome-text">
                    <h2>Bienvenido</h2>
                    <p>Ingrese sus credenciales para acceder al sistema</p>
                </div>

                <!-- Contenedor para animación de éxito (inicialmente oculto) -->
                <div id="success-animation" class="d-none">
                    <div id="lottie-success"></div>
                </div>

                <!-- Alerta de error -->
                <?php if (!empty($error)): ?>
                    <div class="error-alert animate__animated animate__headShake">
                        <i class="ri-error-warning-line"></i>
                        <span><?php echo $error; ?></span>
                        <button type="button" class="close-alert">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form id="loginForm" method="post" action="login.php" class="login-form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="ri-user-line"></i>
                            <input type="text" id="nombre_usuario" name="nombre_usuario" placeholder="Nombre de usuario" required>
                            <label for="nombre_usuario">Nombre de usuario</label>
                            <div class="input-focus-effect"></div>
                        </div>
                    </div>

                    <!-- Dentro del formulario, corregir el campo de contraseña -->
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="ri-lock-line"></i>
                            <input type="password" id="contrasena" name="contrasena" placeholder=" " required>
                            <label for="contrasena">Contraseña</label>
                            <button type="button" class="password-toggle" tabindex="-1">
                                <i class="ri-eye-line"></i>
                            </button>
                            <div class="input-focus-effect"></div>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-option">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="remember" name="remember">
                                <span class="checkmark"></span>
                                <span class="label-text">Recordarme</span>
                            </label>
                        </div>
                        <a href="#" class="forgot-link">¿Olvidó su contraseña?</a>
                    </div>

                    <!-- Mejorar el botón de inicio de sesión -->
                    <button type="submit" id="loginButton" class="login-button" style="opacity: 1 !important; visibility: visible !important;">
                        <span class="button-text">Iniciar Sesión</span>
                        <span class="button-icon"><i class="ri-arrow-right-line"></i></span>
                    </button>

                    <!--<div class="demo-access">
                        <a href="login.php?demo=1" class="demo-link">
                            <i class="ri-rocket-line"></i> Acceso Demo
                        </a>
                    </div>-->
                </form>
            </div>

            <!-- Información de la empresa -->
            <div class="company-info">
                <div class="company-logo">
                    <img src="assets/img/logo-icon.png" alt="CORDIAL SAC">
                </div>
                <div class="company-name">
                    CORDIAL SAC
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> SIGESMAN CORDIAL - CORDIAL SAC. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>

</html>