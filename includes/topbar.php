<?php
// Asumimos que $usuario ya está definido (desde getUsuarioActual())
$rol_mostrado = isset($usuario['roles']) && !empty($usuario['roles']) ? ucfirst($usuario['roles'][0]) : 'Usuario';
?>
<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <button class="sidebar-toggle d-none d-lg-block" id="sidebarToggleLg">
            <i class="bi bi-list"></i>
        </button>

        <div class="search-container d-none d-md-block">
            <form class="search-form">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-0 shadow-none" placeholder="Buscar...">
                    <button type="button" class="btn btn-light border-0">
                        <i class="bi bi-sliders"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-actions">
            <button class="btn btn-icon d-md-none" id="searchToggle">
                <i class="bi bi-search"></i>
            </button>

            <div class="dropdown">
                <button class="btn btn-icon notification-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger badge-dot"></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <div class="dropdown-header d-flex align-items-center justify-content-between"  style="color: white;">
                        <h7 class="mb-0">Notificaciones</h7>
                        <a href="#" class="marcar">Marcar todas como leídas</a>
                    </div>
                    <div class="dropdown-body">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-circle">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Mantenimiento vencido</h6>
                                        <p class="mb-0 small text-muted">Hace 2 horas</p>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle">
                                            <i class="bi bi-tools"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Nuevo mantenimiento asignado</h6>
                                        <p class="mb-0 small text-muted">Hace 5 horas</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="dropdown-footer">
                        <a href="#" class="text-primary">Ver todas</a>
                    </div>
                </div>
            </div>

            <div class="dropdown ms-2">
                <button class="btn user-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-none d-md-flex align-items-center">
                        <div class="user-info me-2 text-end">
                            <h6 class="user-name mb-0"><?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario'; ?></h6>
                            <span class="user-role"><?php echo $rol_mostrado; ?></span>
                        </div>
                        <div class="avatar-container">
                            <img src="<?php echo isset($usuario['fotografia']) && !empty($usuario['fotografia']) ? getAssetUrl($usuario['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png'); ?>" alt="<?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario'; ?>" class="avatar">
                        </div>
                    </div>
                    <div class="d-flex d-md-none">
                        <div class="avatar-container">
                            <img src="<?php echo isset($usuario['fotografia']) && !empty($usuario['fotografia']) ? getAssetUrl($usuario['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png'); ?>" alt="<?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario'; ?>" class="avatar">
                        </div>
                    </div>
                </button>
                <div class="dropdown-menu dropdown-menu-end user-dropdown">
                    <div class="dropdown-header">
                        <div class="user-info">
                            <h6 class="mb-0"><?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario'; ?></h6>
                            <p class="text-muted small mb-0"><?php echo $rol_mostrado; ?></p>
                        </div>
                    </div>
                    <div class="dropdown-body">
                        <a href="<?php echo getPageUrl('perfil.php'); ?>" class="dropdown-item">
                            <i class="bi bi-person"></i>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="<?php echo getPageUrl('configuracion.php'); ?>" class="dropdown-item">
                            <i class="bi bi-gear"></i>
                            <span>Configuración</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo getPageUrl('logout.php'); ?>" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-search-bar">
    <div class="container-fluid">
        <form class="search-form">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control border-0 shadow-none" placeholder="Buscar...">
                <button type="button" class="btn btn-transparent" id="closeSearch">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>