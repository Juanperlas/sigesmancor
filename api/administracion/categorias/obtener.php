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
if (!tienePermiso('administracion.categorias.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver categorías']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos de la categoría
    $conexion = new Conexion();
    $categoria = $conexion->selectOne(
        "SELECT * FROM categorias_equipos WHERE id = ?",
        [$id]
    );

    if (!$categoria) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        exit;
    }

    // Obtener equipos de la categoría
    $equipos = $conexion->select(
        "SELECT id, codigo, nombre, tipo_equipo, estado, ubicacion 
         FROM equipos 
         WHERE categoria_id = ? 
         ORDER BY codigo ASC",
        [$id]
    );

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $categoria['id'],
            'nombre' => $categoria['nombre'],
            'descripcion' => $categoria['descripcion'],
            'creado_en' => $categoria['creado_en']
        ],
        'equipos' => $equipos,
        'total_equipos' => count($equipos)
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la categoría: ' . $e->getMessage()]);
}
