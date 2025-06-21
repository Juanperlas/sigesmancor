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
if (!tienePermiso('componentes.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Obtener equipos para el formulario
$conexion = new Conexion();
$equipos = $conexion->select("SELECT id, nombre, codigo FROM equipos ORDER BY nombre");

// Título de la página
$titulo = "Gestión de Componentes";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/equipos/componentes/componentes.css',
    'componentes/image-upload/image-upload.css',
    'componentes/image-viewer/image-viewer.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/image-upload/image-upload.js',
    'componentes/image-viewer/image-viewer.js',
    'componentes/toast/toast.js',
    'assets/js/equipos/componentes/componentes.js'
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
                <label for="filtro-equipo" class="filtro-label">Equipo</label>
                <select id="filtro-equipo" class="filtro-select">
                    <option value="">Todos</option>
                    <?php foreach ($equipos as $equipo): ?>
                        <option value="<?php echo $equipo['id']; ?>"><?php echo htmlspecialchars($equipo['codigo'] . ' - ' . $equipo['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-estado" class="filtro-label">Estado</label>
                <select id="filtro-estado" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="averiado">Averiado</option>
                    <option value="vendido">Vendido</option>
                    <option value="descanso">Descanso</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-tipo-orometro" class="filtro-label">Tipo de Orómetro</label>
                <select id="filtro-tipo-orometro" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="horas">Horas</option>
                    <option value="kilometros">Kilómetros</option>
                </select>
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
                <?php if (tienePermiso('componentes.crear')): ?>
                    <button type="button" id="btn-nuevo-componente" class="btn-nuevo">
                        <i class="bi bi-plus-circle"></i> Nuevo Componente
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="componentes-layout">
        <!-- Tabla de componentes -->
        <div class="componentes-table-container">
            <div class="table-container">
                <table id="componentes-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="50">Imagen</th>
                            <th width="100">Código</th>
                            <th>Nombre</th>
                            <th width="100">Equipo</th>
                            <th width="100">Estado</th>
                            <th width="100">Anterior</th>
                            <th width="100">Actual</th>
                            <th width="100">Próximo</th>
                            <th width="100">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="9" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="componente-detalle" class="componentes-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Componente</h2>
                <p class="detail-subtitle">Seleccione un componente para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un componente para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar componente -->
    <div class="modal fade" id="modal-componente" tabindex="-1" aria-labelledby="modal-componente-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-componente-titulo">Nuevo Componente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-componente" enctype="multipart/form-data">
                        <input type="hidden" id="componente-id" name="id">

                        <div class="row g-1">
                            <!-- Columna izquierda -->
                            <div class="col-md-9">
                                <!-- Tarjeta de información básica -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-info-circle me-2"></i>Información Básica
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-codigo" class="form-label form-label-sm">Código <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="componente-codigo" name="codigo" required>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group mb-2">
                                                    <label for="componente-nombre" class="form-label form-label-sm">Nombre <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="componente-nombre" name="nombre" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="componente-equipo" class="form-label form-label-sm">Equipo <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="componente-equipo" name="equipo_id" required>
                                                        <option value="">Seleccione...</option>
                                                        <?php foreach ($equipos as $equipo): ?>
                                                            <option value="<?php echo $equipo['id']; ?>"><?php echo htmlspecialchars($equipo['codigo'] . ' - ' . $equipo['nombre']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="componente-estado" class="form-label form-label-sm">Estado <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="componente-estado" name="estado" required>
                                                        <option value="">Seleccione...</option>
                                                        <option value="activo">Activo</option>
                                                        <option value="mantenimiento">Mantenimiento</option>
                                                        <option value="averiado">Averiado</option>
                                                        <option value="vendido">Vendido</option>
                                                        <option value="descanso">Descanso</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de información técnica -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-tools me-2"></i>Información Técnica
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-marca" class="form-label form-label-sm">Marca</label>
                                                    <input type="text" class="form-control form-control-sm" id="componente-marca" name="marca">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-modelo" class="form-label form-label-sm">Modelo</label>
                                                    <input type="text" class="form-control form-control-sm" id="componente-modelo" name="modelo">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-serie" class="form-label form-label-sm">Número de Serie</label>
                                                    <input type="text" class="form-control form-control-sm" id="componente-serie" name="numero_serie">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de información de orómetro -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-speedometer2 me-2"></i>Información de Orómetro
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label for="componente-tipo-orometro" class="form-label form-label-sm">Tipo de Orómetro <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="componente-tipo-orometro" name="tipo_orometro" required>
                                                        <option value="">Seleccione...</option>
                                                        <option value="horas">Horas</option>
                                                        <option value="kilometros">Kilómetros</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label for="componente-mantenimiento" class="form-label form-label-sm">Mantenimiento</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="componente-mantenimiento" name="mantenimiento">
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label for="componente-notificacion" class="form-label form-label-sm">Notificación</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="componente-notificacion" name="notificacion">
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label for="componente-limite" class="form-label form-label-sm">Límite</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="componente-limite" name="limite">
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-anterior-orometro" class="form-label form-label-sm">Orómetro Anterior</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="componente-anterior-orometro" name="anterior_orometro" value="0">
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-orometro" class="form-label form-label-sm">Orómetro Actual <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="componente-orometro" name="orometro_actual" value="0" required>
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="componente-proximo-orometro" class="form-label form-label-sm">Próximo Orómetro</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm bg-light" id="componente-proximo-orometro" readonly>
                                                        <span class="input-group-text unidad-orometro">hrs</span>
                                                    </div>
                                                    <!-- Añadir campo oculto para enviar el valor -->
                                                    <input type="hidden" name="proximo_orometro" id="hidden-proximo-orometro">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de observaciones -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-chat-left-text me-2"></i>Observaciones
                                    </div>
                                    <div class="card-form-body">
                                        <div class="form-group">
                                            <textarea class="form-control form-control-sm" id="componente-observaciones" name="observaciones" rows="2" placeholder="Ingrese observaciones adicionales sobre el componente..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha (imagen) -->
                            <div class="col-md-3">
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-image me-2"></i>Imagen del Componente
                                    </div>
                                    <div class="card-form-body text-center">
                                        <div class="image-upload-container" id="container-componente-imagen">
                                            <div class="image-upload-preview">
                                                <img src="<?php echo $baseUrl; ?>assets/img/equipos/componentes/default.png"
                                                    alt="Vista previa"
                                                    id="preview-componente-imagen"
                                                    class="image-preview">
                                                <div class="image-upload-overlay">
                                                    <div class="image-upload-buttons">
                                                        <button type="button" class="btn btn-sm btn-light" data-action="upload" title="Subir imagen">
                                                            <i class="bi bi-upload"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light" data-action="camera" title="Tomar foto">
                                                            <i class="bi bi-camera"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light" data-action="remove" title="Eliminar imagen" style="display:none;">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="file" name="imagen" id="input-componente-imagen" class="image-upload-input" accept="image/*">
                                            <input type="hidden" name="imagen_existing" id="existing-componente-imagen" value="">
                                        </div>
                                        <p class="text-muted small mt-2">Tamaño máximo: 2MB. Formatos: JPG, PNG, GIF, WEBP</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-componente" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del componente -->
    <div class="modal fade" id="modal-detalle-componente" tabindex="-1" aria-labelledby="modal-detalle-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-titulo">Detalles del Componente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <!-- Información del componente -->
                        <div class="col-md-3 text-center mb-2">
                            <div class="componente-imagen-container">
                                <img id="detalle-imagen" src="<?php echo $baseUrl; ?>assets/img/equipos/componentes/default.png" alt="Imagen del componente" class="img-fluid rounded mb-1">
                                <button type="button" id="btn-ver-imagen" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-search-plus me-1"></i> Ampliar
                                </button>
                            </div>
                            <div class="mt-1">
                                <span id="detalle-estado" class="badge rounded-pill bg-success">Activo</span>
                            </div>
                            <!-- Tiempo restante para mantenimiento -->
                            <div id="detalle-tiempo-restante" class="mt-1"></div>
                        </div>
                        <div class="col-md-9">
                            <h4 id="detalle-nombre" class="fs-5 mb-2">Nombre del Componente</h4>
                            <!-- Tarjetas de información -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Código:</span>
                                                <span id="detalle-codigo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Tipo Orómetro:</span>
                                                <span id="detalle-tipo-orometro" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Equipo:</span>
                                                <span id="detalle-equipo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-tools me-2"></i>Información Técnica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Marca:</span>
                                                <span id="detalle-marca" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Modelo:</span>
                                                <span id="detalle-modelo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Número de Serie:</span>
                                                <span id="detalle-serie" class="detalle-valor">-</span>
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
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Mantenimiento:</span>
                                                <span id="detalle-mantenimiento" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Notificación:</span>
                                                <span id="detalle-notificacion" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Límite:</span>
                                                <span id="detalle-limite" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Orómetro Anterior:</span>
                                                <span id="detalle-orometro-anterior" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Orómetro Actual:</span>
                                                <span id="detalle-orometro" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Próximo Orómetro:</span>
                                                <span id="detalle-proximo-orometro" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-chat-left-text me-2"></i>Observaciones
                                </div>
                                <div class="detalle-card-body">
                                    <p id="detalle-observaciones" class="detalle-valor mb-0">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (tienePermiso('componentes.editar')): ?>
                        <button type="button" id="btn-editar-desde-detalle" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar (Versión mejorada) -->
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
                    <h4 class="mb-3">¿Está seguro que desea eliminar este componente?</h4>
                    <p class="text-muted mb-0">Esta acción eliminará permanentemente el componente y todos sus datos asociados.</p>
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

    <!-- Componente de visualización de imágenes -->
    <?php include_once '../../../componentes/image-viewer/image-viewer.php'; ?>

    <!-- Componente de notificaciones toast -->
    <?php include_once '../../../componentes/toast/toast.php'; ?>

    <?php
    // Incluir el footer
    include_once '../../../includes/footer.php';
    ?>