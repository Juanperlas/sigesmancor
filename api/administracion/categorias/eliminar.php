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
if (!tienePermiso('administracion.categorias.eliminar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar categorías']);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si la categoría existe
    $categoria = $conexion->selectOne("SELECT id, nombre FROM categorias_equipos WHERE id = ?", [$id]);
    if (!$categoria) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        exit;
    }

    // Verificar si tiene equipos asociados
    $equiposAsociados = $conexion->select(
        "SELECT codigo, nombre FROM equipos WHERE categoria_id = ?",
        [$id]
    );

    if (!empty($equiposAsociados)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar la categoría porque tiene equipos asociados',
            'equipos' => array_map(fn($e) => $e['codigo'] . ' - ' . $e['nombre'], $equiposAsociados)
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar categoría
    $conexion->delete('categorias_equipos', 'id = ?', [$id]);

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Categoría eliminada correctamente'
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conexion) && $conexion->getConexion()) {
        $conexion->getConexion()->rollBack();
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la categoría: ' . $e->getMessage()]);
}
