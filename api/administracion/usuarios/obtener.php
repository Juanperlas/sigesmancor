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
if (!tienePermiso('administracion.usuarios.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver usuarios']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del usuario
    $conexion = new Conexion();
    $usuario = $conexion->selectOne(
        "SELECT u.*
         FROM usuarios u
         WHERE u.id = ?",
        [$id]
    );

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Obtener roles del usuario
    $roles = $conexion->select(
        "SELECT r.id, r.nombre
         FROM roles r
         INNER JOIN usuarios_roles ur ON r.id = ur.rol_id
         WHERE ur.usuario_id = ?",
        [$id]
    );

    // Verificar si hay fotografía
    $fotografia = !empty($usuario['fotografia']) && file_exists('../../../' . $usuario['fotografia'])
        ? getAssetUrl($usuario['fotografia'])
        : getAssetUrl('assets/img/administracion/usuarios/default.png');

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $usuario['id'],
            'username' => $usuario['username'],
            'nombre_completo' => $usuario['nombre_completo'],
            'correo' => $usuario['correo'],
            'dni' => $usuario['dni'],
            'telefono' => $usuario['telefono'],
            'direccion' => $usuario['direccion'],
            'area' => $usuario['area'],
            'esta_activo' => $usuario['esta_activo'],
            'fotografia' => $fotografia,
            'roles' => $roles
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el usuario: ' . $e->getMessage()]);
}