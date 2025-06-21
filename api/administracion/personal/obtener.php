<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no permitido']);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar permiso
if (!tienePermiso('administracion.personal.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver personal']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de personal no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del personal
    $conexion = new Conexion();
    $persona = $conexion->selectOne(
        "SELECT p.*, u.nombre_completo as creado_por_nombre
         FROM personal p
         LEFT JOIN usuarios u ON p.creado_por = u.id
         WHERE p.id = ?",
        [$id]
    );

    if (!$persona) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Personal no encontrado']);
        exit;
    }

    // Verificar si hay imagen
    $imagen = !empty($persona['imagen']) && file_exists('../../../' . $persona['imagen'])
        ? getAssetUrl($persona['imagen'])
        : getAssetUrl('assets/img/administracion/personal/default.png');

    // Formatear fechas
    $fechaIngreso = !empty($persona['fecha_ingreso']) 
        ? date('Y-m-d', strtotime($persona['fecha_ingreso'])) 
        : null;
    
    $fechaBaja = !empty($persona['fecha_baja']) 
        ? date('Y-m-d', strtotime($persona['fecha_baja'])) 
        : null;

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $persona['id'],
            'nombre' => $persona['nombre'],
            'dni' => $persona['dni'],
            'telefono' => $persona['telefono'],
            'direccion' => $persona['direccion'],
            'area' => $persona['area'],
            'fecha_ingreso' => $fechaIngreso,
            'fecha_baja' => $fechaBaja,
            'esta_activo' => $persona['esta_activo'],
            'imagen' => $imagen,
            'creado_por' => $persona['creado_por'],
            'creado_por_nombre' => $persona['creado_por_nombre'],
            'creado_en' => $persona['creado_en']
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el personal: ' . $e->getMessage()]);
}