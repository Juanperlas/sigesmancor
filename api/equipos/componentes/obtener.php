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
if (!tienePermiso('componentes.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver componentes']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de componente no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del componente
    $conexion = new Conexion();
    $componente = $conexion->selectOne(
        "SELECT c.*, e.nombre as equipo_nombre
         FROM componentes c
         LEFT JOIN equipos e ON c.equipo_id = e.id
         WHERE c.id = ?",
        [$id]
    );

    if (!$componente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Componente no encontrado']);
        exit;
    }

    // Verificar si hay imagen
    $imagen = !empty($componente['imagen']) && file_exists('../../../' . $componente['imagen'])
        ? getAssetUrl($componente['imagen'])
        : getAssetUrl('assets/img/equipos/componentes/default.png');

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $componente['id'],
            'codigo' => $componente['codigo'],
            'nombre' => $componente['nombre'],
            'equipo_id' => $componente['equipo_id'],
            'equipo_nombre' => $componente['equipo_nombre'],
            'marca' => $componente['marca'],
            'modelo' => $componente['modelo'],
            'numero_serie' => $componente['numero_serie'],
            'estado' => $componente['estado'],
            'tipo_orometro' => $componente['tipo_orometro'],
            'anterior_orometro' => $componente['anterior_orometro'],
            'orometro_actual' => $componente['orometro_actual'],
            'proximo_orometro' => $componente['proximo_orometro'],
            'limite' => $componente['limite'],
            'notificacion' => $componente['notificacion'],
            'mantenimiento' => $componente['mantenimiento'],
            'observaciones' => $componente['observaciones'],
            'imagen' => $imagen
        ]
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el componente: ' . $e->getMessage()]);
}