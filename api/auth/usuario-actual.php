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

// Obtener datos del usuario actual
$usuario = getUsuarioActual();

// Obtener permisos del usuario
$conexion = new Conexion();
$permisos = $conexion->select(
    "SELECT p.nombre
     FROM permisos p
     INNER JOIN roles_permisos rp ON p.id = rp.permiso_id
     INNER JOIN usuarios_roles ur ON rp.rol_id = ur.rol_id
     WHERE ur.usuario_id = ?
     GROUP BY p.nombre",
    [$_SESSION['usuario_id']]
);

// Preparar respuesta
$response = [
    'success' => true,
    'data' => [
        'id' => $usuario['id'],
        'username' => $usuario['username'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'fotografia' => $usuario['fotografia'],
        'roles' => $usuario['roles'],
        'permisos' => array_column($permisos, 'nombre')
    ]
];

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode($response);