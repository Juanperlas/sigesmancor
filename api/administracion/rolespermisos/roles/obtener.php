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
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver roles']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de rol no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del rol
    $conexion = new Conexion();
    $rol = $conexion->selectOne(
        "SELECT r.*
         FROM roles r
         WHERE r.id = ?",
        [$id]
    );

    if (!$rol) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rol no encontrado']);
        exit;
    }

    // Obtener permisos asignados al rol
    $permisos = $conexion->select(
        "SELECT p.id, p.nombre, p.descripcion, m.nombre as modulo_nombre
         FROM permisos p
         INNER JOIN roles_permisos rp ON p.id = rp.permiso_id
         INNER JOIN modulos m ON p.modulo_id = m.id
         WHERE rp.rol_id = ?
         ORDER BY m.nombre, p.nombre",
        [$id]
    );

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $rol['id'],
            'nombre' => $rol['nombre'],
            'descripcion' => $rol['descripcion'],
            'esta_activo' => $rol['esta_activo'],
            'creado_en' => $rol['creado_en'],
            'permisos' => $permisos
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el rol: ' . $e->getMessage()]);
}