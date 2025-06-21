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
        case 'recientes':
            $resultado = obtenerMantenimientosRecientes($conexion);
            break;
        case 'pendientes':
            $resultado = obtenerMantenimientosPendientes($conexion);
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
    error_log("Error en calendario.php (correctivo): " . $e->getMessage());

    // Devolver un mensaje de error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}

/**
 * Obtiene los eventos de mantenimiento correctivo para el calendario
 */
function obtenerEventosCalendario($conexion, $start = null, $end = null)
{
    // Construir condición de fecha si se proporcionan parámetros
    $whereDate = "";
    $params = [];

    if ($start && $end) {
        $whereDate = "AND (
            (mc.estado = 'pendiente' AND mc.fecha_hora_problema BETWEEN ? AND ?) OR
            (mc.estado = 'completado' AND mc.fecha_realizado BETWEEN ? AND ?)
        )";
        $params = [$start, $end, $start, $end];
    }

    // Consulta para obtener mantenimientos correctivos
    $query = "
        SELECT 
            mc.id,
            mc.equipo_id,
            mc.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mc.descripcion_problema,
            mc.fecha_hora_problema,
            mc.fecha_realizado,
            mc.estado,
            mc.observaciones,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item
        FROM 
            mantenimiento_correctivo mc
        LEFT JOIN 
            equipos e ON mc.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mc.componente_id = c.id
        WHERE 
            1=1 $whereDate
        ORDER BY 
            CASE 
                WHEN mc.estado = 'pendiente' THEN mc.fecha_hora_problema
                ELSE mc.fecha_realizado
            END DESC
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
        $nombre = $mantenimiento['equipo_nombre'] ?: ($mantenimiento['componente_nombre'] ?: 'Sin nombre');

        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: ($mantenimiento['codigo_componente'] ?: 'Sin código');

        // Crear evento
        $eventos[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $mantenimiento['equipo_nombre'],
            'componente_nombre' => $mantenimiento['componente_nombre'],
            'codigo' => $codigo,
            'descripcion_problema' => $mantenimiento['descripcion_problema'],
            'fecha_hora_problema' => $mantenimiento['fecha_hora_problema'],
            'fecha_realizado' => $mantenimiento['fecha_realizado'],
            'estado' => $mantenimiento['estado'],
            'observaciones' => $mantenimiento['observaciones'],
            'tipo_item' => $mantenimiento['tipo_item']
        ];
    }

    return $eventos;
}

/**
 * Obtiene los 10 mantenimientos correctivos más recientes
 */
function obtenerMantenimientosRecientes($conexion)
{
    $query = "
        SELECT 
            mc.id,
            mc.equipo_id,
            mc.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mc.descripcion_problema,
            mc.fecha_hora_problema,
            mc.fecha_realizado,
            mc.estado,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item
        FROM 
            mantenimiento_correctivo mc
        LEFT JOIN 
            equipos e ON mc.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mc.componente_id = c.id
        ORDER BY 
            mc.fecha_hora_problema DESC
        LIMIT 10
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
        $nombre = $mantenimiento['equipo_nombre'] ?: ($mantenimiento['componente_nombre'] ?: 'Sin nombre');

        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: ($mantenimiento['codigo_componente'] ?: 'Sin código');

        // Crear item
        $recientes[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $mantenimiento['equipo_nombre'],
            'componente_nombre' => $mantenimiento['componente_nombre'],
            'codigo' => $codigo,
            'fecha_hora_problema' => $mantenimiento['fecha_hora_problema'],
            'fecha_realizado' => $mantenimiento['fecha_realizado'],
            'estado' => $mantenimiento['estado'],
            'tipo_item' => $mantenimiento['tipo_item']
        ];
    }

    return $recientes;
}

/**
 * Obtiene los mantenimientos correctivos pendientes
 */
function obtenerMantenimientosPendientes($conexion)
{
    $query = "
        SELECT 
            mc.id,
            mc.equipo_id,
            mc.componente_id,
            e.codigo as codigo,
            c.codigo as codigo_componente,
            e.nombre as equipo_nombre,
            c.nombre as componente_nombre,
            mc.descripcion_problema,
            mc.fecha_hora_problema,
            mc.estado,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item
        FROM 
            mantenimiento_correctivo mc
        LEFT JOIN 
            equipos e ON mc.equipo_id = e.id
        LEFT JOIN 
            componentes c ON mc.componente_id = c.id
        WHERE 
            mc.estado = 'pendiente'
        ORDER BY 
            mc.fecha_hora_problema DESC
        LIMIT 10
    ";

    $mantenimientos = $conexion->select($query);

    // Si no hay resultados, devolver array vacío
    if (!$mantenimientos) {
        return [];
    }

    // Procesar los resultados
    $pendientes = [];
    foreach ($mantenimientos as $mantenimiento) {
        // Determinar el nombre del equipo/componente
        $nombre = $mantenimiento['equipo_nombre'] ?: ($mantenimiento['componente_nombre'] ?: 'Sin nombre');

        // Determinar el código
        $codigo = $mantenimiento['codigo'] ?: ($mantenimiento['codigo_componente'] ?: 'Sin código');

        // Crear item
        $pendientes[] = [
            'id' => $mantenimiento['id'],
            'equipo_id' => $mantenimiento['equipo_id'],
            'componente_id' => $mantenimiento['componente_id'],
            'equipo_nombre' => $mantenimiento['equipo_nombre'],
            'componente_nombre' => $mantenimiento['componente_nombre'],
            'codigo' => $codigo,
            'fecha_hora_problema' => $mantenimiento['fecha_hora_problema'],
            'estado' => $mantenimiento['estado'],
            'tipo_item' => $mantenimiento['tipo_item']
        ];
    }

    return $pendientes;
}
