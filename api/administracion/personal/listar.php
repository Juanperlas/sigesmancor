<?php
// Suppress warnings and notices to prevent them from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

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
if (!tienePermiso('administracion.personal.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver personal']);
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
    2 => 'p.dni',
    3 => 'p.area',
    4 => 'p.fecha_ingreso',
    5 => 'p.esta_activo'
];

// Filtro de estado (activo/inactivo)
$estado = '';
if (isset($_POST['filtros']) && isset($_POST['filtros']['estado'])) {
    $estado = $_POST['filtros']['estado'];
}

// Filtro de área
$area = '';
if (isset($_POST['filtros']) && isset($_POST['filtros']['area']) && !empty($_POST['filtros']['area'])) {
    $area = $_POST['filtros']['area'];
}

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM personal";
try {
    $totalRecordsResult = $conexion->selectOne($sqlCount);
    $totalRecords = (isset($totalRecordsResult) && isset($totalRecordsResult['total'])) ? $totalRecordsResult['total'] : 0;
} catch (Exception $e) {
    $totalRecords = 0;
}

// Construir la consulta con filtros
$sql = "SELECT p.*, u.nombre_completo as creado_por_nombre 
        FROM personal p
        LEFT JOIN usuarios u ON p.creado_por = u.id
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (p.nombre LIKE ? OR p.dni LIKE ? OR p.telefono LIKE ? OR p.area LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Aplicar filtro de estado
if ($estado !== '') {
    $estaActivo = $estado === 'activo' ? 1 : 0;
    $sql .= " AND p.esta_activo = ?";
    $params[] = $estaActivo;
}

// Aplicar filtro de área
if (!empty($area)) {
    $sql .= " AND p.area = ?";
    $params[] = $area;
}

// Consulta para contar registros filtrados - Enfoque más directo
try {
    // Usar una consulta COUNT directa en lugar de manipular la consulta original
    $sqlFilteredCount = "SELECT COUNT(*) as total FROM (
        SELECT p.id
        FROM personal p
        LEFT JOIN usuarios u ON p.creado_por = u.id
        WHERE 1=1";
    
    // Añadir las mismas condiciones de filtro
    if (!empty($search)) {
        $sqlFilteredCount .= " AND (p.nombre LIKE ? OR p.dni LIKE ? OR p.telefono LIKE ? OR p.area LIKE ?)";
    }
    
    if ($estado !== '') {
        $sqlFilteredCount .= " AND p.esta_activo = ?";
    }
    
    if (!empty($area)) {
        $sqlFilteredCount .= " AND p.area = ?";
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
    $sql .= " ORDER BY p.id DESC";
}

$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
try {
    $personal = $conexion->select($sql, $params);
} catch (Exception $e) {
    $personal = [];
}

// Preparar datos para DataTables
$data = [];
foreach ($personal as $persona) {
    $imagen = !empty($persona['imagen']) ? getAssetUrl($persona['imagen']) : getAssetUrl('assets/img/administracion/personal/default.png');
    
    // Formatear fechas
    $fechaIngreso = !empty($persona['fecha_ingreso']) 
        ? date('d/m/Y', strtotime($persona['fecha_ingreso'])) 
        : '-';
    
    $fechaBaja = !empty($persona['fecha_baja']) 
        ? date('d/m/Y', strtotime($persona['fecha_baja'])) 
        : '-';

    $data[] = [
        'id' => $persona['id'],
        'imagen' => $imagen,
        'nombre' => $persona['nombre'],
        'dni' => $persona['dni'],
        'telefono' => $persona['telefono'],
        'direccion' => $persona['direccion'],
        'area' => $persona['area'],
        'fecha_ingreso' => $fechaIngreso,
        'fecha_baja' => $fechaBaja,
        'esta_activo' => $persona['esta_activo'],
        'creado_por' => $persona['creado_por_nombre'],
        'creado_en' => $persona['creado_en']
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