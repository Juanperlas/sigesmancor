<?php
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('administracion.horarios.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Gestión de Horarios";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/administracion/horarios/horarios.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/administracion/horarios/horarios.js'
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

    <!-- Tabs de Manual y Predeterminado -->
    <ul class="nav nav-tabs" id="horariosTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab" aria-controls="manual" aria-selected="true">Manual</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="predefinido-tab" data-bs-toggle="tab" data-bs-target="#predefinido" type="button" role="tab" aria-controls="predefinido" aria-selected="false">Predeterminado</button>
        </li>
    </ul>

    <!-- Contenido de los tabs -->
    <div class="tab-content" id="horariosTabContent">
        <div class="tab-pane fade show active" id="manual" role="tabpanel" aria-labelledby="manual-tab">
            <?php include_once 'manuales.php'; ?>
        </div>
        <div class="tab-pane fade" id="predefinido" role="tabpanel" aria-labelledby="predefinido-tab">
            <?php include_once 'predefinidos.php'; ?>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>
