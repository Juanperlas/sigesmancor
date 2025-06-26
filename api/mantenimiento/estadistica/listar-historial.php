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

try {
    // Parámetros de DataTables
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

    // Mapeo de columnas para ordenamiento
    $columns = [
        0 => 'htd.fecha',
        1 => 'nombre',
        2 => 'codigo',
        3 => 'tipo',
        4 => 'htd.horas_trabajadas',
        5 => 'htd.fuente',
        6 => 'htd.observaciones'
    ];

    // Filtros específicos
    $fechaDesde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
    $fechaHasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $fuente = isset($_POST['fuente']) ? $_POST['fuente'] : '';

    $conexion = new Conexion();

    // Consulta base para contar total de registros
    $sqlCount = "SELECT COUNT(*) as total FROM historial_trabajo_diario";
    $totalRecords = $conexion->selectOne($sqlCount);
    $totalRecords = $totalRecords['total'];

    // Construir la consulta principal
    $sql = "SELECT 
                htd.id,
                htd.fecha,
                htd.horas_trabajadas,
                htd.fuente,
                htd.observaciones,
                CASE 
                    WHEN htd.equipo_id IS NOT NULL THEN e.nombre
                    WHEN htd.componente_id IS NOT NULL THEN c.nombre
                    ELSE 'Sin nombre'
                END as nombre,
                CASE 
                    WHEN htd.equipo_id IS NOT NULL THEN e.codigo
                    WHEN htd.componente_id IS NOT NULL THEN c.codigo
                    ELSE 'Sin código'
                END as codigo,
                CASE 
                    WHEN htd.equipo_id IS NOT NULL THEN 'Equipo'
                    WHEN htd.componente_id IS NOT NULL THEN 'Componente'
                    ELSE 'Desconocido'
                END as tipo,
                CASE 
                    WHEN htd.equipo_id IS NOT NULL THEN e.tipo_orometro
                    WHEN htd.componente_id IS NOT NULL THEN c.tipo_orometro
                    ELSE 'horas'
                END as tipo_orometro
            FROM historial_trabajo_diario htd
            LEFT JOIN equipos e ON htd.equipo_id = e.id
            LEFT JOIN componentes c ON htd.componente_id = c.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($search)) {
        $sql .= " AND (e.nombre LIKE ? OR e.codigo LIKE ? OR c.nombre LIKE ? OR c.codigo LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if (!empty($fechaDesde)) {
        $sql .= " AND htd.fecha >= ?";
        $params[] = $fechaDesde;
    }

    if (!empty($fechaHasta)) {
        $sql .= " AND htd.fecha <= ?";
        $params[] = $fechaHasta;
    }

    if (!empty($tipo)) {
        if ($tipo === 'equipo') {
            $sql .= " AND htd.equipo_id IS NOT NULL";
        } elseif ($tipo === 'componente') {
            $sql .= " AND htd.componente_id IS NOT NULL";
        }
    }

    if (!empty($fuente)) {
        $sql .= " AND htd.fuente = ?";
        $params[] = $fuente;
    }

    // Consulta para contar registros filtrados
    $sqlFilteredCount = $sql;
    $totalFiltered = count($conexion->select($sqlFilteredCount, $params));

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    } else {
        $sql .= " ORDER BY htd.fecha DESC";
    }

    // Aplicar paginación
    $sql .= " LIMIT " . $start . ", " . $length;

    // Ejecutar la consulta
    $registros = $conexion->select($sql, $params);

    // Preparar datos para DataTables
    $data = [];
    foreach ($registros as $registro) {
        $unidad = $registro['tipo_orometro'] === 'kilometros' ? 'km' : 'hrs';
        
        $data[] = [
            'id' => $registro['id'],
            'fecha' => date('d/m/Y', strtotime($registro['fecha'])),
            'nombre' => $registro['nombre'],
            'codigo' => $registro['codigo'],
            'tipo' => $registro['tipo'],
            'horas_trabajadas' => number_format($registro['horas_trabajadas'], 2) . ' ' . $unidad,
            'fuente' => ucfirst($registro['fuente']),
            'observaciones' => $registro['observaciones'] ?: '-'
        ];
    }

    // Preparar respuesta para DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalFiltered,
        'data' => $data
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en listar-historial.php: " . $e->getMessage());
    error_log("SQL Error: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
