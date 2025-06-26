<?php
// Obtener la página actual para marcar el elemento activo
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
$request_uri = $_SERVER['REQUEST_URI'];
$request_parts = explode('/', trim($request_uri, '/'));

// Mejorar la detección de la ruta actual
$pagina_ruta = '';
if (count($request_parts) > 0) {
    // Eliminar el nombre del archivo y la extensión
    $last_part = end($request_parts);
    if (strpos($last_part, '.php') !== false) {
        array_pop($request_parts);
    }

    // Reconstruir la ruta
    if (count($request_parts) > 0) {
        $pagina_ruta = implode('/', $request_parts);
    }
}

// Verificar si estamos en un módulo específico
$is_equipos = strpos($pagina_ruta, 'modulos/equipos/equipos') !== false;
$is_componentes = strpos($pagina_ruta, 'modulos/equipos/componentes') !== false;
$is_preventivo = strpos($pagina_ruta, 'modulos/mantenimiento/preventivo') !== false;
$is_correctivo = strpos($pagina_ruta, 'modulos/mantenimiento/correctivo') !== false;
$is_programado = strpos($pagina_ruta, 'modulos/mantenimiento/programado') !== false;
$is_historial = strpos($pagina_ruta, 'modulos/mantenimiento/historial') !== false;
$is_estadistica = strpos($pagina_ruta, 'modulos/estadistica/historial') !== false;
$is_usuarios = strpos($pagina_ruta, 'modulos/administracion/usuarios') !== false;
$is_personal = strpos($pagina_ruta, 'modulos/administracion/personal') !== false;
$is_categorias = strpos($pagina_ruta, 'modulos/administracion/categorias') !== false;
$is_horarios = strpos($pagina_ruta, 'modulos/administracion/horarios') !== false;
$is_roles = strpos($pagina_ruta, 'modulos/administracion/roles') !== false;


// Obtener datos del usuario
$usuario = getUsuarioActual();
?>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo getPageUrl('dashboard.php'); ?>" class="sidebar-logo">
            <img src="<?php echo getAssetUrl('assets/img/logo.png'); ?>" alt="SIGESMANCOR" class="logo logo-expanded" />
            <img src="<?php echo getAssetUrl('assets/img/logo-icon.png'); ?>" alt="SIGESMANCOR" class="logo logo-collapsed" />
        </a>
    </div>

    <div class="sidebar-content">
        <!-- Dashboard -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">General</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('dashboard.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Equipos -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Equipos</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?php echo $is_equipos ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/equipos/equipos/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-truck"></i>
                        <span>Equipos</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo $is_componentes ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/equipos/componentes/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-gear"></i>
                        <span>Componentes</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Mantenimiento -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Mantenimiento</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?php echo $is_preventivo ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/mantenimiento/preventivo/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-tools"></i>
                        <span>Preventivo</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo $is_correctivo ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/mantenimiento/correctivo/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>Correctivo</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo $is_programado ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/mantenimiento/programado/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-calendar-check"></i>
                        <span>Programado</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo $is_historial ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('modulos/mantenimiento/historial/index.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Historial</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Administración (solo para administradores) -->
        <?php if (esAdmin()): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administración</div>
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item <?php echo $is_usuarios ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/administracion/usuarios/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-people"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item <?php echo $is_personal ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/administracion/personal/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-person-badge"></i>
                            <span>Personal</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item <?php echo $is_categorias ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/administracion/categorias/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-tags"></i>
                            <span>Categorías</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item <?php echo $is_horarios ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/administracion/horarios/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-clock"></i>
                            <span>Horarios</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item <?php echo $is_estadistica ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/mantenimiento/estadistica/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Estadística</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item <?php echo $is_roles ? 'active' : ''; ?>">
                        <a href="<?php echo getPageUrl('modulos/administracion/rolespermisos/index.php'); ?>" class="sidebar-menu-link">
                            <i class="bi bi-shield-lock"></i>
                            <span>Roles y Permisos</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>