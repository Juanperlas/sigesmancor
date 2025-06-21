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
if (!tienePermiso('equipos.eliminar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar equipos']);
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
    echo json_encode(['success' => false, 'message' => 'ID de equipo no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el equipo existe
    $equipo = $conexion->selectOne("SELECT id, imagen FROM equipos WHERE id = ?", [$id]);
    if (!$equipo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
        exit;
    }

    // Verificar si tiene componentes asociados
    $componentesAsociados = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM componentes WHERE equipo_id = ?",
        [$id]
    );

    if ($componentesAsociados && $componentesAsociados['total'] > 0) {
        $componentes = $conexion->select("SELECT codigo, nombre FROM componentes WHERE equipo_id = ?", [$id]);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el equipo porque tiene componentes asociados',
            'componentes' => array_map(fn($c) => $c['codigo'] . ' - ' . $c['nombre'], $componentes)
        ]);
        exit;
    }

    // Verificar si tiene mantenimientos asociados
    $mantenimientosAsociados = $conexion->selectOne(
        "SELECT 
            (SELECT COUNT(*) FROM mantenimiento_correctivo WHERE equipo_id = ?) +
            (SELECT COUNT(*) FROM mantenimiento_preventivo WHERE equipo_id = ?) +
            (SELECT COUNT(*) FROM mantenimiento_programado WHERE equipo_id = ?) as total",
        [$id, $id, $id]
    );

    if ($mantenimientosAsociados && $mantenimientosAsociados['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el equipo porque tiene mantenimientos asociados',
            'mantenimientos' => $mantenimientosAsociados['total']
        ]);
        exit;
    }

    // Verificar si tiene historial de trabajo asociado
    $trabajoAsociado = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM historial_trabajo_equipos WHERE equipo_id = ?",
        [$id]
    );
    if ($trabajoAsociado && $trabajoAsociado['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el equipo porque tiene registros de historial de trabajo asociados',
            'trabajo' => $trabajoAsociado['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar equipo
    $conexion->delete('equipos', 'id = ?', [$id]);

    // Eliminar imagen si existe
    if (!empty($equipo['imagen']) && file_exists('../../../' . $equipo['imagen'])) {
        unlink('../../../' . $equipo['imagen']);
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Equipo eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el equipo: ' . $e->getMessage()]);
}
