<?php
// Iniciar sesión
session_start();

// Incluir funciones y conexión a la base de datos
require_once '../../../db/conexion.php';
require_once '../../../db/funciones.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'eventos';
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

// Crear conexión a la base de datos
$conexion = new Conexion();

// Procesar según el tipo de solicitud
try {
    $resultado = [];
    
    switch ($tipo) {
        case 'proximos':
            $resultado = obtenerProximosMantenimientos($conexion);
            break;
        case 'recientes':
            $resultado = obtenerMantenimientosRecientes($conexion);
            break;
        default:
            $resultado = obtenerEventosCalendario($conexion, $start, $end);
            break;
    }
    
    // Devolver los datos en formato JSON
    header('Content-Type: application/json');
    echo json_encode($resultado);
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en calendario.php: " . $e->getMessage());
    
    // Devolver un mensaje de error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}

/**
 * Obtiene los eventos de mantenimiento preventivo para el calendario
 */
function obtenerEventosCalendario($conexion, $start = null, $end = null) {
    // Construir condición de fecha si se proporcionan parámetros
    $whereDate = "";
    $params = [];
    
    if ($start && $end) {
        $whereDate = "AND (
            (mp.estado = 'pendiente' AND mp.fecha_programada BETWEEN ? AND ?) OR
            (mp.estado = 'completado' AND mp.fecha_realizado BETWEEN ? AND ?)
        )";
        $params = [$start, $end, $start, $end];
    }
    
    // Consulta para obtener mantenimientos preventivos
    $query = "
        SELECT 
            mp.id,
            mp.equipo_id,
            mp.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mp.descripcion_razon,
            mp.fecha_programada,
            mp.fecha_realizado,
            mp.estado,
            mp.observaciones
        FROM 
            mantenimiento_preventivo mp
        LEFT JOIN 
            equipos e ON mp.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mp.componente_id = c.id
        WHERE 
            1=1 $whereDate
        ORDER BY 
            CASE 
                WHEN mp.estado = 'pendiente' THEN mp.fecha_programada
                ELSE mp.fecha_realizado
            END
    ";
    
    $mantenimientos = $conexion->select($query, $params);
    
    // Si no hay resultados, devolver array vacío
    if (!$mantenimientos) {
        return [];
    }
    
    // Procesar los resultados
    $eventos = [];
    foreach ($mantenimientos as $mantenimiento) {
        // Determinar el nombre del equipo/componente
        $nombre = $mantenimiento['equipo_nombre'] ?: 
                 ($mantenimiento['componente_nombre'] ?: 'Sin nombre');
        
        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: 
                 ($mantenimiento['codigo_componente'] ?: 'Sin código');
        
        // Crear evento
        $eventos[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $nombre,
            'codigo' => $codigo,
            'descripcion_razon' => $mantenimiento['descripcion_razon'],
            'fecha_programada' => $mantenimiento['fecha_programada'],
            'fecha_realizado' => $mantenimiento['fecha_realizado'],
            'estado' => $mantenimiento['estado'],
            'observaciones' => $mantenimiento['observaciones']
        ];
    }
    
    return $eventos;
}

/**
 * Obtiene los próximos 5 mantenimientos preventivos pendientes
 */
function obtenerProximosMantenimientos($conexion) {
    $query = "
        SELECT 
            mp.id,
            mp.equipo_id,
            mp.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mp.descripcion_razon,
            mp.fecha_programada,
            DATEDIFF(mp.fecha_programada, CURDATE()) as dias_restantes
        FROM 
            mantenimiento_preventivo mp
        LEFT JOIN 
            equipos e ON mp.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mp.componente_id = c.id
        WHERE 
            mp.estado = 'pendiente'
            AND mp.fecha_programada >= CURDATE()
        ORDER BY 
            mp.fecha_programada ASC
        LIMIT 5
    ";
    
    $mantenimientos = $conexion->select($query);
    
    // Si no hay resultados, devolver array vacío
    if (!$mantenimientos) {
        return [];
    }
    
    // Procesar los resultados
    $proximos = [];
    foreach ($mantenimientos as $mantenimiento) {
        // Determinar el nombre del equipo/componente
        $nombre = $mantenimiento['equipo_nombre'] ?: 
                 ($mantenimiento['componente_nombre'] ?: 'Sin nombre');
        
        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: 
                 ($mantenimiento['codigo_componente'] ?: 'Sin código');
        
        // Crear item
        $proximos[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $nombre,
            'codigo' => $codigo,
            'fecha_programada' => $mantenimiento['fecha_programada'],
            'dias_restantes' => $mantenimiento['dias_restantes']
        ];
    }
    
    return $proximos;
}

/**
 * Obtiene los 5 mantenimientos preventivos completados más recientes
 */
function obtenerMantenimientosRecientes($conexion) {
    $query = "
        SELECT 
            mp.id,
            mp.equipo_id,
            mp.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mp.descripcion_razon,
            mp.fecha_realizado
        FROM 
            mantenimiento_preventivo mp
        LEFT JOIN 
            equipos e ON mp.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mp.componente_id = c.id
        WHERE 
            mp.estado = 'completado'
            AND mp.fecha_realizado IS NOT NULL
        ORDER BY 
            mp.fecha_realizado DESC
        LIMIT 5
    ";
    
    $mantenimientos = $conexion->select($query);
    
    // Si no hay resultados, devolver array vacío
    if (!$mantenimientos) {
        return [];
    }
    
    // Procesar los resultados
    $recientes = [];
    foreach ($mantenimientos as $mantenimiento) {
        // Determinar el nombre del equipo/componente
        $nombre = $mantenimiento['equipo_nombre'] ?: 
                 ($mantenimiento['componente_nombre'] ?: 'Sin nombre');
        
        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: 
                 ($mantenimiento['codigo_componente'] ?: 'Sin código');
        
        // Crear item
        $recientes[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $nombre,
            'codigo' => $codigo,
            'fecha_realizado' => $mantenimiento['fecha_realizado']
        ];
    }
    
    return $recientes;
}
