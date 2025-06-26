<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Establecer cabeceras para respuesta JSON
header('Content-Type: application/json');

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no permitido']);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Verificar permiso
if (!tienePermiso('estadisticas.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver estadísticas']);
    exit;
}

$conexion = new Conexion();

try {
    // Obtener parámetros de filtrado
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
    $buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

    // Construir consulta base
    $sql = "
        SELECT 
            'equipo' as tipo,
            hte.fecha,
            e.nombre,
            e.codigo,
            e.tipo_equipo,
            hte.horas_trabajadas,
            hte.fuente,
            hte.observaciones,
            hte.creado_en
        FROM historial_trabajo_equipos hte
        INNER JOIN equipos e ON hte.equipo_id = e.id
        WHERE 1=1
    ";

    $params = [];

    // Agregar filtro de tipo si no es 'todos'
    if ($tipo === 'equipo') {
        // Solo equipos, ya está en la consulta base
    } else if ($tipo === 'componente') {
        // Cambiar a consulta de componentes
        $sql = "
            SELECT 
                'componente' as tipo,
                htc.fecha,
                c.nombre,
                c.codigo,
                c.modelo as tipo_equipo,
                htc.horas_trabajadas,
                htc.fuente,
                htc.observaciones,
                htc.creado_en
            FROM historial_trabajo_componentes htc
            INNER JOIN componentes c ON htc.componente_id = c.id
            WHERE 1=1
        ";
    } else {
        // Todos: UNION de equipos y componentes
        $sql = "
            (SELECT 
                'equipo' as tipo,
                hte.fecha,
                e.nombre,
                e.codigo,
                e.tipo_equipo,
                hte.horas_trabajadas,
                hte.fuente,
                hte.observaciones,
                hte.creado_en
            FROM historial_trabajo_equipos hte
            INNER JOIN equipos e ON hte.equipo_id = e.id
            WHERE 1=1
        ";
    }

    // Agregar filtros de fecha
    if ($fechaDesde) {
        if ($tipo === 'todos') {
            $sql .= " AND hte.fecha >= ?";
        } else {
            $sql .= " AND " . ($tipo === 'equipo' ? 'hte' : 'htc') . ".fecha >= ?";
        }
        $params[] = $fechaDesde;
    }

    if ($fechaHasta) {
        if ($tipo === 'todos') {
            $sql .= " AND hte.fecha <= ?";
        } else {
            $sql .= " AND " . ($tipo === 'equipo' ? 'hte' : 'htc') . ".fecha <= ?";
        }
        $params[] = $fechaHasta;
    }

    // Agregar filtro de búsqueda
    if ($buscar) {
        if ($tipo === 'todos') {
            $sql .= " AND (e.nombre LIKE ? OR e.codigo LIKE ?)";
        } else {
            $tabla = $tipo === 'equipo' ? 'e' : 'c';
            $sql .= " AND ($tabla.nombre LIKE ? OR $tabla.codigo LIKE ?)";
        }
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }

    // Completar consulta UNION si es necesario
    if ($tipo === 'todos') {
        $sql .= ")
            UNION ALL
            (SELECT 
                'componente' as tipo,
                htc.fecha,
                c.nombre,
                c.codigo,
                c.modelo as tipo_equipo,
                htc.horas_trabajadas,
                htc.fuente,
                htc.observaciones,
                htc.creado_en
            FROM historial_trabajo_componentes htc
            INNER JOIN componentes c ON htc.componente_id = c.id
            WHERE 1=1
        ";

        // Repetir filtros para la segunda parte del UNION
        if ($fechaDesde) {
            $sql .= " AND htc.fecha >= ?";
            $params[] = $fechaDesde;
        }

        if ($fechaHasta) {
            $sql .= " AND htc.fecha <= ?";
            $params[] = $fechaHasta;
        }

        if ($buscar) {
            $sql .= " AND (c.nombre LIKE ? OR c.codigo LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
        }

        $sql .= ")";
    }

    // Ordenar por fecha descendente
    $sql .= " ORDER BY fecha DESC, creado_en DESC";

    $resultados = $conexion->select($sql, $params);

    // Formatear los datos para DataTables
    $data = [];
    foreach ($resultados as $row) {
        $data[] = [
            'fecha' => date('d/m/Y', strtotime($row['fecha'])),
            'equipo_componente' => $row['nombre'],
            'codigo' => $row['codigo'],
            'tipo' => ucfirst($row['tipo']),
            'tipo_equipo' => $row['tipo_equipo'],
            'horas_trabajadas' => number_format($row['horas_trabajadas'], 2),
            'fuente' => ucfirst($row['fuente']),
            'observaciones' => $row['observaciones'] ?: '-'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
