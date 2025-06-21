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
    echo json_encode(['error' => 'No tiene permisos para ver roles']);
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
    0 => 'r.id',
    1 => 'r.nombre',
    2 => 'r.descripcion',
    3 => 'permisos_count',
    4 => 'r.esta_activo',
    5 => 'r.creado_en'
];

// Filtro de estado (activo/inactivo)
$estado = '';
if (isset($_POST['filtros']) && isset($_POST['filtros']['estado'])) {
    $estado = $_POST['filtros']['estado'];
}

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM roles";
try {
    $totalRecordsResult = $conexion->selectOne($sqlCount);
    $totalRecords = (isset($totalRecordsResult) && isset($totalRecordsResult['total'])) ? $totalRecordsResult['total'] : 0;
} catch (Exception $e) {
    $totalRecords = 0;
}

// Construir la consulta con filtros
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM roles_permisos WHERE rol_id = r.id) as permisos_count
        FROM roles r
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (r.nombre LIKE ? OR r.descripcion LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Aplicar filtro de estado
if ($estado !== '') {
    $estaActivo = $estado === 'activo' ? 1 : 0;
    $sql .= " AND r.esta_activo = ?";
    $params[] = $estaActivo;
}

// Consulta para contar registros filtrados
try {
    $sqlFilteredCount = "SELECT COUNT(*) as total FROM (
        SELECT r.id
        FROM roles r
        WHERE 1=1";
    
    // Añadir las mismas condiciones de filtro
    if (!empty($search)) {
        $sqlFilteredCount .= " AND (r.nombre LIKE ? OR r.descripcion LIKE ?)";
    }
    
    if ($estado !== '') {
        $sqlFilteredCount .= " AND r.esta_activo = ?";
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
    $sql .= " ORDER BY r.id DESC";
}

$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
try {
    $roles = $conexion->select($sql, $params);
} catch (Exception $e) {
    $roles = [];
}

// Preparar datos para DataTables
$data = [];
foreach ($roles as $rol) {
    $data[] = [
        'id' => $rol['id'],
        'nombre' => $rol['nombre'],
        'descripcion' => $rol['descripcion'],
        'permisos_count' => $rol['permisos_count'],
        'esta_activo' => $rol['esta_activo'],
        'creado_en' => $rol['creado_en']
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