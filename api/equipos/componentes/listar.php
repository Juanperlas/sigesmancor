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
if (!tienePermiso('componentes.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver componentes']);
    exit;
}

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Mapeo de columnas para ordenamiento
$columns = [
    0 => 'c.id',
    1 => 'c.codigo',
    2 => 'c.nombre',
    3 => 'c.estado',
    4 => 'c.tipo_orometro',
    5 => 'c.anterior_orometro',
    6 => 'c.orometro_actual',
    7 => 'c.proximo_orometro'
];

// Filtro de estado
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

// Filtro de tipo_orometro
$tipoOrometro = isset($_POST['tipo_orometro']) ? $_POST['tipo_orometro'] : '';

// Filtro de equipo_id
$equipoId = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : null;

// Construir la consulta SQL
$conexion = new Conexion();

// Consulta base para contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM componentes";
$totalRecords = $conexion->selectOne($sqlCount);
$totalRecords = $totalRecords['total'];

// Construir la consulta con filtros
$sql = "SELECT c.*, e.nombre as equipo_nombre
        FROM componentes c
        LEFT JOIN equipos e ON c.equipo_id = e.id
        WHERE 1=1";

$params = [];

// Aplicar búsqueda
if (!empty($search)) {
    $sql .= " AND (c.codigo LIKE ? OR c.nombre LIKE ? OR c.marca LIKE ? OR c.modelo LIKE ? OR e.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Aplicar filtro de estado
if (!empty($estado)) {
    $sql .= " AND c.estado = ?";
    $params[] = $estado;
}

// Aplicar filtro de tipo_orometro
if (!empty($tipoOrometro)) {
    $sql .= " AND c.tipo_orometro = ?";
    $params[] = $tipoOrometro;
}

// Aplicar filtro de equipo_id
if (!empty($equipoId)) {
    $sql .= " AND c.equipo_id = ?";
    $params[] = $equipoId;
}

// Consulta para contar registros filtrados
$sqlFilteredCount = $sql;
$totalFiltered = count($conexion->select($sqlFilteredCount, $params));

// Aplicar ordenamiento y paginación
if (isset($columns[$orderColumn])) {
    $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
} else {
    $sql .= " ORDER BY c.id DESC";
}

$sql .= " LIMIT " . $start . ", " . $length;

// Ejecutar la consulta
$componentes = $conexion->select($sql, $params);

// Preparar datos para DataTables
$data = [];
foreach ($componentes as $componente) {
    $imagen = !empty($componente['imagen']) ? getAssetUrl($componente['imagen']) : getAssetUrl('assets/img/equipos/componentes/default.png');

    $data[] = [
        'id' => $componente['id'],
        'imagen' => $imagen,
        'codigo' => $componente['codigo'],
        'nombre' => $componente['nombre'],
        'equipo_id' => $componente['equipo_id'],
        'equipo_nombre' => $componente['equipo_nombre'],
        'marca' => $componente['marca'],
        'modelo' => $componente['modelo'],
        'estado' => $componente['estado'],
        'tipo_orometro' => $componente['tipo_orometro'],
        'anterior_orometro' => $componente['anterior_orometro'],
        'orometro_actual' => $componente['orometro_actual'],
        'proximo_orometro' => $componente['proximo_orometro'],
        'notificacion' => $componente['notificacion'],
        'mantenimiento' => $componente['mantenimiento'],
        'limite' => $componente['limite'],
        'numero_serie' => $componente['numero_serie'] ?: ''
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
