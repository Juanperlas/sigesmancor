<?php
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('estadisticas.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Estadísticas de Trabajo";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/mantenimiento/estadistica/estadistica.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'assets/js/vendor/apexcharts/apexcharts.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/mantenimiento/estadistica/estadistica.js'
];

// Incluir el header
$baseUrl = '../../../';
include_once '../../../includes/header.php';
include_once '../../../includes/navbar.php';
include_once '../../../includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <!-- Cabecera compacta -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="page-title"><?php echo $titulo; ?></h1>
        <div class="page-actions">
            <button class="btn btn-sm btn-outline-secondary" id="btn-actualizar" data-bs-toggle="tooltip" title="Actualizar datos">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- Tabs principales -->
    <ul class="nav nav-tabs estadistica-tabs" id="estadisticaTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="true">
                <i class="bi bi-table me-2"></i>Historial de Trabajo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="graficas-tab" data-bs-toggle="tab" data-bs-target="#graficas" type="button" role="tab" aria-controls="graficas" aria-selected="false">
                <i class="bi bi-graph-up me-2"></i>Gráficas de Trabajo
            </button>
        </li>
    </ul>

    <!-- Contenido de los tabs -->
    <div class="tab-content estadistica-tab-content" id="estadisticaTabContent">
        <!-- Tab de Historial -->
        <div class="tab-pane fade show active" id="historial" role="tabpanel" aria-labelledby="historial-tab">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Historial de Trabajo Diario
                        </h5>
                        <div class="card-actions">
                            <button class="btn btn-sm btn-outline-primary" id="btn-exportar-historial">
                                <i class="bi bi-download me-1"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros con diseño de componentes -->
                    <div class="filtros-container">
                        <div class="filtros-header">Filtros</div>
                        <div class="filtros-content">
                            <div class="filtro-grupo">
                                <label for="fecha-desde-historial" class="filtro-label">Fecha Desde</label>
                                <input type="date" class="filtro-select" id="fecha-desde-historial">
                            </div>
                            <div class="filtro-grupo">
                                <label for="fecha-hasta-historial" class="filtro-label">Fecha Hasta</label>
                                <input type="date" class="filtro-select" id="fecha-hasta-historial">
                            </div>
                            <div class="filtro-grupo">
                                <label for="tipo-historial" class="filtro-label">Tipo</label>
                                <select class="filtro-select" id="tipo-historial">
                                    <option value="">Todos</option>
                                    <option value="equipo">Equipos</option>
                                    <option value="componente">Componentes</option>
                                </select>
                            </div>
                            <div class="filtro-grupo">
                                <label for="fuente-historial" class="filtro-label">Fuente</label>
                                <select class="filtro-select" id="fuente-historial">
                                    <option value="">Todas</option>
                                    <option value="manual">Manual</option>
                                    <option value="automatico">Automático</option>
                                </select>
                            </div>
                            <div class="filtros-actions">
                                <button id="btn-aplicar-filtros-historial" class="btn-aplicar">
                                    <i class="bi bi-funnel"></i> Aplicar
                                </button>
                                <button id="btn-limpiar-filtros-historial" class="btn-limpiar">
                                    <i class="bi bi-x"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de historial con diseño de componentes -->
                    <div class="table-container">
                        <table id="tabla-historial" class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th width="100">Fecha</th>
                                    <th>Equipo/Componente</th>
                                    <th width="100">Código</th>
                                    <th width="100">Tipo</th>
                                    <th width="120">Horas Trabajadas</th>
                                    <th width="100">Fuente</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="d-flex justify-content-center align-items-center py-3">
                                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            Cargando datos...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Gráficas -->
        <div class="tab-pane fade" id="graficas" role="tabpanel" aria-labelledby="graficas-tab">
            <div class="row">
                <!-- Panel de selección -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear me-2"></i>Configuración de Gráficas
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Filtros de fecha -->
                            <div class="mb-3">
                                <label class="form-label-sm">Período de Análisis</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" id="fecha-desde-grafica">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" id="fecha-hasta-grafica">
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo de elemento -->
                            <div class="mb-3">
                                <label class="form-label-sm">Tipo de Elemento</label>
                                <select class="form-select form-select-sm" id="tipo-elemento-grafica">
                                    <option value="equipo">Equipos</option>
                                    <option value="componente">Componentes</option>
                                </select>
                            </div>

                            <!-- Lista de elementos disponibles -->
                            <div class="mb-3">
                                <label class="form-label-sm">Elementos Disponibles</label>
                                <div class="elementos-disponibles" id="elementos-disponibles">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <div class="mt-2">Cargando elementos...</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Elementos seleccionados -->
                            <div class="mb-3">
                                <label class="form-label-sm">Elementos Seleccionados</label>
                                <div class="elementos-seleccionados" id="elementos-seleccionados">
                                    <div class="text-muted text-center py-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Seleccione elementos para graficar
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" id="btn-generar-grafica">
                                    <i class="bi bi-graph-up me-1"></i>Generar Gráfica
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar-seleccion">
                                    <i class="bi bi-x-circle me-1"></i>Limpiar Selección
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de gráficas -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-bar-chart me-2"></i>Gráfica de Horas Trabajadas
                                </h5>
                                <div class="card-actions">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary grafica-tipo active" data-tipo="line">
                                            <i class="bi bi-graph-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary grafica-tipo" data-tipo="bar">
                                            <i class="bi bi-bar-chart"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary grafica-tipo" data-tipo="area">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary ms-2" id="btn-exportar-grafica">
                                        <i class="bi bi-download me-1"></i>Exportar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="grafica-trabajo" class="grafica-container">
                                <div class="grafica-placeholder">
                                    <div class="text-center py-5">
                                        <i class="bi bi-graph-up grafica-placeholder-icon"></i>
                                        <h5 class="mt-3">Gráfica de Horas Trabajadas</h5>
                                        <p class="text-muted">Seleccione elementos y genere la gráfica para visualizar las horas trabajadas por día</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Componente de notificaciones toast -->
<?php include_once '../../../componentes/toast/toast.php'; ?>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>
