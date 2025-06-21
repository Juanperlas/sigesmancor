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
if (!tienePermiso('administracion.usuarios.eliminar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar usuarios']);
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
    echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

// No permitir eliminar al usuario actual
if ($id === getUsuarioId()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No puede eliminar su propio usuario']);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el usuario existe
    $usuario = $conexion->selectOne("SELECT id, fotografia FROM usuarios WHERE id = ?", [$id]);
    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Verificar si es superadmin (no se puede eliminar)
    $esSuperAdmin = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM usuarios_roles ur 
         INNER JOIN roles r ON ur.rol_id = r.id 
         WHERE ur.usuario_id = ? AND r.nombre = 'superadmin'",
        [$id]
    );

    if ($esSuperAdmin && $esSuperAdmin['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar un usuario con rol de superadmin'
        ]);
        exit;
    }

    // Verificar si tiene registros asociados (creado_por en otras tablas)
    $registrosAsociados = $conexion->selectOne(
        "SELECT 
            (SELECT COUNT(*) FROM usuarios WHERE creado_por = ?) +
            (SELECT COUNT(*) FROM personal WHERE creado_por = ?) as total",
        [$id, $id]
    );

    if ($registrosAsociados && $registrosAsociados['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el usuario porque tiene registros asociados',
            'registros' => $registrosAsociados['total']
        ]);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    // Eliminar roles del usuario
    $conexion->delete('usuarios_roles', 'usuario_id = ?', [$id]);

    // Eliminar sesiones del usuario
    $conexion->delete('sesiones_usuarios', 'usuario_id = ?', [$id]);

    // Eliminar usuario
    $conexion->delete('usuarios', 'id = ?', [$id]);

    // Eliminar fotografía si existe
    if (!empty($usuario['fotografia']) && file_exists('../../../' . $usuario['fotografia'])) {
        unlink('../../../' . $usuario['fotografia']);
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Usuario eliminado correctamente'
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
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario: ' . $e->getMessage()]);
}