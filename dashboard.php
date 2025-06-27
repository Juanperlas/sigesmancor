```php
<?php
// Incluir archivos necesarios
require_once 'db/funciones.php';
require_once 'db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('dashboard.ver')) {
    header("Location: login.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Dashboard - Panel de Control";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/dashboard.css',
    'assets/vendor/bootstrap-icons/bootstrap-icons.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/vendor/chartjs/chart.min.js',
    'assets/js/vendor/apexcharts/apexcharts.min.js',
    'assets/js/dashboard.js'
];

// Incluir el header
$baseUrl = '';
include_once 'includes/header.php';
include_once 'includes/navbar.php';
include_once 'includes/topbar.php';
?>

<div id="main-content" class="main-content dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="dashboard-title-section">
            <h1 class="dashboard-title">
                <i class="bi bi-speedometer2"></i>
                Panel de Control - SIGESMANCOR
            </h1>
            <p class="dashboard-subtitle">Sistema de Gestión de Mantenimiento CORDIAL SAC</p>
        </div>
        <div class="dashboard-actions">
            <button type="button" class="btn-dashboard-action" id="btn-exportar-resumen">
                <i class="bi bi-download"></i>
                Exportar Resumen
            </button>
            <button type="button" class="btn-dashboard-action" id="btn-actualizar-datos">
                <i class="bi bi-arrow-clockwise"></i>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas Principales -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="total-equipos">0</div>
                <div class="stat-label">Total Equipos</div>
                <div class="stat-change positive" id="equipos-change">+0 este mes</div>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="equipos-activos">0</div>
                <div class="stat-label">Equipos Activos</div>
                <div class="stat-change positive" id="activos-percentage">0%</div>
            </div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="bi bi-tools"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="mantenimientos-pendientes">0</div>
                <div class="stat-label">Mantenimientos Pendientes</div>
                <div class="stat-change neutral" id="pendientes-change">Programados</div>
            </div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="equipos-criticos">0</div>
                <div class="stat-label">Equipos Críticos</div>
                <div class="stat-change negative" id="criticos-change">Requieren atención</div>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficas y Análisis -->
    <div class="dashboard-grid">
        <!-- Gráfica de Estado de Equipos -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-pie-chart-fill"></i>
                    Estado de Equipos
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-equipos">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-estado-equipos"></canvas>
            </div>
        </div>

        <!-- Gráfica de Mantenimientos por Mes -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bar-chart-fill"></i>
                    Mantenimientos por Mes
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-mantenimientos">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-mantenimientos-mes"></canvas>
            </div>
        </div>

        <!-- Distribución por Ubicación -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-geo-alt-fill"></i>
                    Distribución por Ubicación
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-ubicaciones">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-ubicaciones"></canvas>
            </div>
        </div>
    </div>

    <!-- Sección de Tablas Detalladas -->
    <div class="dashboard-tables">
        <!-- Equipos que Requieren Atención -->
        <div class="dashboard-card table-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Equipos que Requieren Atención
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-atencion">
                        <i class="bi bi-file-earmark-excel"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="dashboard-table" id="tabla-equipos-atencion">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Próximo Mantenimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Últimos Mantenimientos Realizados -->
        <div class="dashboard-card table-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-check2-circle"></i>
                    Últimos Mantenimientos Realizados
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-ver-todo-historial">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="dashboard-table" id="tabla-ultimos-mantenimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Equipo</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Alertas y Notificaciones -->
    <div class="alerts-section">
        <div class="dashboard-card alerts-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bell-fill"></i>
                    Alertas y Notificaciones
                </h3>
                <div class="card-actions">
                    <span class="alert-count" id="total-alertas">0</span>
                </div>
            </div>
            <div class="card-content">
                <div class="alerts-container" id="container-alertas">
                    <div class="alert-loading">
                        <div class="spinner"></div>
                        <span>Cargando alertas...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer del Dashboard -->
    <div class="dashboard-footer">
        <div class="footer-info">
            <p><strong>SIGESMANCOR</strong> - Sistema de Gestión de Mantenimiento</p>
            <p>CORDIAL SAC © <?php echo date('Y'); ?> - Última actualización: <span id="ultima-actualizacion">--</span></p>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once 'includes/footer.php';
?>
```