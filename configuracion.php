<?php
// Incluir archivos necesarios
require_once 'db/funciones.php';
require_once 'db/conexion.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener datos del usuario actual
$usuario = getUsuarioActual();
if (!$usuario) {
    header("Location: login.php");
    exit;
}

// Título de la página
$titulo = "Configuración";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/configuracion.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/configuracion.js'
];

// Incluir el header
$baseUrl = '';
include_once 'includes/header.php';
include_once 'includes/navbar.php';
include_once 'includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title"><?php echo $titulo; ?></h1>
    </div>

    <!-- Layout de configuración -->
    <div class="configuracion-layout">
        <!-- Navegación lateral -->
        <div class="config-nav">
            <div class="config-nav-header">
                <h5>Configuraciones</h5>
            </div>
            <ul class="config-nav-list">
                <li class="config-nav-item active" data-section="preferencias">
                    <i class="bi bi-person-gear me-2"></i>
                    Preferencias Personales
                </li>
                <li class="config-nav-item" data-section="notificaciones">
                    <i class="bi bi-bell me-2"></i>
                    Notificaciones
                </li>
                <li class="config-nav-item" data-section="seguridad">
                    <i class="bi bi-shield-lock me-2"></i>
                    Seguridad y Privacidad
                </li>
                <?php if (esAdmin()): ?>
                    <li class="config-nav-item" data-section="sistema">
                        <i class="bi bi-gear me-2"></i>
                        Configuración del Sistema
                    </li>
                <?php endif; ?>
                <li class="config-nav-item" data-section="acerca">
                    <i class="bi bi-info-circle me-2"></i>
                    Acerca del Sistema
                </li>
            </ul>
        </div>

        <!-- Contenido de configuración -->
        <div class="config-content">
            <!-- Sección de Preferencias Personales -->
            <div id="section-preferencias" class="config-section active">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-gear me-2"></i>
                                    Preferencias Personales
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Configuración básica -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="config-tema" class="form-label">Tema de la interfaz</label>
                                        <select class="form-select" id="config-tema">
                                            <option value="claro">Claro</option>
                                            <option value="oscuro">Oscuro</option>
                                            <option value="auto">Automático</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="config-idioma" class="form-label">Idioma</label>
                                        <select class="form-select" id="config-idioma">
                                            <option value="es">Español</option>
                                            <option value="en">English</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="config-pagina-inicio" class="form-label">Página de inicio</label>
                                        <select class="form-select" id="config-pagina-inicio">
                                            <option value="dashboard">Dashboard</option>
                                            <option value="equipos">Equipos</option>
                                            <option value="mantenimientos">Mantenimientos</option>
                                            <option value="reportes">Reportes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="config-elementos-pagina" class="form-label">Elementos por página</label>
                                        <select class="form-select" id="config-elementos-pagina">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Personalización de Navbar -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="bi bi-palette me-2"></i>
                                        Personalización de Navbar
                                    </h6>

                                    <!-- Diseños predefinidos -->
                                    <div class="mb-3">
                                        <label class="form-label">Diseños predefinidos</label>
                                        <div class="design-options-grid">
                                            <div class="design-option" data-design="default">
                                                <div class="design-preview design-default">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Predeterminado</span>
                                            </div>

                                            <div class="design-option" data-design="dark">
                                                <div class="design-preview design-dark">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Oscuro</span>
                                            </div>

                                            <div class="design-option" data-design="blue">
                                                <div class="design-preview design-blue">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Azul</span>
                                            </div>

                                            <div class="design-option" data-design="green">
                                                <div class="design-preview design-green">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Verde</span>
                                            </div>

                                            <div class="design-option" data-design="superdark">
                                                <div class="design-preview design-superdark">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Super Oscuro</span>
                                            </div>

                                            <div class="design-option" data-design="bluetotal">
                                                <div class="design-preview design-bluetotal">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Azul Total</span>
                                            </div>

                                            <div class="design-option" data-design="custom">
                                                <div class="design-preview design-custom">
                                                    <div class="content-area"></div>
                                                </div>
                                                <span class="design-name">Personalizado</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Controles personalizados -->
                                    <div id="custom-colors-section" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-3">Navbar (Barra lateral)</h6>

                                                <!-- Color de fondo del navbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color de fondo</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="navbar-bg-color" value="#1571b0">
                                                        <input type="text" class="form-control" id="navbar-bg-color-text" value="#1571b0" placeholder="#000000">
                                                    </div>
                                                </div>

                                                <!-- Color del texto del navbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color del texto</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="navbar-text-color" value="#ffffff">
                                                        <input type="text" class="form-control" id="navbar-text-color-text" value="#ffffff" placeholder="#ffffff">
                                                    </div>
                                                </div>

                                                <!-- Color de fondo activo del navbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color de fondo (activo)</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="navbar-active-bg-color" value="#125a8a">
                                                        <input type="text" class="form-control" id="navbar-active-bg-color-text" value="#125a8a" placeholder="#125a8a">
                                                    </div>
                                                </div>

                                                <!-- NUEVO: Color del texto activo del navbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color del texto (activo)</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="navbar-active-text-color" value="#ffffff">
                                                        <input type="text" class="form-control" id="navbar-active-text-color-text" value="#ffffff" placeholder="#ffffff">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-3">Topbar (Barra superior)</h6>

                                                <!-- Color de fondo del topbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color de fondo</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="topbar-bg-color" value="#ffffff">
                                                        <input type="text" class="form-control" id="topbar-bg-color-text" value="#ffffff" placeholder="#ffffff">
                                                    </div>
                                                </div>

                                                <!-- Color del texto del topbar -->
                                                <div class="mb-3">
                                                    <label class="form-label">Color del texto</label>
                                                    <div class="color-input-group">
                                                        <input type="color" class="form-control form-control-color" id="topbar-text-color" value="#333333">
                                                        <input type="text" class="form-control" id="topbar-text-color-text" value="#333333" placeholder="#333333">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Vista previa -->
                                        <div class="mt-4">
                                            <h6 class="fw-bold mb-3">Vista previa</h6>
                                            <div id="navbar-preview" class="navbar-preview">
                                                <div class="preview-sidebar">
                                                    <div class="preview-menu-item">Dashboard</div>
                                                    <div class="preview-menu-item active">Equipos</div>
                                                    <div class="preview-menu-item">Mantenimientos</div>
                                                </div>
                                                <div class="preview-topbar-area">
                                                    <div class="preview-search">Buscar...</div>
                                                    <div class="preview-user">Usuario</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Personalización de Topbar -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="bi bi-layout-navbar me-2"></i>
                                        Personalización de Topbar
                                    </h6>
                                    <p class="text-muted small">Los colores del topbar se configuran junto con el navbar en la sección anterior.</p>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary" id="btn-guardar-preferencias">
                                        <i class="bi bi-check-lg me-1"></i>
                                        Guardar Preferencias
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="config-section" id="section-notificaciones">
                <div class="config-section-header">
                    <h4><i class="bi bi-bell me-2"></i>Notificaciones</h4>
                    <p class="text-muted">Configura cómo y cuándo recibir notificaciones</p>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Notificaciones del Sistema</h6>
                    </div>
                    <div class="config-card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="notif-mantenimientos" checked>
                            <label class="form-check-label" for="notif-mantenimientos">
                                <strong>Mantenimientos Vencidos</strong>
                                <small class="d-block text-muted">Recibir notificaciones cuando un mantenimiento esté vencido</small>
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="notif-equipos" checked>
                            <label class="form-check-label" for="notif-equipos">
                                <strong>Equipos Averiados</strong>
                                <small class="d-block text-muted">Notificar cuando un equipo cambie a estado averiado</small>
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="notif-asignaciones">
                            <label class="form-check-label" for="notif-asignaciones">
                                <strong>Nuevas Asignaciones</strong>
                                <small class="d-block text-muted">Recibir notificaciones de nuevas tareas asignadas</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Frecuencia de Notificaciones</h6>
                    </div>
                    <div class="config-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Recordatorios de mantenimiento</label>
                                <select class="form-select" id="config-frecuencia-mantenimiento">
                                    <option value="inmediato">Inmediato</option>
                                    <option value="diario">Diario</option>
                                    <option value="semanal">Semanal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Resumen de actividades</label>
                                <select class="form-select" id="config-resumen-actividades">
                                    <option value="diario">Diario</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="nunca">Nunca</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="config-actions">
                    <button type="button" class="btn btn-primary" id="btn-guardar-notificaciones">
                        <i class="bi bi-check-lg me-1"></i> Guardar Configuración
                    </button>
                </div>
            </div>

            <!-- Seguridad y Privacidad -->
            <div class="config-section" id="section-seguridad">
                <div class="config-section-header">
                    <h4><i class="bi bi-shield-lock me-2"></i>Seguridad y Privacidad</h4>
                    <p class="text-muted">Gestiona la seguridad de tu cuenta</p>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Sesiones Activas</h6>
                    </div>
                    <div class="config-card-body">
                        <div class="session-item">
                            <div class="session-info">
                                <strong>Sesión Actual</strong>
                                <small class="d-block text-muted">Navegador actual - Iniciada hoy</small>
                            </div>
                            <span class="badge bg-success">Activa</span>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btn-cerrar-otras-sesiones">
                                <i class="bi bi-box-arrow-right me-1"></i> Cerrar Otras Sesiones
                            </button>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Configuración de Privacidad</h6>
                    </div>
                    <div class="config-card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="privacidad-perfil" checked>
                            <label class="form-check-label" for="privacidad-perfil">
                                <strong>Perfil Visible</strong>
                                <small class="d-block text-muted">Permitir que otros usuarios vean mi información de perfil</small>
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="privacidad-actividad">
                            <label class="form-check-label" for="privacidad-actividad">
                                <strong>Mostrar Última Actividad</strong>
                                <small class="d-block text-muted">Mostrar cuándo fue mi última conexión al sistema</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="config-actions">
                    <button type="button" class="btn btn-primary" id="btn-guardar-seguridad">
                        <i class="bi bi-check-lg me-1"></i> Guardar Configuración
                    </button>
                </div>
            </div>

            <!-- Configuración del Sistema (Solo Administradores) -->
            <?php if (esAdmin()): ?>
                <div class="config-section" id="section-sistema">
                    <div class="config-section-header">
                        <h4><i class="bi bi-gear me-2"></i>Configuración del Sistema</h4>
                        <p class="text-muted">Configuraciones globales del sistema</p>
                    </div>

                    <div class="config-card">
                        <div class="config-card-header">
                            <h6>Configuración General</h6>
                        </div>
                        <div class="config-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del Sistema</label>
                                    <input type="text" class="form-control" id="sistema-nombre" value="SIGESMANCOR">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Zona Horaria</label>
                                    <select class="form-select" id="sistema-timezone">
                                        <option value="America/Lima">Lima (UTC-5)</option>
                                        <option value="America/Bogota">Bogotá (UTC-5)</option>
                                        <option value="America/Mexico_City">Ciudad de México (UTC-6)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-header">
                            <h6>Configuración de Mantenimientos</h6>
                        </div>
                        <div class="config-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Días de anticipación para notificaciones</label>
                                    <input type="number" class="form-control" id="sistema-dias-notificacion" value="7" min="1" max="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Auto-generar mantenimientos preventivos</label>
                                    <select class="form-select" id="sistema-auto-preventivo">
                                        <option value="1">Activado</option>
                                        <option value="0">Desactivado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-actions">
                        <button type="button" class="btn btn-primary" id="btn-guardar-sistema">
                            <i class="bi bi-check-lg me-1"></i> Guardar Configuración
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acerca del Sistema -->
            <div class="config-section" id="section-acerca">
                <div class="config-section-header">
                    <h4><i class="bi bi-info-circle me-2"></i>Acerca del Sistema</h4>
                    <p class="text-muted">Información sobre SIGESMANCOR</p>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Información del Sistema</h6>
                    </div>
                    <div class="config-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Nombre:</strong>
                                    <span>SIGESMANCOR</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Versión:</strong>
                                    <span>1.0.0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Descripción:</strong>
                                    <span>Sistema de Gestión de Mantenimiento</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Última Actualización:</strong>
                                    <span><?php echo date('d/m/Y'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="config-card-header">
                        <h6>Soporte Técnico</h6>
                    </div>
                    <div class="config-card-body">
                        <p>Si necesitas ayuda o tienes algún problema con el sistema, puedes contactar al equipo de soporte técnico.</p>
                        <div class="support-actions">
                            <button type="button" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i> Contactar Soporte
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-book me-1"></i> Manual de Usuario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Componente de notificaciones toast -->
    <?php include_once 'componentes/toast/toast.php'; ?>
</div>

<?php
// Incluir el footer
include_once 'includes/footer.php';
?>