<?php
// Incluir archivos necesarios
require_once 'db/funciones.php';
require_once 'db/conexion.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener datos del usuario actual usando la función existente
$usuario = getUsuarioActual();
if (!$usuario) {
    header("Location: login.php");
    exit;
}

// Obtener datos completos del usuario para el perfil
$conexion = new Conexion();
$usuarioCompleto = $conexion->selectOne(
    "SELECT u.*, 
     GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_nombres
     FROM usuarios u
     LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id
     LEFT JOIN roles r ON ur.rol_id = r.id
     WHERE u.id = ?
     GROUP BY u.id",
    [$usuario['id']]
);

// Título de la página
$titulo = "Mi Perfil";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/perfil.css',
    'componentes/image-upload/image-upload.css',
    'componentes/image-viewer/image-viewer.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/image-upload/image-upload.js',
    'componentes/image-viewer/image-viewer.js',
    'componentes/toast/toast.js',
    'assets/js/perfil.js'
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
        <div class="page-actions">
            <button type="button" id="btn-editar-perfil" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Editar Perfil
            </button>
        </div>
    </div>

    <!-- Layout de perfil -->
    <div class="perfil-layout">
        <!-- Panel de información del usuario -->
        <div class="perfil-info-container">
            <div class="perfil-header">
                <div class="perfil-avatar">
                    <img src="<?php echo $usuarioCompleto['fotografia'] ? getAssetUrl($usuarioCompleto['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png'); ?>" 
                         alt="<?php echo htmlspecialchars($usuarioCompleto['nombre_completo'] ?: $usuarioCompleto['username']); ?>" 
                         class="perfil-avatar-img" 
                         id="perfil-avatar-display">
                </div>
                <div class="perfil-info">
                    <h2 class="perfil-nombre"><?php echo htmlspecialchars($usuarioCompleto['nombre_completo'] ?: $usuarioCompleto['username']); ?></h2>
                    <p class="perfil-username">@<?php echo htmlspecialchars($usuarioCompleto['username']); ?></p>
                    <div class="perfil-roles">
                        <?php if (!empty($usuarioCompleto['roles_nombres'])): ?>
                            <?php 
                            $roles = explode(', ', $usuarioCompleto['roles_nombres']);
                            foreach ($roles as $rol): 
                            ?>
                                <span class="badge bg-primary me-1"><?php echo htmlspecialchars(ucfirst($rol)); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div class="perfil-details-container">
            <!-- Información básica -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <i class="bi bi-person-circle me-2"></i>Información Personal
                </div>
                <div class="detail-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Nombre Completo:</span>
                                <span class="detail-value" id="display-nombre"><?php echo htmlspecialchars($usuarioCompleto['nombre_completo'] ?: '-'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Usuario:</span>
                                <span class="detail-value" id="display-username"><?php echo htmlspecialchars($usuarioCompleto['username']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Correo:</span>
                                <span class="detail-value" id="display-correo"><?php echo htmlspecialchars($usuarioCompleto['correo'] ?: '-'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">DNI:</span>
                                <span class="detail-value" id="display-dni"><?php echo htmlspecialchars($usuarioCompleto['dni'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de contacto -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <i class="bi bi-telephone me-2"></i>Información de Contacto
                </div>
                <div class="detail-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Teléfono:</span>
                                <span class="detail-value" id="display-telefono"><?php echo htmlspecialchars($usuarioCompleto['telefono'] ?: '-'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Área:</span>
                                <span class="detail-value" id="display-area"><?php echo htmlspecialchars($usuarioCompleto['area'] ?: '-'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="detail-item">
                                <span class="detail-label">Dirección:</span>
                                <span class="detail-value" id="display-direccion"><?php echo htmlspecialchars($usuarioCompleto['direccion'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del sistema -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <i class="bi bi-info-circle me-2"></i>Información del Sistema
                </div>
                <div class="detail-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Roles Asignados:</span>
                                <div class="detail-value">
                                    <?php if (!empty($usuarioCompleto['roles_nombres'])): ?>
                                        <?php 
                                        $roles = explode(', ', $usuarioCompleto['roles_nombres']);
                                        foreach ($roles as $rol): 
                                        ?>
                                            <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars(ucfirst($rol)); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin roles asignados</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Última Actualización:</span>
                                <span class="detail-value" id="display-actualizado"><?php echo $usuarioCompleto['actualizado_en'] ? date('d/m/Y H:i', strtotime($usuarioCompleto['actualizado_en'])) : '-'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar perfil -->
    <div class="modal fade" id="modal-editar-perfil" tabindex="-1" aria-labelledby="modal-perfil-titulo" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-perfil-titulo">Editar Mi Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-perfil" enctype="multipart/form-data">
                        <div class="row g-3">
                            <!-- Columna izquierda -->
                            <div class="col-md-9">
                                <!-- Información personal -->
                                <div class="card-form mb-3">
                                    <div class="card-form-header">
                                        <i class="bi bi-person-circle me-2"></i>Información Personal
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="perfil-nombre" name="nombre_completo" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-username" class="form-label">Usuario</label>
                                                    <input type="text" class="form-control" id="perfil-username" name="username" readonly>
                                                    <small class="text-muted">El nombre de usuario no se puede cambiar</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-correo" class="form-label">Correo Electrónico</label>
                                                    <input type="email" class="form-control" id="perfil-correo" name="correo">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-dni" class="form-label">DNI</label>
                                                    <input type="text" class="form-control" id="perfil-dni" name="dni">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de contacto -->
                                <div class="card-form mb-3">
                                    <div class="card-form-header">
                                        <i class="bi bi-telephone me-2"></i>Información de Contacto
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-telefono" class="form-label">Teléfono</label>
                                                    <input type="text" class="form-control" id="perfil-telefono" name="telefono">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="perfil-area" class="form-label">Área</label>
                                                    <input type="text" class="form-control" id="perfil-area" name="area">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="perfil-direccion" class="form-label">Dirección</label>
                                                    <textarea class="form-control" id="perfil-direccion" name="direccion" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cambiar contraseña -->
                                <div class="card-form mb-3">
                                    <div class="card-form-header">
                                        <i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña
                                    </div>
                                    <div class="card-form-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="perfil-contrasena-actual" class="form-label">Contraseña Actual</label>
                                                    <input type="password" class="form-control" id="perfil-contrasena-actual" name="contrasena_actual">
                                                    <small class="text-muted">Dejar en blanco para no cambiar la contraseña</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="perfil-contrasena-nueva" class="form-label">Nueva Contraseña</label>
                                                    <input type="password" class="form-control" id="perfil-contrasena-nueva" name="contrasena_nueva">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="perfil-confirmar-contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                                                    <input type="password" class="form-control" id="perfil-confirmar-contrasena" name="confirmar_contrasena">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha (fotografía) -->
                            <div class="col-md-3">
                                <div class="card-form">
                                    <div class="card-form-header">
                                        <i class="bi bi-image me-2"></i>Fotografía de Perfil
                                    </div>
                                    <div class="card-form-body text-center">
                                        <div class="image-upload-container" id="container-perfil-fotografia">
                                            <div class="image-upload-preview">
                                                <img src="<?php echo $usuarioCompleto['fotografia'] ? getAssetUrl($usuarioCompleto['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png'); ?>"
                                                    alt="Vista previa"
                                                    id="preview-perfil-fotografia"
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
                                            <input type="file" name="fotografia" id="input-perfil-fotografia" class="image-upload-input" accept="image/*">
                                            <input type="hidden" name="existing_fotografia" id="existing-perfil-fotografia" value="">
                                        </div>
                                        <p class="text-muted small mt-2">Tamaño máximo: 2MB. Formatos: JPG, PNG, GIF, WEBP</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-perfil" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Componente de visualización de imágenes -->
    <?php include_once 'componentes/image-viewer/image-viewer.php'; ?>

    <!-- Componente de notificaciones toast -->
    <?php include_once 'componentes/toast/toast.php'; ?>
</div>

<?php
// Incluir el footer
include_once 'includes/footer.php';
?>
