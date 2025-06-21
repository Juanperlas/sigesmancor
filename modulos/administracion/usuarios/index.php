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
if (!tienePermiso('administracion.usuarios.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Obtener roles para el formulario
$conexion = new Conexion();
$roles = $conexion->select("SELECT id, nombre, descripcion FROM roles ORDER BY nombre");

// Título de la página
$titulo = "Gestión de Usuarios";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/administracion/usuarios/usuarios.css',
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
    'assets/js/administracion/usuarios/usuarios.js'
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
                <label for="filtro-rol" class="filtro-label">Rol</label>
                <select id="filtro-rol" class="filtro-select">
                    <option value="">Todos</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-estado" class="filtro-label">Estado</label>
                <select id="filtro-estado" class="filtro-select">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
                <?php if (tienePermiso('administracion.usuarios.crear')): ?>
                    <button type="button" id="btn-nuevo-usuario" class="btn-nuevo">
                        <i class="bi bi-plus-circle"></i> Nuevo Usuario
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="usuarios-layout">
        <!-- Tabla de usuarios -->
        <div class="usuarios-table-container">
            <div class="table-container">
                <table id="usuarios-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="50">Foto</th>
                            <th width="120">Username</th>
                            <th>Nombre Completo</th>
                            <th width="180">Correo</th>
                            <th width="120">Área</th>
                            <th width="80">Estado</th>
                            <th width="100">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="usuario-detalle" class="usuarios-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Usuario</h2>
                <p class="detail-subtitle">Seleccione un usuario para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un usuario para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div class="modal fade" id="modal-usuario" tabindex="-1" aria-labelledby="modal-usuario-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-usuario-titulo">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-usuario" enctype="multipart/form-data">
                        <input type="hidden" id="usuario-id" name="id">

                        <div class="row g-1">
                            <!-- Columna izquierda -->
                            <div class="col-md-9">
                                <!-- Tarjeta de información básica -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-person-circle me-2"></i>Información Básica
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-username" class="form-label form-label-sm">Username <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="usuario-username" name="username" required>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-nombre-completo" class="form-label form-label-sm">Nombre Completo <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="usuario-nombre-completo" name="nombre_completo" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-correo" class="form-label form-label-sm">Correo Electrónico</label>
                                                    <input type="email" class="form-control form-control-sm" id="usuario-correo" name="correo">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-dni" class="form-label form-label-sm">DNI</label>
                                                    <input type="text" class="form-control form-control-sm" id="usuario-dni" name="dni">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de información de contacto -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-telephone me-2"></i>Información de Contacto
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-telefono" class="form-label form-label-sm">Teléfono</label>
                                                    <input type="text" class="form-control form-control-sm" id="usuario-telefono" name="telefono">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-area" class="form-label form-label-sm">Área</label>
                                                    <input type="text" class="form-control form-control-sm" id="usuario-area" name="area">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-estado" class="form-label form-label-sm">Estado <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="usuario-estado" name="esta_activo" required>
                                                        <option value="1">Activo</option>
                                                        <option value="0">Inactivo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-direccion" class="form-label form-label-sm">Dirección</label>
                                                    <textarea class="form-control form-control-sm" id="usuario-direccion" name="direccion" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de seguridad -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-shield-lock me-2"></i>Seguridad
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-contrasena" class="form-label form-label-sm">Contraseña <span class="text-danger contrasena-requerida">*</span></label>
                                                    <input type="password" class="form-control form-control-sm" id="usuario-contrasena" name="contrasena">
                                                    <small class="text-muted" id="contrasena-ayuda">Dejar en blanco para mantener la contraseña actual.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="usuario-confirmar-contrasena" class="form-label form-label-sm">Confirmar Contraseña <span class="text-danger contrasena-requerida">*</span></label>
                                                    <input type="password" class="form-control form-control-sm" id="usuario-confirmar-contrasena" name="confirmar_contrasena">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta de roles -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-person-badge me-2"></i>Roles
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-12">
                                                <div class="form-group mb-2">
                                                    <label class="form-label form-label-sm">Seleccione los roles para este usuario</label>
                                                    <div class="roles-container">
                                                        <?php foreach ($roles as $rol): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $rol['id']; ?>" id="rol-<?php echo $rol['id']; ?>">
                                                                <label class="form-check-label" for="rol-<?php echo $rol['id']; ?>">
                                                                    <?php echo htmlspecialchars($rol['nombre']); ?>
                                                                    <?php if (!empty($rol['descripcion'])): ?>
                                                                        <small class="text-muted"> - <?php echo htmlspecialchars($rol['descripcion']); ?></small>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha (fotografía) -->
                            <div class="col-md-3">
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-image me-2"></i>Fotografía del Usuario
                                    </div>
                                    <div class="card-form-body text-center">
                                        <div class="image-upload-container" id="container-usuario-fotografia">
                                            <div class="image-upload-preview">
                                                <img src="<?php echo $baseUrl; ?>assets/img/administracion/usuarios/default.png"
                                                    alt="Vista previa"
                                                    id="preview-usuario-fotografia"
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
                                            <input type="file" name="fotografia" id="input-usuario-fotografia" class="image-upload-input" accept="image/*">
                                            <input type="hidden" name="existing_fotografia" id="existing-usuario-fotografia" value="">
                                        </div>
                                        <p class="text-muted small mt-2">Tamaño máximo: 2MB. Formatos: JPG, PNG, GIF, WEBP</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-usuario" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del usuario -->
    <div class="modal fade" id="modal-detalle-usuario" tabindex="-1" aria-labelledby="modal-detalle-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-titulo">Detalles del Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <!-- Información del usuario -->
                        <div class="col-md-3 text-center mb-2">
                            <div class="usuario-fotografia-container">
                                <img id="detalle-fotografia" src="<?php echo $baseUrl; ?>assets/img/administracion/usuarios/default.png" alt="Fotografía del usuario" class="img-fluid rounded mb-1">
                                <button type="button" id="btn-ver-fotografia" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-search-plus me-1"></i> Ampliar
                                </button>
                            </div>
                            <div class="mt-1">
                                <span id="detalle-estado" class="badge rounded-pill bg-success">Activo</span>
                            </div>
                            <div id="detalle-roles" class="mt-2">
                                <span class="badge bg-primary">Admin</span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 id="detalle-nombre-completo" class="fs-5 mb-2">Nombre del Usuario</h4>
                            <!-- Tarjetas de información -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-person-circle me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Username:</span>
                                                <span id="detalle-username" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Correo:</span>
                                                <span id="detalle-correo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">DNI:</span>
                                                <span id="detalle-dni" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-telephone me-2"></i>Información de Contacto
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Teléfono:</span>
                                                <span id="detalle-telefono" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Área:</span>
                                                <span id="detalle-area" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha Creación:</span>
                                                <span id="detalle-creado-en" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Dirección:</span>
                                                <span id="detalle-direccion" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-person-badge me-2"></i>Roles Asignados
                                </div>
                                <div class="detalle-card-body">
                                    <div id="detalle-roles-lista" class="detalle-valor mb-0">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (tienePermiso('administracion.usuarios.editar')): ?>
                        <button type="button" id="btn-generar-reporte" class="btn btn-sm btn-danger">
                            <i class="bi bi-file-pdf me-1"></i> Generar Informe
                        </button>
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
                    <h4 class="mb-3">¿Está seguro que desea eliminar este usuario?</h4>
                    <p class="text-muted mb-0">Esta acción eliminará permanentemente el usuario y todos sus datos asociados.</p>
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