<?php
// Suppress warnings and notices
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Incluir archivos necesarios
require_once '../../db/funciones.php';
require_once '../../db/conexion.php';

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

try {
    // Obtener datos completos del usuario actual
    $conexion = new Conexion();
    $usuario = $conexion->selectOne(
        "SELECT u.id, u.username, u.nombre_completo, u.correo, u.dni, u.telefono, 
                u.direccion, u.area, u.fotografia, u.esta_activo, u.creado_en, u.actualizado_en
         FROM usuarios u
         WHERE u.id = ?",
        [$_SESSION['usuario_id']]
    );

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Obtener roles del usuario
    $roles = $conexion->select(
        "SELECT r.nombre
         FROM roles r
         INNER JOIN usuarios_roles ur ON r.id = ur.rol_id
         WHERE ur.usuario_id = ?",
        [$_SESSION['usuario_id']]
    );

    // Obtener permisos del usuario
    $permisos = $conexion->select(
        "SELECT p.nombre
         FROM permisos p
         INNER JOIN roles_permisos rp ON p.id = rp.permiso_id
         INNER JOIN usuarios_roles ur ON rp.rol_id = ur.rol_id
         WHERE ur.usuario_id = ?
         GROUP BY p.nombre",
        [$_SESSION['usuario_id']]
    );

    // Verificar si hay fotografía
    $fotografia = !empty($usuario['fotografia']) && file_exists('../../' . $usuario['fotografia'])
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
            'fotografia' => $fotografia,
            'esta_activo' => $usuario['esta_activo'],
            'creado_en' => $usuario['creado_en'],
            'actualizado_en' => $usuario['actualizado_en'],
            'roles' => array_column($roles, 'nombre'),
            'permisos' => array_column($permisos, 'nombre')
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del perfil: ' . $e->getMessage()]);
}
?>
