<?php
// Incluir archivos necesarios
require_once '../../../../db/funciones.php';
require_once '../../../../db/conexion.php';

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
if (!tienePermiso('administracion.roles_permisos.editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar roles']);
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
    echo json_encode(['success' => false, 'message' => 'ID de rol no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el rol existe
    $rol = $conexion->selectOne("SELECT id, nombre FROM roles WHERE id = ?", [$id]);
    if (!$rol) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rol no encontrado']);
        exit;
    }

    // Verificar si es un rol protegido (superadmin, admin)
    if (in_array(strtolower($rol['nombre']), ['superadmin', 'admin'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar un rol protegido del sistema'
        ]);
        exit;
    }

    // Verificar si hay usuarios con este rol
    $usuariosConRol = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM usuarios_roles WHERE rol_id = ?",
        [$id]
    );

    if ($usuariosConRol && $usuariosConRol['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el rol porque hay usuarios asignados a él',
            'usuarios' => $usuariosConRol['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar permisos del rol
    $conexion->delete('roles_permisos', 'rol_id = ?', [$id]);

    // Eliminar rol
    $conexion->delete('roles', 'id = ?', [$id]);

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Rol eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el rol: ' . $e->getMessage()]);
}