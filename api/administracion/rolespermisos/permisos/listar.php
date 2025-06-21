<?php
// Suppress warnings and notices to prevent them from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Incluir archivos necesarios
require_once '../../../../db/funciones.php';
require_once '../../../../db/conexion.php';

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
if (!tienePermiso('administracion.roles_permisos.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver permisos']);
    exit;
}

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Mapeo de columnas para ordenamiento
$columns = [
    0 => 'p.id',
    1 => 'p.nombre',
    2 => 'm.nombre',
    3 => 'p.descripcion',
    4 => 'roles_count'
];

// Filtro de módulo
$moduloId = null;
if (isset($_POST['filtros']) && isset($_POST['filtros']['modulo_id']) && !empty($_POST['filtros']['modulo_id'])) {
    $moduloId = intval($_POST['filtros']['modulo_id']);
}

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM permisos";
try {
    $totalRecordsResult = $conexion->selectOne($sqlCount);
    $totalRecords = (isset($totalRecordsResult) && isset($totalRecordsResult['total'])) ? $totalRecordsResult['total'] : 0;
} catch (Exception $e) {
    $totalRecords = 0;
}

// Construir la consulta con filtros
$sql = "SELECT p.*, m.nombre as modulo_nombre,
        (SELECT COUNT(*) FROM roles_permisos WHERE permiso_id = p.id) as roles_count
        FROM permisos p
        LEFT JOIN modulos m ON p.modulo_id = m.id
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR m.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Aplicar filtro de módulo
if (!empty($moduloId)) {
    $sql .= " AND p.modulo_id = ?";
    $params[] = $moduloId;
}

// Consulta para contar registros filtrados
try {
    $sqlFilteredCount = "SELECT COUNT(*) as total FROM (
        SELECT p.id
        FROM permisos p
        LEFT JOIN modulos m ON p.modulo_id = m.id
        WHERE 1=1";
    
    // Añadir las mismas condiciones de filtro
    if (!empty($search)) {
        $sqlFilteredCount .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR m.nombre LIKE ?)";
    }
    
    if (!empty($moduloId)) {
        $sqlFilteredCount .= " AND p.modulo_id = ?";
    }
    
    $sqlFilteredCount .= ") as filtered_count";
    
    $totalFilteredResult = $conexion->selectOne($sqlFilteredCount, $params);
    $totalFiltered = (isset($totalFilteredResult) && isset($totalFilteredResult['total'])) ? $totalFilteredResult['total'] : $totalRecords;
} catch (Exception $e) {
    // En caso de error, usar el total de registros como fallback
    $totalFiltered = $totalRecords;
}

// Aplicar ordenamiento y paginación
if (isset($columns[$orderColumn])) {
    $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
} else {
    $sql .= " ORDER BY m.nombre ASC, p.nombre ASC";
}

$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
try {
    $permisos = $conexion->select($sql, $params);
} catch (Exception $e) {
    $permisos = [];
}

// Preparar datos para DataTables
$data = [];
foreach ($permisos as $permiso) {
    $data[] = [
        'id' => $permiso['id'],
        'nombre' => $permiso['nombre'],
        'modulo_id' => $permiso['modulo_id'],
        'modulo_nombre' => $permiso['modulo_nombre'],
        'descripcion' => $permiso['descripcion'],
        'roles_count' => $permiso['roles_count'],
        'creado_en' => $permiso['creado_en']
    ];
}

// Preparar respuesta para DataTables
$response = [
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => $data
];

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode($response);