<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación y permisos
if (!estaAutenticado() || !tienePermiso('equipos.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener filtros
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';
$tipoOrometro = isset($_POST['tipo_orometro']) ? $_POST['tipo_orometro'] : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
$search = isset($_POST['search']) ? $_POST['search'] : '';

// Construir consulta con TODAS las columnas
$sql = "SELECT 
    e.id,
    e.codigo,
    e.nombre,
    c.nombre as categoria_nombre,
    e.tipo_equipo,
    e.marca,
    e.modelo,
    e.numero_serie,
    e.capacidad,
    e.fase,
    e.linea_electrica,
    e.ubicacion,
    e.estado,
    e.tipo_orometro,
    e.anterior_orometro,
    e.orometro_actual,
    e.proximo_orometro,
    e.limite,
    e.notificacion,
    e.mantenimiento,
    e.observaciones,
    e.imagen,
    e.categoria_id
FROM equipos e
LEFT JOIN categorias_equipos c ON e.categoria_id = c.id
WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($search)) {
    $sql .= " AND (e.codigo LIKE ? OR e.nombre LIKE ? OR e.marca LIKE ? OR e.modelo LIKE ? OR e.ubicacion LIKE ? OR c.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($estado)) {
    $sql .= " AND e.estado = ?";
    $params[] = $estado;
}

if (!empty($tipoOrometro)) {
    $sql .= " AND e.tipo_orometro = ?";
    $params[] = $tipoOrometro;
}

if (!empty($categoria)) {
    $sql .= " AND e.categoria_id = ?";
    $params[] = $categoria;
}

$sql .= " ORDER BY e.codigo ASC";

// Ejecutar consulta
$conexion = new Conexion();
$equipos = $conexion->select($sql, $params);

// Preparar datos para exportación
$data = [];
foreach ($equipos as $equipo) {
    $data[] = [
        'ID' => $equipo['id'],
        'Código' => $equipo['codigo'],
        'Nombre' => $equipo['nombre'],
        'Categoría' => $equipo['categoria_nombre'] ?: 'Sin categoría',
        'Tipo de Equipo' => $equipo['tipo_equipo'],
        'Marca' => $equipo['marca'] ?: '-',
        'Modelo' => $equipo['modelo'] ?: '-',
        'Número de Serie' => $equipo['numero_serie'] ?: '-',
        'Capacidad' => $equipo['capacidad'] ?: '-',
        'Fase' => $equipo['fase'] ?: '-',
        'Línea Eléctrica' => $equipo['linea_electrica'] ?: '-',
        'Ubicación' => $equipo['ubicacion'] ?: '-',
        'Estado' => ucfirst($equipo['estado']),
        'Tipo Orómetro' => ucfirst($equipo['tipo_orometro']),
        'Orómetro Anterior' => number_format($equipo['anterior_orometro'], 2),
        'Orómetro Actual' => number_format($equipo['orometro_actual'], 2),
        'Próximo Orómetro' => $equipo['proximo_orometro'] ? number_format($equipo['proximo_orometro'], 2) : '-',
        'Límite' => $equipo['limite'] ? number_format($equipo['limite'], 2) : '-',
        'Notificación' => $equipo['notificacion'] ? number_format($equipo['notificacion'], 2) : '-',
        'Mantenimiento' => $equipo['mantenimiento'] ? number_format($equipo['mantenimiento'], 2) : '-',
        'Observaciones' => $equipo['observaciones'] ?: '-'
    ];
}

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
?>