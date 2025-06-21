<?php
// Iniciar sesión
session_start();

// Incluir funciones
require_once '../../../db/funciones.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener datos del usuario
$usuario = getUsuarioActual();

// Definir título de la página
$titulo = "Calendario de Mantenimientos Preventivos | SIGESMANCOR";

// Definir la URL base para los assets
$baseUrl = getProjectRoot();

// Incluir CSS adicional
$css_adicional = [
    'assets/css/mantenimiento/preventivo/calendario.css',
];

// Incluir JS adicional
$js_adicional = [
    'assets/js/vendor/fullcalendar/index.global.min.js',
    'assets/js/mantenimiento/preventivo/calendario.js'
];

// Incluir header
include_once '../../../includes/header.php';

// Incluir navbar
include_once '../../../includes/navbar.php';

// Incluir topbar
include_once '../../../includes/topbar.php';
?>

<!-- Contenido principal -->
<div class="main-content" id="main-content">
    <div class="container-fluid">
        <!-- Encabezado de la página -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">Calendario de Mantenimientos Preventivos</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo getPageUrl('dashboard.php'); ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo getPageUrl('modulos/mantenimiento/preventivo/index.php'); ?>">Mantenimiento Preventivo</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Calendario</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-auto">
                    <div class="page-actions">
                        <a href="<?php echo getPageUrl('modulos/mantenimiento/preventivo/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Volver
                        </a>
                        <button id="refreshCalendar" class="btn btn-primary" onclick="location.reload();">
                            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout principal -->
        <div class="calendario-layout">
            <!-- Tarjeta del calendario -->
            <div class="calendario-container">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title">Calendario de Mantenimientos</h5>
                            </div>
                            <div class="col-auto">
                                <div class="calendar-legend">
                                    <div class="legend-item">
                                        <div class="legend-color bg-warning"></div>
                                        <span>Pendiente</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color bg-info"></div>
                                        <span>Completado</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="preventiveMaintenanceCalendar"></div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral de mantenimientos -->
            <div class="mantenimientos-panel">
                <!-- Próximos mantenimientos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-calendar-check me-2"></i>
                            Próximos Mantenimientos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="proximosMantenimientosTable">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipo</th>
                                        <th>Fecha</th>
                                        <th>Días</th>
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

                <!-- Mantenimientos recientes -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-check2-circle me-2"></i>
                            Mantenimientos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="mantenimientosRecientesTable">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipo</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center">Cargando datos...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del mantenimiento -->
<div class="modal fade" id="maintenanceDetailsModal" tabindex="-1" aria-labelledby="maintenanceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceDetailsModalLabel">Detalles del Mantenimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="maintenance-details">
                    <div class="row mb-3">
                        <div class="col-4 fw-bold">Equipo:</div>
                        <div class="col-8" id="modalEquipo"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 fw-bold">Descripción:</div>
                        <div class="col-8" id="modalDescripcion"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 fw-bold">Fecha Programada:</div>
                        <div class="col-8" id="modalFechaProgramada"></div>
                    </div>
                    <div class="row mb-3" id="rowFechaRealizada">
                        <div class="col-4 fw-bold">Fecha Realizada:</div>
                        <div class="col-8" id="modalFechaRealizada"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 fw-bold">Estado:</div>
                        <div class="col-8">
                            <span class="badge" id="modalEstado"></span>
                        </div>
                    </div>
                    <div class="row mb-3" id="rowObservaciones">
                        <div class="col-4 fw-bold">Observaciones:</div>
                        <div class="col-8" id="modalObservaciones"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="btnVerDetalle">Ver Detalle Completo</a>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once '../../../includes/footer.php';
?>