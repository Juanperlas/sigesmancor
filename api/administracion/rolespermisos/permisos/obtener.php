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
if (!tienePermiso('administracion.roles_permisos.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver permisos']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de permiso no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del permiso
    $conexion = new Conexion();
    $permiso = $conexion->selectOne(
        "SELECT p.*, m.nombre as modulo_nombre
         FROM permisos p
         LEFT JOIN modulos m ON p.modulo_id = m.id
         WHERE p.id = ?",
        [$id]
    );

    if (!$permiso) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Permiso no encontrado']);
        exit;
    }

    // Obtener roles que tienen este permiso
    $roles = $conexion->select(
        "SELECT r.id, r.nombre, r.descripcion
         FROM roles r
         INNER JOIN roles_permisos rp ON r.id = rp.rol_id
         WHERE rp.permiso_id = ?
         ORDER BY r.nombre",
        [$id]
    );

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $permiso['id'],
            'nombre' => $permiso['nombre'],
            'modulo_id' => $permiso['modulo_id'],
            'modulo_nombre' => $permiso['modulo_nombre'],
            'descripcion' => $permiso['descripcion'],
            'creado_en' => $permiso['creado_en'],
            'roles' => $roles
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el permiso: ' . $e->getMessage()]);
}