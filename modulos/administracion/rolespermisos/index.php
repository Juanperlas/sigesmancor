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
if (!tienePermiso('administracion.roles_permisos.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Obtener módulos para el formulario de permisos
$conexion = new Conexion();
$modulos = $conexion->select("SELECT id, nombre, descripcion FROM modulos ORDER BY nombre");

// Obtener roles para el formulario de permisos
$roles = $conexion->select("SELECT id, nombre, descripcion FROM roles ORDER BY nombre");

// Título de la página
$titulo = "Gestión de Roles y Permisos";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/administracion/rolespermisos/rolespermisos.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/administracion/rolespermisos/rolespermisos.js'
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

    <!-- Pestañas de navegación -->
    <ul class="nav nav-tabs mb-2" id="rolesPermisosTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-content" type="button" role="tab" aria-controls="roles-content" aria-selected="true">
                <i class="bi bi-person-badge me-1"></i>Roles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="permisos-tab" data-bs-toggle="tab" data-bs-target="#permisos-content" type="button" role="tab" aria-controls="permisos-content" aria-selected="false">
                <i class="bi bi-key me-1"></i>Permisos
            </button>
        </li>
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="rolesPermisosTabContent">
        <!-- Pestaña de Roles -->
        <div class="tab-pane fade show active" id="roles-content" role="tabpanel" aria-labelledby="roles-tab">
            <!-- Filtros para Roles -->
            <div class="filtros-container">
                <div class="filtros-header">Filtros de Roles</div>
                <div class="filtros-content">
                    <div class="filtro-grupo">
                        <label for="filtro-estado-rol" class="filtro-label">Estado</label>
                        <select id="filtro-estado-rol" class="filtro-select">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="filtros-actions">
                        <button id="btn-aplicar-filtros-roles" class="btn-aplicar">
                            <i class="bi bi-funnel"></i> Aplicar
                        </button>
                        <button id="btn-limpiar-filtros-roles" class="btn-limpiar">
                            <i class="bi bi-x"></i> Limpiar
                        </button>
                        <?php if (tienePermiso('administracion.roles_permisos.crear')): ?>
                            <button type="button" id="btn-nuevo-rol" class="btn-nuevo">
                                <i class="bi bi-plus-circle"></i> Nuevo Rol
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Layout de dos columnas para Roles -->
            <div class="roles-layout">
                <!-- Tabla de roles -->
                <div class="roles-table-container">
                    <div class="table-container">
                        <table id="roles-table" class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th width="150">Nombre</th>
                                    <th>Descripción</th>
                                    <th width="100">Permisos</th>
                                    <th width="80">Estado</th>
                                    <th width="100">Acciones</th>
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

                <!-- Panel de detalles de rol -->
                <div id="rol-detalle" class="roles-detail-container">
                    <div class="detail-header">
                        <h2 class="detail-title">Detalles del Rol</h2>
                        <p class="detail-subtitle">Seleccione un rol para ver información</p>
                    </div>
                    <div class="detail-content">
                        <div class="detail-empty">
                            <div class="detail-empty-icon">
                                <i class="bi bi-info-circle"></i>
                            </div>
                            <div class="detail-empty-text">
                                Seleccione un rol para ver sus detalles
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña de Permisos -->
        <div class="tab-pane fade" id="permisos-content" role="tabpanel" aria-labelledby="permisos-tab">
            <!-- Filtros para Permisos -->
            <div class="filtros-container">
                <div class="filtros-header">Filtros de Permisos</div>
                <div class="filtros-content">
                    <div class="filtro-grupo">
                        <label for="filtro-modulo" class="filtro-label">Módulo</label>
                        <select id="filtro-modulo" class="filtro-select">
                            <option value="">Todos</option>
                            <?php foreach ($modulos as $modulo): ?>
                                <option value="<?php echo $modulo['id']; ?>"><?php echo htmlspecialchars($modulo['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtros-actions">
                        <button id="btn-aplicar-filtros-permisos" class="btn-aplicar">
                            <i class="bi bi-funnel"></i> Aplicar
                        </button>
                        <button id="btn-limpiar-filtros-permisos" class="btn-limpiar">
                            <i class="bi bi-x"></i> Limpiar
                        </button>
                        <?php if (tienePermiso('administracion.roles_permisos.crear')): ?>
                            <button type="button" id="btn-nuevo-permiso" class="btn-nuevo">
                                <i class="bi bi-plus-circle"></i> Nuevo Permiso
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Layout de dos columnas para Permisos -->
            <div class="permisos-layout">
                <!-- Tabla de permisos -->
                <div class="permisos-table-container">
                    <div class="table-container">
                        <table id="permisos-table" class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th width="180">Nombre</th>
                                    <th width="120">Módulo</th>
                                    <th>Descripción</th>
                                    <th width="80">Roles</th>
                                    <th width="100">Acciones</th>
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

                <!-- Panel de detalles de permiso -->
                <div id="permiso-detalle" class="permisos-detail-container">
                    <div class="detail-header">
                        <h2 class="detail-title">Detalles del Permiso</h2>
                        <p class="detail-subtitle">Seleccione un permiso para ver información</p>
                    </div>
                    <div class="detail-content">
                        <div class="detail-empty">
                            <div class="detail-empty-icon">
                                <i class="bi bi-info-circle"></i>
                            </div>
                            <div class="detail-empty-text">
                                Seleccione un permiso para ver sus detalles
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div class="modal fade" id="modal-rol" tabindex="-1" aria-labelledby="modal-rol-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-rol-titulo">Nuevo Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-rol">
                        <input type="hidden" id="rol-id" name="id">

                        <div class="row g-1">
                            <!-- Columna izquierda -->
                            <div class="col-md-5">
                                <!-- Tarjeta de información básica -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-person-badge me-2"></i>Información Básica
                                    </div>
                                    <div class="card-form-body">
                                        <div class="form-group mb-2">
                                            <label for="rol-nombre" class="form-label form-label-sm">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="rol-nombre" name="nombre" required>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label for="rol-descripcion" class="form-label form-label-sm">Descripción</label>
                                            <textarea class="form-control form-control-sm" id="rol-descripcion" name="descripcion" rows="2"></textarea>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label for="rol-estado" class="form-label form-label-sm">Estado <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="rol-estado" name="esta_activo" required>
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha (permisos) -->
                            <div class="col-md-7">
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-key me-2"></i>Permisos
                                    </div>
                                    <div class="card-form-body">
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">Seleccione los permisos para este rol</label>
                                            <div class="permisos-search mb-2">
                                                <input type="text" class="form-control form-control-sm" id="buscar-permisos" placeholder="Buscar permisos...">
                                            </div>
                                            <div class="permisos-container">
                                                <?php
                                                // Obtener todos los permisos agrupados por módulo
                                                $permisosAgrupados = $conexion->select(
                                                    "SELECT p.id, p.nombre, p.descripcion, m.nombre as modulo_nombre
                                                     FROM permisos p
                                                     INNER JOIN modulos m ON p.modulo_id = m.id
                                                     ORDER BY m.nombre, p.nombre"
                                                );

                                                $moduloActual = '';
                                                foreach ($permisosAgrupados as $permiso):
                                                    if ($moduloActual != $permiso['modulo_nombre']):
                                                        if ($moduloActual != '') echo '</div>'; // Cerrar el div del módulo anterior
                                                        $moduloActual = $permiso['modulo_nombre'];
                                                ?>
                                                        <div class="permiso-modulo">
                                                            <div class="permiso-modulo-header">
                                                                <strong><?php echo htmlspecialchars($moduloActual); ?></strong>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permisos[]" value="<?php echo $permiso['id']; ?>" id="permiso-<?php echo $permiso['id']; ?>">
                                                            <label class="form-check-label" for="permiso-<?php echo $permiso['id']; ?>">
                                                                <?php echo htmlspecialchars($permiso['nombre']); ?>
                                                                <?php if (!empty($permiso['descripcion'])): ?>
                                                                    <small class="text-muted"> - <?php echo htmlspecialchars($permiso['descripcion']); ?></small>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if ($moduloActual != '') echo '</div>'; // Cerrar el último div de módulo 
                                                    ?>
                                                        </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-rol" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar permiso -->
    <div class="modal fade" id="modal-permiso" tabindex="-1" aria-labelledby="modal-permiso-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-permiso-titulo">Nuevo Permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-permiso">
                        <input type="hidden" id="permiso-id" name="id">

                        <div class="row g-1">
                            <!-- Información del permiso -->
                            <div class="col-md-12">
                                <!-- Tarjeta de información básica -->
                                <div class="card-form mb-2">
                                    <div class="card-form-header">
                                        <i class="bi bi-key me-2"></i>Información del Permiso
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-1">
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="permiso-nombre" class="form-label form-label-sm">Nombre <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="permiso-nombre" name="nombre" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-2">
                                                    <label for="permiso-modulo-id" class="form-label form-label-sm">Módulo <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="permiso-modulo-id" name="modulo_id" required>
                                                        <option value="">Seleccione un módulo</option>
                                                        <?php foreach ($modulos as $modulo): ?>
                                                            <option value="<?php echo $modulo['id']; ?>"><?php echo htmlspecialchars($modulo['nombre']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group mb-2">
                                                    <label for="permiso-descripcion" class="form-label form-label-sm">Descripción</label>
                                                    <textarea class="form-control form-control-sm" id="permiso-descripcion" name="descripcion" rows="2"></textarea>
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
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">Asignar este permiso a roles</label>
                                            <div class="roles-container">
                                                <?php foreach ($roles as $rol): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $rol['id']; ?>" id="rol-permiso-<?php echo $rol['id']; ?>">
                                                        <label class="form-check-label" for="rol-permiso-<?php echo $rol['id']; ?>">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-permiso" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del rol -->
    <div class="modal fade" id="modal-detalle-rol" tabindex="-1" aria-labelledby="modal-detalle-rol-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-rol-titulo">Detalles del Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <!-- Información del rol -->
                        <div class="col-md-12">
                            <h4 id="detalle-rol-nombre" class="fs-5 mb-2">Nombre del Rol</h4>

                            <!-- Tarjeta de información básica -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-person-badge me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Descripción:</span>
                                                <span id="detalle-rol-descripcion" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Estado:</span>
                                                <span id="detalle-rol-estado" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha Creación:</span>
                                                <span id="detalle-rol-creado-en" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de permisos -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-key me-2"></i>Permisos Asignados
                                </div>
                                <div class="detalle-card-body">
                                    <div id="detalle-rol-permisos-lista" class="detalle-valor mb-0">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (tienePermiso('administracion.roles_permisos.editar')): ?>
                        <button type="button" id="btn-editar-desde-detalle-rol" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del permiso -->
    <div class="modal fade" id="modal-detalle-permiso" tabindex="-1" aria-labelledby="modal-detalle-permiso-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-permiso-titulo">Detalles del Permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-1">
                        <!-- Información del permiso -->
                        <div class="col-md-12">
                            <h4 id="detalle-permiso-nombre" class="fs-5 mb-2">Nombre del Permiso</h4>

                            <!-- Tarjeta de información básica -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-key me-2"></i>Información Básica
                                </div>
                                <div class="detalle-card-body">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Módulo:</span>
                                                <span id="detalle-permiso-modulo" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Fecha Creación:</span>
                                                <span id="detalle-permiso-creado-en" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="detalle-item">
                                                <span class="detalle-label">Descripción:</span>
                                                <span id="detalle-permiso-descripcion" class="detalle-valor">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de roles -->
                            <div class="detalle-card mb-2">
                                <div class="detalle-card-header">
                                    <i class="bi bi-person-badge me-2"></i>Roles con este Permiso
                                </div>
                                <div class="detalle-card-body">
                                    <div id="detalle-permiso-roles-lista" class="detalle-valor mb-0">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (tienePermiso('administracion.roles_permisos.editar')): ?>
                        <button type="button" id="btn-editar-desde-detalle-permiso" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-confirmar-eliminar" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modal-confirmar-eliminar-titulo">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-4">
                        <i class="bi bi-trash-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="mb-3" id="modal-confirmar-eliminar-mensaje">¿Está seguro que desea eliminar este elemento?</h4>
                    <p class="text-muted mb-0" id="modal-confirmar-eliminar-submensaje">Esta acción eliminará permanentemente el elemento y todos sus datos asociados.</p>
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