<?php
// Iniciar sesión
session_start();

// Incluir funciones
require_once 'db/funciones.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener datos del usuario
$usuario = getUsuarioActual();

// Definir título de la página
$titulo = "Dashboard | SIGESMANCOR";

// Definir la URL base para los assets
$baseUrl = getProjectRoot();

// Incluir CSS adicional para el dashboard
$css_adicional = [
    'assets/css/dashboard.css',
];

// Incluir JS adicional para el dashboard
$js_adicional = [
    'assets/js/vendor/apexcharts/apexcharts.min.js',
    'assets/js/vendor/fullcalendar/index.global.min.js',
    'assets/js/dashboard.js'
];

// Obtener estadísticas para el dashboard
$estadisticas = obtenerEstadisticasDashboard();

// Obtener categorías de equipos para los filtros
$conexion = new Conexion();
$categorias = $conexion->select("SELECT id, nombre FROM categorias_equipos ORDER BY nombre");

// Si no hay categorías, crear un array vacío para evitar errores
if (!$categorias) {
    $categorias = [];
}

// Incluir header
include_once 'includes/header.php';

// Incluir navbar
include_once 'includes/navbar.php';

// Incluir topbar
include_once 'includes/topbar.php';
?>

<!-- Contenido principal -->
<div class="main-content" id="main-content">
    <div class="dashboard-container">
        <!-- Encabezado del Dashboard -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">Bienvenido al Sistema de Gestión de Mantenimiento</p>
            </div>
            <div class="dashboard-actions">
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Actualizar datos" id="refreshDashboard">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dashboardOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill me-1"></i> Opciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dashboardOptionsDropdown">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-download me-2"></i> Exportar datos</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-printer me-2"></i> Imprimir dashboard</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-sliders me-2"></i> Configurar widgets</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="stats-container">
            <!-- Total de Equipos -->
            <div class="stat-card">
                <div class="icon bg-primary-gradient">
                    <i class="bi bi-truck"></i>
                </div>
                <h6 class="label">Total Equipos</h6>
                <h2 class="value counter-value" data-target="<?php echo $estadisticas['totalEquipos']; ?>">0</h2>
                <div class="trend up">
                    <i class="bi bi-arrow-up"></i>
                    <span>5% desde el mes pasado</span>
                </div>
            </div>

            <!-- Equipos Activos -->
            <div class="stat-card">
                <div class="icon bg-success-gradient">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h6 class="label">Equipos Activos</h6>
                <h2 class="value counter-value" data-target="<?php echo $estadisticas['equiposActivos']; ?>">0</h2>
                <div class="trend up">
                    <i class="bi bi-arrow-up"></i>
                    <span>3% desde el mes pasado</span>
                </div>
            </div>

            <!-- Equipos Averiados -->
            <div class="stat-card">
                <div class="icon bg-danger-gradient">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h6 class="label">Equipos Averiados</h6>
                <h2 class="value counter-value" data-target="<?php echo $estadisticas['equiposAveriados']; ?>">0</h2>
                <div class="trend down">
                    <i class="bi bi-arrow-down"></i>
                    <span>2% desde el mes pasado</span>
                </div>
            </div>

            <!-- Mantenimientos Programados -->
            <div class="stat-card">
                <div class="icon bg-warning-gradient">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h6 class="label">Mantenimientos</h6>
                <h2 class="value counter-value" data-target="<?php echo $estadisticas['mantenimientosProgramados']; ?>">0</h2>
                <div class="trend up">
                    <i class="bi bi-arrow-up"></i>
                    <span>8% desde el mes pasado</span>
                </div>
            </div>
        </div>

        <!-- Gráficos principales -->
        <div class="chart-container">
            <!-- Gráfico de historial de mantenimiento -->
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title">Historial de Mantenimiento</h5>
                    <div class="chart-actions">
                        <div class="dropdown filter-dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="historyFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="historyFilterDropdown">
                                <li><a class="dropdown-item active" href="#" data-categoria="todos">Todos los equipos</a></li>
                                <?php foreach ($categorias as $categoria): ?>
                                    <li><a class="dropdown-item" href="#" data-categoria="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="maintenanceHistoryChart"></div>
                </div>
                <div class="time-period">
                    <button class="btn btn-sm" data-period="30d">30 días</button>
                    <button class="btn btn-sm" data-period="3m">3 meses</button>
                    <button class="btn btn-sm active" data-period="1y">1 año</button>
                    <button class="btn btn-sm" data-period="all">Todo</button>
                </div>
            </div>

            <!-- Gráfico de tendencia de mantenimientos -->
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title">Tendencia de Mantenimientos</h5>
                    <div class="chart-actions">
                        <div class="dropdown filter-dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="trendFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="trendFilterDropdown">
                                <li><a class="dropdown-item active" href="#" data-tipo="todos">Todos los tipos</a></li>
                                <li><a class="dropdown-item" href="#" data-tipo="preventivo">Preventivo</a></li>
                                <li><a class="dropdown-item" href="#" data-tipo="correctivo">Correctivo</a></li>
                                <li><a class="dropdown-item" href="#" data-tipo="predictivo">Predictivo</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="maintenanceTrendChart"></div>
                </div>
                <div class="time-period">
                    <button class="btn btn-sm" data-period="7d">7 días</button>
                    <button class="btn btn-sm active" data-period="30d">30 días</button>
                    <button class="btn btn-sm" data-period="90d">90 días</button>
                    <button class="btn btn-sm" data-period="all">Todo</button>
                </div>
            </div>
        </div>

        <!-- Calendario y tabla de mantenimientos -->
        <div class="calendar-table-container">
            <!-- Calendario de mantenimientos -->
            <div class="calendar-card">
                <div class="calendar-header">
                    <h5 class="calendar-title">Calendario de Mantenimientos</h5>
                    <div class="calendar-actions">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Ver todos los eventos">
                            <i class="bi bi-calendar3"></i>
                        </button>
                    </div>
                </div>
                <div id="maintenanceCalendar"></div>
                <div class="chart-legend mt-3">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: var(--primary-gradient-start);"></div>
                        <span>Preventivo</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: var(--danger-gradient-start);"></div>
                        <span>Correctivo</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: var(--warning-gradient-start);"></div>
                        <span>Predictivo</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(200, 200, 200, 0.6);"></div>
                        <span>Completado</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de próximos mantenimientos -->
            <div class="table-card">
                <div class="table-header">
                    <h5 class="table-title">Próximos Mantenimientos</h5>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-outline-primary">
                            Ver todos
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente con JavaScript -->
                            <tr>
                                <td colspan="4" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos adicionales para mejorar la visualización de los tipos de mantenimiento */
    .maintenance-table .status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
        text-align: center;
        min-width: 90px;
    }

    .maintenance-table .status.preventivo {
        background-color: rgba(67, 97, 238, 0.2);
        color: #4361ee;
    }

    .maintenance-table .status.correctivo {
        background-color: rgba(231, 76, 60, 0.2);
        color: #e74c3c;
    }

    .maintenance-table .status.predictivo {
        background-color: rgba(243, 156, 18, 0.2);
        color: #f39c12;
    }

    .maintenance-table .status.completado {
        background-color: rgba(46, 204, 113, 0.2);
        color: #2ecc71;
    }

    .maintenance-table .status.pendiente {
        background-color: rgba(149, 165, 166, 0.2);
        color: #7f8c8d;
    }

    /* Estilos para el calendario */
    .fc-event.preventivo {
        background-color: var(--primary-gradient-start);
        border-color: var(--primary-gradient-start);
    }

    .fc-event.correctivo {
        background-color: var(--danger-gradient-start);
        border-color: var(--danger-gradient-start);
    }

    .fc-event.predictivo {
        background-color: var(--warning-gradient-start);
        border-color: var(--warning-gradient-start);
    }

    .fc-event.completado {
        background-color: rgba(200, 200, 200, 0.6);
        border-color: rgba(180, 180, 180, 0.8);
        color: #555;
    }

    /* Mejora para la visualización de tipos en la tabla */
    .maintenance-table .tipo-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .maintenance-table .tipo-badge i {
        font-size: 0.9rem;
    }

    .maintenance-table .tipo-badge.preventivo i {
        color: #4361ee;
    }

    .maintenance-table .tipo-badge.correctivo i {
        color: #e74c3c;
    }

    .maintenance-table .tipo-badge.predictivo i {
        color: #f39c12;
    }
</style>

<?php
// Incluir footer
include_once 'includes/footer.php';
?>