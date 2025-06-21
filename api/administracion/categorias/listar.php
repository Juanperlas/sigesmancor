<?php
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
if (!tienePermiso('administracion.categorias.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver categorías']);
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
    0 => 'c.id',
    1 => 'c.nombre',
    2 => 'c.descripcion',
    3 => 'total_equipos',
    4 => 'c.creado_en'
];

// Filtro de búsqueda
$buscar = isset($_POST['buscar']) ? $_POST['buscar'] : '';

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM categorias_equipos";
$totalRecords = $conexion->selectOne($sqlCount);
$totalRecords = $totalRecords['total'];

// Construir la consulta con filtros
$sql = "SELECT c.*, 
        COALESCE(COUNT(e.id), 0) as total_equipos
        FROM categorias_equipos c
        LEFT JOIN equipos e ON c.id = e.categoria_id
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (c.nombre LIKE ? OR c.descripcion LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Aplicar filtro de búsqueda específico
if (!empty($buscar)) {
    $sql .= " AND c.nombre LIKE ?";
    $params[] = "%$buscar%";
}

// Agrupar por categoría
$sql .= " GROUP BY c.id, c.nombre, c.descripcion, c.creado_en";

// Consulta para contar registros filtrados
$sqlFilteredCount = $sql;
$totalFiltered = count($conexion->select($sqlFilteredCount, $params));

// Aplicar ordenamiento
if (isset($columns[$orderColumn])) {
    $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
} else {
    $sql .= " ORDER BY c.id DESC";
}

// Aplicar paginación
$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
$categorias = $conexion->select($sql, $params);

// Preparar datos para DataTables
$data = [];
foreach ($categorias as $categoria) {
    $data[] = [
        'id' => $categoria['id'],
        'nombre' => $categoria['nombre'],
        'descripcion' => $categoria['descripcion'],
        'total_equipos' => $categoria['total_equipos'],
        'creado_en' => $categoria['creado_en']
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
