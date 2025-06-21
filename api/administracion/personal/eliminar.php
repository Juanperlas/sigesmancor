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
if (!tienePermiso('administracion.personal.eliminar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar personal']);
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
    echo json_encode(['success' => false, 'message' => 'ID de personal no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el personal existe
    $persona = $conexion->selectOne("SELECT id, imagen FROM personal WHERE id = ?", [$id]);
    if (!$persona) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Personal no encontrado']);
        exit;
    }

    // Verificar si tiene registros asociados (si se necesita)
    // Este es un ejemplo, ajústalo según tus relaciones de base de datos
    $registrosAsociados = $conexion->selectOne(
        "SELECT 
            (SELECT COUNT(*) FROM equipos WHERE responsable_id = ?) +
            (SELECT COUNT(*) FROM mantenimiento_preventivo WHERE responsable_id = ?) as total",
        [$id, $id]
    );

    if ($registrosAsociados && $registrosAsociados['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el personal porque tiene registros asociados',
            'registros' => $registrosAsociados['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar personal
    $conexion->delete('personal', 'id = ?', [$id]);

    // Eliminar imagen si existe
    if (!empty($persona['imagen']) && file_exists('../../../' . $persona['imagen'])) {
        unlink('../../../' . $persona['imagen']);
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Personal eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el personal: ' . $e->getMessage()]);
}