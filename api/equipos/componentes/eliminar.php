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
if (!tienePermiso('componentes.eliminar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar componentes']);
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
    echo json_encode(['success' => false, 'message' => 'ID de componente no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el componente existe
    $componente = $conexion->selectOne("SELECT id, imagen FROM componentes WHERE id = ?", [$id]);
    if (!$componente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Componente no encontrado']);
        exit;
    }

    // Verificar si tiene mantenimientos asociados
    $mantenimientosAsociados = $conexion->selectOne(
        "SELECT 
            (SELECT COUNT(*) FROM mantenimiento_correctivo WHERE componente_id = ?) +
            (SELECT COUNT(*) FROM mantenimiento_preventivo WHERE componente_id = ?) +
            (SELECT COUNT(*) FROM mantenimiento_programado WHERE componente_id = ?) as total",
        [$id, $id, $id]
    );

    if ($mantenimientosAsociados && $mantenimientosAsociados['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el componente porque tiene mantenimientos asociados',
            'mantenimientos' => $mantenimientosAsociados['total']
        ]);
        exit;
    }

    // Verificar si tiene historial de trabajo asociado
    $trabajoAsociado = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM historial_trabajo_componentes WHERE componente_id = ?",
        [$id]
    );
    if ($trabajoAsociado && $trabajoAsociado['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el componente porque tiene registros de historial de trabajo asociados',
            'trabajo' => $trabajoAsociado['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar componente
    $conexion->delete('componentes', 'id = ?', [$id]);

    // Eliminar imagen si existe
    if (!empty($componente['imagen']) && file_exists('../../../' . $componente['imagen'])) {
        unlink('../../../' . $componente['imagen']);
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Componente eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el componente: ' . $e->getMessage()]);
}