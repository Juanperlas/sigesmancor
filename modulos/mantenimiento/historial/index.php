<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('mantenimientos.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Historial de Mantenimientos";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/plugins/datepicker/css/bootstrap-datepicker.min.css',
    'assets/css/mantenimiento/historial/historial.css',
    'componentes/image-upload/image-upload.css',
    'componentes/image-viewer/image-viewer.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'assets/plugins/datepicker/js/bootstrap-datepicker.min.js',
    'assets/plugins/datepicker/js/locales/bootstrap-datepicker.es.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/image-upload/image-upload.js',
    'componentes/image-viewer/image-viewer.js',
    'componentes/toast/toast.js',
    'assets/js/mantenimiento/historial/historial.js'
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
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-header">Filtros</div>
        <div class="filtros-content">
            <div class="filtro-grupo">
                <label for="filtro-tipo-mantenimiento" class="filtro-label">Tipo de Mantenimiento</label>
                <select id="filtro-tipo-mantenimiento" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="correctivo">Correctivo</option>
                    <option value="preventivo">Preventivo</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-tipo-item" class="filtro-label">Tipo de Ítem</label>
                <select id="filtro-tipo-item" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="equipo">Equipos</option>
                    <option value="componente">Componentes</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-fecha-desde" class="filtro-label">Fecha Desde</label>
                <input type="text" id="filtro-fecha-desde" class="filtro-select datepicker" placeholder="DD/MM/AAAA">
            </div>
            <div class="filtro-grupo">
                <label for="filtro-fecha-hasta" class="filtro-label">Fecha Hasta</label>
                <input type="text" id="filtro-fecha-hasta" class="filtro-select datepicker" placeholder="DD/MM/AAAA">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="mantenimientos-layout">
        <!-- Tabla de mantenimientos -->
        <div class="mantenimientos-table-container">
            <div class="table-container">
                <table id="historial-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="40">ID</th>
                            <th width="50">Imagen</th>
                            <th>Tipo</th>
                            <th>Código</th>
                            <th>Equipo/Componente</th>
                            <th>Fecha y Hora</th>
                            <th>Orómetro</th>
                            <th width="80">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="historial-detalle" class="mantenimientos-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Mantenimiento</h2>
                <p class="detail-subtitle">Seleccione un mantenimiento para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un mantenimiento para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del mantenimiento -->
    <div class="modal fade" id="modal-detalle-mantenimiento" tabindex="-1" aria-labelledby="modal-detalle-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-titulo">Detalles del Mantenimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <!-- Información del mantenimiento -->
                        <div class="col-md-3 text-center mb-2">
                            <div class="mantenimiento-imagen-container">
                                <img id="detalle-imagen" src="<?php echo $baseUrl; ?>assets/img/mantenimiento/default.png" alt="Imagen del mantenimiento" class="img-fluid rounded mb-1">
                                <button type="button" id="btn-ver-imagen" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-search-plus me-1"></i> Ampliar
                                </button>
                            </div>
                            <div class="mt-1">
                                <span id="detalle-estado" class="badge rounded-pill bg-success">Completado</span>
                            </div>
                            <div class="mt-1">
                                <span id="detalle-tipo-mantenimiento" class="badge rounded-pill bg-info">Tipo</span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 id="detalle-nombre" class="fs-5 mb-2">Nombre del Equipo/Componente</h4>
                            <!-- Tarjetas de información -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Código:</span>
                                                <span id="detalle-codigo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Tipo Ítem:</span>
                                                <span id="detalle-tipo-item" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha Problema:</span>
                                                <span id="detalle-fecha-problema" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha Realizado:</span>
                                                <span id="detalle-fecha-realizado" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-speedometer2 me-2"></i>Información de Orómetro
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Tipo de Orómetro:</span>
                                                <span id="detalle-tipo-orometro" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Orómetro al Completar:</span>
                                                <span id="detalle-orometro" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-chat-left-text me-2"></i>Descripción y Observaciones
                                </div>
                                <div class="detalle-card-body">
                                    <div class="detalle-item mb-2">
                                        <span class="detalle-label">Descripción:</span>
                                        <p id="detalle-descripcion" class="detalle-valor mb-0">-</p>
                                    </div>
                                    <div class="detalle-item">
                                        <span class="detalle-label">Observaciones:</span>
                                        <p id="detalle-observaciones" class="detalle-valor mb-0">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="modal-footer">
                        <button type="button" id="btn-generar-reporte" class="btn btn-sm btn-danger">
                            <i class="bi bi-file-pdf me-1"></i> Generar Informe
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Componente de visualización de imágenes -->
    <?php include_once '../../../componentes/image-viewer/image-viewer.php'; ?>

    <!-- Componente de notificaciones toast -->
    <?php include_once '../../../componentes/toast/toast.php'; ?>

    <?php
    // Incluir el footer
    include_once '../../../includes/footer.php';
    ?>