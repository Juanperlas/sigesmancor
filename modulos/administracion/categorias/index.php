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
if (!tienePermiso('administracion.personal.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Gestión de Categorías";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/administracion/categorias/categorias.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/administracion/categorias/categorias.js'
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
                <label for="filtro-buscar" class="filtro-label">Buscar</label>
                <input type="text" id="filtro-buscar" class="filtro-input" placeholder="Buscar por nombre...">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
                <?php if (tienePermiso('administracion.categorias.crear')): ?>
                    <button type="button" id="btn-nueva-categoria" class="btn-nuevo">
                        <i class="bi bi-plus-circle"></i> Nueva Categoría
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="categorias-layout">
        <!-- Tabla de categorías -->
        <div class="categorias-table-container">
            <div class="table-container">
                <table id="categorias-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="100">ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th width="120">Total Equipos</th>
                            <th width="150">Fecha Creación</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="categoria-detalle" class="categorias-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles de la Categoría</h2>
                <p class="detail-subtitle">Seleccione una categoría para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione una categoría para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar categoría -->
    <div class="modal fade" id="modal-categoria" tabindex="-1" aria-labelledby="modal-categoria-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-categoria-titulo">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-categoria">
                        <input type="hidden" id="categoria-id" name="id">

                        <div class="row g-3">
                            <!-- Información básica -->
                            <div class="col-12">
                                <div class="card-form mb-3">
                                    <div class="card-form-header">
                                        <i class="bi bi-info-circle me-2"></i>Información de la Categoría
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="categoria-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="categoria-nombre" name="nombre" required>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="categoria-descripcion" class="form-label">Descripción</label>
                                                    <textarea class="form-control" id="categoria-descripcion" name="descripcion" rows="3" placeholder="Ingrese una descripción para la categoría..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-categoria" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de la categoría -->
    <div class="modal fade" id="modal-detalle-categoria" tabindex="-1" aria-labelledby="modal-detalle-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-titulo">Detalles de la Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <h4 id="detalle-nombre" class="fs-5 mb-3">Nombre de la Categoría</h4>

                            <!-- Información básica -->
                            <div class="detalle-card mb-3">
                                <div class="detalle-card-header">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">ID:</span>
                                                <span id="detalle-id" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Descripción:</span>
                                                <span id="detalle-descripcion" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha de Creación:</span>
                                                <span id="detalle-fecha" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Total de Equipos:</span>
                                                <span id="detalle-total-equipos" class="detalle-valor badge bg-primary">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipos asociados -->
                            <div class="detalle-card">
                                <div class="detalle-card-header">
                                    <i class="bi bi-gear me-2"></i>Equipos Asociados
                                </div>
                                <div class="detalle-card-body">
                                    <div class="table-responsive">
                                        <table id="equipos-table" class="table table-sm table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Nombre</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Ubicación</th>
                                                </tr>
                                            </thead>
                                            <tbody id="equipos-body">
                                                <!-- Los equipos se cargarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="sin-equipos" class="text-center py-3 d-none">
                                        <p class="text-muted mb-0">Esta categoría no tiene equipos asociados.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (tienePermiso('administracion.categorias.editar')): ?>
                        <button type="button" id="btn-editar-desde-detalle" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="modal-confirmar-eliminar" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-4">
                        <i class="bi bi-trash-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="mb-3">¿Está seguro que desea eliminar esta categoría?</h4>
                    <p class="text-muted mb-0">Esta acción eliminará permanentemente la categoría.</p>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Nota:</strong> Esta acción no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" id="btn-confirmar-eliminar" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Eliminar Definitivamente
                    </button>
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