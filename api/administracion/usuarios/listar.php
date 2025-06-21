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
if (!tienePermiso('administracion.usuarios.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver usuarios']);
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
    0 => 'u.id',
    1 => 'u.username',
    2 => 'u.nombre_completo',
    3 => 'u.correo',
    4 => 'u.area',
    5 => 'u.esta_activo'
];

// Filtro de estado (activo/inactivo)
$estado = '';
if (isset($_POST['filtros']) && isset($_POST['filtros']['estado'])) {
    $estado = $_POST['filtros']['estado'];
}

// Filtro de rol
$rolId = null;
if (isset($_POST['filtros']) && isset($_POST['filtros']['rol_id']) && !empty($_POST['filtros']['rol_id'])) {
    $rolId = intval($_POST['filtros']['rol_id']);
}

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM usuarios";
try {
    $totalRecordsResult = $conexion->selectOne($sqlCount);
    $totalRecords = (isset($totalRecordsResult) && isset($totalRecordsResult['total'])) ? $totalRecordsResult['total'] : 0;
} catch (Exception $e) {
    $totalRecords = 0;
}

// Construir la consulta con filtros
$sql = "SELECT u.*, 
        GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_nombres
        FROM usuarios u
        LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id
        LEFT JOIN roles r ON ur.rol_id = r.id
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.nombre_completo LIKE ? OR u.correo LIKE ? OR u.dni LIKE ? OR u.area LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Aplicar filtro de estado
if ($estado !== '') {
    $estaActivo = $estado === 'activo' ? 1 : 0;
    $sql .= " AND u.esta_activo = ?";
    $params[] = $estaActivo;
}

// Aplicar filtro de rol
if (!empty($rolId)) {
    $sql .= " AND EXISTS (SELECT 1 FROM usuarios_roles ur2 WHERE ur2.usuario_id = u.id AND ur2.rol_id = ?)";
    $params[] = $rolId;
}

// Agrupar por usuario para evitar duplicados por roles
$sql .= " GROUP BY u.id";

// Consulta para contar registros filtrados - Enfoque más directo
try {
    // Usar una consulta COUNT directa en lugar de manipular la consulta original
    $sqlFilteredCount = "SELECT COUNT(*) as total FROM (
        SELECT DISTINCT u.id
        FROM usuarios u
        LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id
        LEFT JOIN roles r ON ur.rol_id = r.id
        WHERE 1=1";
    
    // Añadir las mismas condiciones de filtro
    if (!empty($search)) {
        $sqlFilteredCount .= " AND (u.username LIKE ? OR u.nombre_completo LIKE ? OR u.correo LIKE ? OR u.dni LIKE ? OR u.area LIKE ?)";
    }
    
    if ($estado !== '') {
        $sqlFilteredCount .= " AND u.esta_activo = ?";
    }
    
    if (!empty($rolId)) {
        $sqlFilteredCount .= " AND EXISTS (SELECT 1 FROM usuarios_roles ur2 WHERE ur2.usuario_id = u.id AND ur2.rol_id = ?)";
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
    $sql .= " ORDER BY u.id DESC";
}

$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
try {
    $usuarios = $conexion->select($sql, $params);
} catch (Exception $e) {
    $usuarios = [];
}

// Preparar datos para DataTables
$data = [];
foreach ($usuarios as $usuario) {
    $fotografia = !empty($usuario['fotografia']) ? getAssetUrl($usuario['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png');

    $data[] = [
        'id' => $usuario['id'],
        'fotografia' => $fotografia,
        'username' => $usuario['username'],
        'nombre_completo' => $usuario['nombre_completo'],
        'correo' => $usuario['correo'],
        'dni' => $usuario['dni'],
        'telefono' => $usuario['telefono'],
        'direccion' => $usuario['direccion'],
        'area' => $usuario['area'],
        'roles' => $usuario['roles_nombres'],
        'esta_activo' => $usuario['esta_activo'],
        'creado_en' => $usuario['creado_en']
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