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
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar permisos']);
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
    echo json_encode(['success' => false, 'message' => 'ID de permiso no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el permiso existe
    $permiso = $conexion->selectOne("SELECT id, nombre FROM permisos WHERE id = ?", [$id]);
    if (!$permiso) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Permiso no encontrado']);
        exit;
    }

    // Verificar si es un permiso protegido del sistema
    $permisosProtegidos = [
        'dashboard.acceder', 'dashboard.ver',
        'administracion.acceder', 'administracion.ver',
        'administracion.usuarios.acceder', 'administracion.usuarios.ver',
        'administracion.roles_permisos.acceder', 'administracion.roles_permisos.ver'
    ];
    
    if (in_array($permiso['nombre'], $permisosProtegidos)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar un permiso protegido del sistema'
        ]);
        exit;
    }

    // Verificar si hay roles con este permiso
    $rolesConPermiso = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM roles_permisos WHERE permiso_id = ?",
        [$id]
    );

    if ($rolesConPermiso && $rolesConPermiso['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el permiso porque está asignado a roles',
            'roles' => $rolesConPermiso['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar permiso
    $conexion->delete('permisos', 'id = ?', [$id]);

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Permiso eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el permiso: ' . $e->getMessage()]);
}