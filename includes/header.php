<!DOCTYPE html>
<html lang="es">

<head>
    <title><?php echo $titulo ?? 'SIGESMANCOR - Sistema de Gestión de Mantenimiento'; ?></title>
    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="SIGESMANCOR - Sistema de Gestión y Mantenimiento de Cordial" />
    <meta name="keywords" content="mantenimiento, equipos, gestión, control" />
    <meta name="author" content="SIGESMANCOR" />

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/fonts.css" />
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $baseUrl; ?>assets/img/logo-icon.png" type="image/png" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" />

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/vendor/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css" />

    <?php if (isset($css_adicional)): ?>
        <?php foreach ($css_adicional as $css): ?>
            <link rel="stylesheet" href="<?php echo $baseUrl . $css; ?>" />
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Aplicar estado del sidebar inmediatamente para evitar parpadeo -->
    <script>
        // Aplicar el estado del sidebar antes de que se cargue la página
        (function() {
            var sidebarState = localStorage.getItem('sidebar-collapsed');
            if (sidebarState === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <?php
    // Cargar preferencias del usuario si está autenticado
    $preferencias_usuario = null;
    if (estaAutenticado()) {
        $conexion = new Conexion();
        $preferencias_usuario = $conexion->selectOne(
            "SELECT * FROM preferencias_usuarios WHERE usuario_id = ?",
            [getUsuarioId()]
        );
    }
    ?>

    <script>
        // Cargar preferencias del usuario
        <?php if ($preferencias_usuario): ?>
            window.userPreferences = <?php echo json_encode($preferencias_usuario); ?>;
        <?php else: ?>
            window.userPreferences = null;
        <?php endif; ?>

            // Aplicar preferencias inmediatamente
            (function() {
                if (window.userPreferences) {
                    const prefs = window.userPreferences;

                    // Aplicar colores del navbar si existen
                    if (prefs.navbar_bg_color) {
                        const colors = {
                            navbarBg: prefs.navbar_bg_color,
                            navbarText: prefs.navbar_text_color || '#ffffff',
                            navbarActive: prefs.navbar_active_bg_color || '#125a8a',
                            navbarActiveText: prefs.navbar_active_text_color || '#ffffff',
                            topbarBg: prefs.topbar_bg_color || '#ffffff',
                            topbarText: prefs.topbar_text_color || '#333333'
                        };

                        const css = `
                /* Navbar (Sidebar) */
                .sidebar {
                    background-color: ${colors.navbarBg} !important;
                    color: ${colors.navbarText} !important;
                }
                .sidebar .sidebar-menu-link {
                    color: ${colors.navbarText} !important;
                }
                .sidebar .sidebar-menu-item.active .sidebar-menu-link,
                .sidebar .sidebar-menu-link:hover {
                    background-color: ${colors.navbarActive} !important;
                    color: ${colors.navbarActiveText} !important;
                }
                .sidebar .sidebar-section-title {
                    color: ${colors.navbarText} !important;
                    opacity: 0.8;
                }
                
                /* Logo */
                .sidebar .logo {
                    filter: brightness(0) invert(1);
                }
                
                /* Topbar */
                .topbar {
                    background-color: ${colors.topbarBg} !important;
                    color: ${colors.topbarText} !important;
                }
                .topbar .user-name, .topbar .user-role {
                    color: ${colors.topbarText} !important;
                }
                
                /* Controles de búsqueda y iconos */
                .sidebar-toggle,
                .topbar .btn-icon,
                .topbar .notification-btn {
                    color: ${colors.topbarText} !important;
                }
                
                .search-container .form-control,
                .search-container .input-group-text,
                .search-container .btn {
                    color: ${colors.topbarText} !important;
                }
                
                .search-container .form-control::placeholder {
                    color: ${colors.topbarText} !important;
                    opacity: 0.6;
                }
                
                /* Búsqueda móvil */
                .mobile-search-bar {
                    background-color: ${colors.topbarBg} !important;
                }
                
                .mobile-search-bar .form-control,
                .mobile-search-bar .input-group-text,
                .mobile-search-bar .btn {
                    color: ${colors.topbarText} !important;
                }
                
                .mobile-search-bar .form-control::placeholder {
                    color: ${colors.topbarText} !important;
                    opacity: 0.6;
                }
            `;

                        const style = document.createElement('style');
                        style.id = 'user-navbar-preferences';
                        style.textContent = css;
                        document.head.appendChild(style);
                    }
                }
            })();
    </script>

</head>

<body>
    <!-- Preloader (solo la barra azul) -->
    <div class="loader-track" id="preloader">
        <div class="loader-fill"></div>
    </div>

    <div class="app-container">