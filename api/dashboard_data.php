<?php
// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Incluir archivos necesarios
require_once '../db/funciones.php';
require_once '../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar permiso
if (!tienePermiso('dashboard.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

// Configurar headers
header('Content-Type: application/json');

try {
    $conexion = new Conexion();
    
    // Verificar si es una solicitud de exportación
    if (isset($_GET['export'])) {
        handleExport($_GET['export'], $_GET['tipo'] ?? null);
        exit;
    }
    
    // Obtener datos del dashboard
    $dashboardData = [
        'estadisticas' => obtenerEstadisticasPrincipales($conexion),
        'graficas' => obtenerDatosGraficas($conexion),
        'tablas' => obtenerDatosTablas($conexion),
        'actividad' => obtenerActividadReciente($conexion),
        'alertas' => obtenerAlertas($conexion)
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $dashboardData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error en dashboard_data.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

/**
 * Obtiene las estadísticas principales del dashboard
 */
function obtenerEstadisticasPrincipales($conexion) {
    // Total de equipos
    $totalEquipos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos");
    $totalEquipos = $totalEquipos ? $totalEquipos['total'] : 0;
    
    // Equipos activos
    $equiposActivos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos WHERE estado = 'activo'");
    $equiposActivos = $equiposActivos ? $equiposActivos['total'] : 0;
    
    // Mantenimientos pendientes (suma de todos los tipos)
    $mantenimientosPendientes = 0;
    
    // Preventivos pendientes
    $preventivos = $conexion->selectOne("SELECT COUNT(*) as total FROM mantenimiento_preventivo WHERE estado = 'pendiente'");
    $mantenimientosPendientes += $preventivos ? $preventivos['total'] : 0;
    
    // Correctivos pendientes
    $correctivos = $conexion->selectOne("SELECT COUNT(*) as total FROM mantenimiento_correctivo WHERE estado = 'pendiente'");
    $mantenimientosPendientes += $correctivos ? $correctivos['total'] : 0;
    
    // Programados pendientes
    $programados = $conexion->selectOne("SELECT COUNT(*) as total FROM mantenimiento_programado WHERE estado = 'pendiente'");
    $mantenimientosPendientes += $programados ? $programados['total'] : 0;
    
    // Equipos críticos (averiados + en mantenimiento)
    $equiposCriticos = $conexion->selectOne("
        SELECT COUNT(*) as total FROM equipos 
        WHERE estado IN ('averiado', 'mantenimiento')
    ");
    $equiposCriticos = $equiposCriticos ? $equiposCriticos['total'] : 0;
    
    // Equipos nuevos este mes
    $equiposNuevos = $conexion->selectOne("
        SELECT COUNT(*) as total FROM equipos 
        WHERE MONTH(creado_en) = MONTH(CURRENT_DATE()) 
        AND YEAR(creado_en) = YEAR(CURRENT_DATE())
    ");
    $equiposNuevos = $equiposNuevos ? $equiposNuevos['total'] : 0;
    
    return [
        'totalEquipos' => (int)$totalEquipos,
        'equiposActivos' => (int)$equiposActivos,
        'mantenimientosPendientes' => (int)$mantenimientosPendientes,
        'equiposCriticos' => (int)$equiposCriticos,
        'equiposNuevos' => (int)$equiposNuevos
    ];
}

/**
 * Obtiene los datos para las gráficas
 */
function obtenerDatosGraficas($conexion) {
    return [
        'estadoEquipos' => obtenerEstadoEquipos($conexion),
        'mantenimientosMes' => obtenerMantenimientosPorMes($conexion),
        'ubicaciones' => obtenerDistribucionUbicaciones($conexion)
    ];
}

/**
 * Obtiene la distribución de equipos por estado
 */
function obtenerEstadoEquipos($conexion) {
    $estados = $conexion->select("
        SELECT estado, COUNT(*) as total 
        FROM equipos 
        GROUP BY estado 
        ORDER BY total DESC
    ");
    
    if (empty($estados)) {
        // Datos de demostración si no hay equipos
        return [
            'labels' => ['Sin datos'],
            'values' => [1]
        ];
    }
    
    $labels = [];
    $values = [];
    
    foreach ($estados as $estado) {
        $labels[] = ucfirst($estado['estado']);
        $values[] = (int)$estado['total'];
    }
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Obtiene los mantenimientos por mes
 */
function obtenerMantenimientosPorMes($conexion) {
    // Obtener los últimos 6 meses
    $meses = [];
    $preventivo = [];
    $correctivo = [];
    $programado = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $fecha = date('Y-m', strtotime("-$i months"));
        $mesNombre = date('M Y', strtotime("-$i months"));
        $meses[] = $mesNombre;
        
        // Mantenimientos preventivos
        $prev = $conexion->selectOne("
            SELECT COUNT(*) as total 
            FROM mantenimiento_preventivo 
            WHERE DATE_FORMAT(fecha_realizado, '%Y-%m') = ? 
            AND estado = 'completado'
        ", [$fecha]);
        $preventivo[] = $prev ? (int)$prev['total'] : 0;
        
        // Mantenimientos correctivos
        $corr = $conexion->selectOne("
            SELECT COUNT(*) as total 
            FROM mantenimiento_correctivo 
            WHERE DATE_FORMAT(fecha_realizado, '%Y-%m') = ? 
            AND estado = 'completado'
        ", [$fecha]);
        $correctivo[] = $corr ? (int)$corr['total'] : 0;
        
        // Mantenimientos programados
        $prog = $conexion->selectOne("
            SELECT COUNT(*) as total 
            FROM mantenimiento_programado 
            WHERE DATE_FORMAT(fecha_realizado, '%Y-%m') = ? 
            AND estado = 'completado'
        ", [$fecha]);
        $programado[] = $prog ? (int)$prog['total'] : 0;
    }
    
    return [
        'labels' => $meses,
        'preventivo' => $preventivo,
        'correctivo' => $correctivo,
        'programado' => $programado
    ];
}

/**
 * Obtiene la distribución por ubicaciones
 */
function obtenerDistribucionUbicaciones($conexion) {
    $ubicaciones = $conexion->select("
        SELECT ubicacion, COUNT(*) as total 
        FROM equipos 
        WHERE ubicacion IS NOT NULL AND ubicacion != ''
        GROUP BY ubicacion 
        ORDER BY total DESC 
        LIMIT 8
    ");
    
    if (empty($ubicaciones)) {
        // Datos de demostración si no hay ubicaciones
        return [
            'labels' => ['Sin ubicaciones'],
            'values' => [1]
        ];
    }
    
    $labels = [];
    $values = [];
    
    foreach ($ubicaciones as $ubicacion) {
        $labels[] = $ubicacion['ubicacion'];
        $values[] = (int)$ubicacion['total'];
    }
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Obtiene los datos para las tablas
 */
function obtenerDatosTablas($conexion) {
    return [
        'equiposAtencion' => obtenerEquiposAtencion($conexion),
        'ultimosMantenimientos' => obtenerUltimosMantenimientos($conexion)
    ];
}

/**
 * Obtiene equipos que requieren atención
 */
function obtenerEquiposAtencion($conexion) {
    $equipos = $conexion->select("
        SELECT e.id, e.codigo, e.nombre, e.ubicacion, e.estado,
               e.orometro_actual, e.proximo_orometro, e.notificacion,
               CASE 
                   WHEN e.estado = 'averiado' THEN 'Alta'
                   WHEN e.estado = 'mantenimiento' THEN 'Alta'
                   WHEN e.proximo_orometro IS NOT NULL AND (e.proximo_orometro - e.orometro_actual) <= (e.notificacion / 2) THEN 'Alta'
                   WHEN e.proximo_orometro IS NOT NULL AND (e.proximo_orometro - e.orometro_actual) <= e.notificacion THEN 'Media'
                   ELSE 'Baja'
               END as prioridad,
               CASE 
                   WHEN e.proximo_orometro IS NOT NULL THEN 
                       CONCAT(FORMAT(e.proximo_orometro, 2), ' ', 
                              CASE e.tipo_orometro WHEN 'horas' THEN 'hrs' ELSE 'km' END)
                   ELSE 'No programado'
               END as proximoMantenimiento,
               CASE 
                   WHEN e.proximo_orometro IS NOT NULL AND e.proximo_orometro > e.orometro_actual THEN 
                       CONCAT('Faltan ', FORMAT(e.proximo_orometro - e.orometro_actual, 2), ' ', 
                              CASE e.tipo_orometro WHEN 'horas' THEN 'hrs' ELSE 'km' END)
                   WHEN e.proximo_orometro IS NOT NULL AND e.proximo_orometro <= e.orometro_actual THEN 
                       CONCAT('Excedido por ', FORMAT(e.orometro_actual - e.proximo_orometro, 2), ' ', 
                              CASE e.tipo_orometro WHEN 'horas' THEN 'hrs' ELSE 'km' END)
                   ELSE 'Sin programar'
               END as tiempoRestante
        FROM equipos e
        WHERE e.estado IN ('averiado', 'mantenimiento') 
           OR (e.proximo_orometro IS NOT NULL AND (e.proximo_orometro - e.orometro_actual) <= e.notificacion)
        ORDER BY 
            CASE e.estado 
                WHEN 'averiado' THEN 1
                WHEN 'mantenimiento' THEN 2
                ELSE 3
            END,
            (e.proximo_orometro - e.orometro_actual) ASC
        LIMIT 10
    ");
    
    return $equipos ?: [];
}

/**
 * Obtiene los últimos mantenimientos realizados
 */
function obtenerUltimosMantenimientos($conexion) {
    $mantenimientos = $conexion->select("
        SELECT 
            DATE_FORMAT(h.fecha_realizado, '%d/%m/%Y') as fecha,
            COALESCE(e.nombre, c.nombre, 'Equipo/Componente eliminado') as equipo,
            COALESCE(e.codigo, c.codigo, 'N/A') as codigo,
            CASE 
                WHEN h.tipo_mantenimiento = 'preventivo' THEN 'Preventivo'
                WHEN h.tipo_mantenimiento = 'correctivo' THEN 'Correctivo'
                WHEN h.tipo_mantenimiento = 'predictivo' THEN 'Programado'
                ELSE h.tipo_mantenimiento
            END as tipo,
            h.descripcion,
            'Completado' as estado
        FROM historial_mantenimiento h
        LEFT JOIN equipos e ON h.equipo_id = e.id
        LEFT JOIN componentes c ON h.componente_id = c.id
        WHERE h.fecha_realizado IS NOT NULL
        ORDER BY h.fecha_realizado DESC
        LIMIT 10
    ");
    
    return $mantenimientos ?: [];
}

/**
 * Obtiene la actividad reciente
 */
function obtenerActividadReciente($conexion) {
    $actividades = [];
    
    // Mantenimientos recientes del historial
    $mantenimientos = $conexion->select("
        SELECT 
            'mantenimiento' as tipo,
            CONCAT('Mantenimiento completado: ', COALESCE(e.nombre, c.nombre, 'Equipo/Componente')) as titulo,
            h.descripcion as descripcion,
            h.fecha_realizado as fecha
        FROM historial_mantenimiento h
        LEFT JOIN equipos e ON h.equipo_id = e.id
        LEFT JOIN componentes c ON h.componente_id = c.id
        WHERE h.fecha_realizado >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY h.fecha_realizado DESC
        LIMIT 5
    ");
    
    foreach ($mantenimientos as $mant) {
        $actividades[] = [
            'tipo' => $mant['tipo'],
            'titulo' => $mant['titulo'],
            'descripcion' => $mant['descripcion'] ?: 'Sin descripción',
            'tiempo' => timeAgo($mant['fecha'])
        ];
    }
    
    // Si no hay actividades reales, mostrar mensaje
    if (empty($actividades)) {
        $actividades[] = [
            'tipo' => 'info',
            'titulo' => 'Sin actividad reciente',
            'descripcion' => 'No hay mantenimientos registrados en los últimos 7 días',
            'tiempo' => 'Sistema iniciado'
        ];
    }
    
    return $actividades;
}

/**
 * Obtiene las alertas del sistema
 */
function obtenerAlertas($conexion) {
    $alertas = [];
    
    // Equipos averiados
    $averiados = $conexion->select("
        SELECT nombre, codigo FROM equipos WHERE estado = 'averiado' LIMIT 5
    ");
    
    foreach ($averiados as $equipo) {
        $alertas[] = [
            'tipo' => 'critical',
            'titulo' => 'Equipo Averiado',
            'descripcion' => "El equipo {$equipo['nombre']} ({$equipo['codigo']}) está fuera de servicio",
            'tiempo' => 'Requiere atención inmediata'
        ];
    }
    
    // Mantenimientos vencidos
    $vencidos = $conexion->select("
        SELECT e.nombre, e.codigo 
        FROM equipos e 
        WHERE e.proximo_orometro IS NOT NULL 
        AND e.orometro_actual >= e.proximo_orometro 
        LIMIT 3
    ");
    
    foreach ($vencidos as $equipo) {
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Mantenimiento Vencido',
            'descripcion' => "El equipo {$equipo['nombre']} ({$equipo['codigo']}) tiene mantenimiento vencido",
            'tiempo' => 'Programar mantenimiento'
        ];
    }
    
    // Si no hay alertas, mostrar mensaje informativo
    if (empty($alertas)) {
        $alertas[] = [
            'tipo' => 'info',
            'titulo' => 'Sistema Operativo',
            'descripcion' => 'No hay alertas críticas en este momento',
            'tiempo' => 'Todo funcionando correctamente'
        ];
    }
    
    return $alertas;
}

/**
 * Maneja las exportaciones
 */
function handleExport($tipo, $subtipo = null) {
    switch ($tipo) {
        case 'resumen':
            exportarResumenGeneral();
            break;
        case 'tabla':
            exportarTabla($subtipo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de exportación no válido']);
    }
}

/**
 * Exporta un resumen general en PDF
 */
function exportarResumenGeneral() {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="resumen-dashboard-' . date('Y-m-d') . '.pdf"');
    echo "PDF de resumen general - Implementar con TCPDF";
}

/**
 * Exporta una tabla específica
 */
function exportarTabla($tipo) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="tabla-' . $tipo . '-' . date('Y-m-d') . '.xlsx"');
    echo "Excel de tabla $tipo - Implementar con PhpSpreadsheet";
}

/**
 * Función auxiliar para calcular tiempo transcurrido
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Hace menos de 1 minuto';
    if ($time < 3600) return 'Hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'Hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'Hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'Hace ' . floor($time/2592000) . ' meses';
    
    return 'Hace ' . floor($time/31536000) . ' años';
}
?>
