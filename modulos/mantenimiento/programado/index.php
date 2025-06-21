<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Obtener información del módulo actual
$rutaActual = $_SERVER['REQUEST_URI'];
$nombreModulo = "Módulo de Mantenimiento";
$tipoMantenimiento = "";

if (strpos($rutaActual, 'preventivo') !== false) {
    $tipoMantenimiento = "Preventivo";
} elseif (strpos($rutaActual, 'correctivo') !== false) {
    $tipoMantenimiento = "Correctivo";
} elseif (strpos($rutaActual, 'programado') !== false) {
    $tipoMantenimiento = "Programado";
}

$tituloCompleto = $nombreModulo . ($tipoMantenimiento ? " - " . $tipoMantenimiento : "");

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/mantenimiento/modulo-desarrollo.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/mantenimiento/modulo-desarrollo.js'
];

// Incluir el header
$baseUrl = '../../../';
include_once '../../../includes/header.php';
include_once '../../../includes/navbar.php';
include_once '../../../includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <div class="modulo-desarrollo-container">
        <div class="desarrollo-header">
            <div class="desarrollo-titulo">
                <h1><?php echo $tituloCompleto; ?></h1>
                <div class="desarrollo-subtitulo">
                    <span class="badge-desarrollo">En Desarrollo</span>
                    <span class="fecha-lanzamiento">Lanzamiento estimado: Próximamente</span>
                </div>
            </div>
        </div>

        <div class="desarrollo-content">
            <div class="desarrollo-animacion">
                <div class="gear-container">
                    <div class="gear-large"></div>
                    <div class="gear-medium"></div>
                    <div class="gear-small"></div>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">Desarrollo en progreso: <span id="progress-percentage">0</span>%</div>
                </div>
            </div>

            <div class="desarrollo-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="info-content">
                        <h3>Características Principales</h3>
                        <ul>
                            <li>Gestión integral de mantenimientos</li>
                            <li>Programación automática de tareas</li>
                            <li>Reportes detallados y estadísticas</li>
                            <li>Notificaciones y alertas personalizables</li>
                            <li>Integración con otros módulos del sistema</li>
                        </ul>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="info-content">
                        <h3>Beneficios</h3>
                        <ul>
                            <li>Reducción de tiempos de inactividad</li>
                            <li>Optimización de recursos y costos</li>
                            <li>Mejora en la planificación operativa</li>
                            <li>Incremento en la vida útil de equipos</li>
                            <li>Toma de decisiones basada en datos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="desarrollo-timeline">
                <h3>Cronograma de Desarrollo</h3>
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Fase 1: Análisis y Diseño</h4>
                            <p>Definición de requerimientos y arquitectura del módulo</p>
                            <span class="timeline-date">Completado</span>
                        </div>
                    </div>
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Fase 2: Desarrollo</h4>
                            <p>Implementación de funcionalidades principales</p>
                            <span class="timeline-date">En progreso</span>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Fase 3: Pruebas</h4>
                            <p>Validación y corrección de errores</p>
                            <span class="timeline-date">Próximamente</span>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Fase 4: Implementación</h4>
                            <p>Despliegue y capacitación</p>
                            <span class="timeline-date">Próximamente</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="desarrollo-contacto">
                <div class="contacto-card">
                    <h3>¿Tienes sugerencias para este módulo?</h3>
                    <p>Nos encantaría conocer tus ideas para hacer de este módulo una herramienta aún más útil para tu trabajo diario.</p>
                    <button id="btn-sugerencia" class="btn-sugerencia">
                        <i class="bi bi-lightbulb"></i> Enviar Sugerencia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sugerencia -->
    <div class="modal fade" id="modal-sugerencia" tabindex="-1" aria-labelledby="modal-sugerencia-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-sugerencia-titulo">Enviar Sugerencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-sugerencia">
                        <div class="mb-3">
                            <label for="sugerencia-tipo" class="form-label">Tipo de Sugerencia</label>
                            <select class="form-select" id="sugerencia-tipo" required>
                                <option value="">Seleccione una opción</option>
                                <option value="funcionalidad">Nueva Funcionalidad</option>
                                <option value="mejora">Mejora de Funcionalidad Existente</option>
                                <option value="interfaz">Mejora de Interfaz</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="sugerencia-descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="sugerencia-descripcion" rows="4" required placeholder="Describa su sugerencia en detalle..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-enviar-sugerencia" class="btn btn-primary">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de confirmación -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="sugerencia-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-check-circle me-2"></i>
                <strong class="me-auto">Sugerencia Enviada</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body">
                ¡Gracias por tu sugerencia! La revisaremos lo antes posible.
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>