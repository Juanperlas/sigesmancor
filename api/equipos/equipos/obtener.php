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
if (!tienePermiso('equipos.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para ver equipos']);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de equipo no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener datos del equipo
    $conexion = new Conexion();
    $equipo = $conexion->selectOne(
        "SELECT e.*, c.nombre as categoria_nombre
         FROM equipos e
         LEFT JOIN categorias_equipos c ON e.categoria_id = c.id
         WHERE e.id = ?",
        [$id]
    );

    if (!$equipo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
        exit;
    }

    // Verificar si hay imagen
    $imagen = !empty($equipo['imagen']) && file_exists('../../../' . $equipo['imagen'])
        ? getAssetUrl($equipo['imagen'])
        : getAssetUrl('assets/img/equipos/equipos/default.png');

    // Obtener componentes del equipo
    $componentes = $conexion->select(
        "SELECT * FROM componentes WHERE equipo_id = ? ORDER BY codigo ASC",
        [$id]
    );

    // Preparar datos de componentes
    $componentesData = [];
    foreach ($componentes as $componente) {
        // Verificar si hay imagen para el componente
        $imagenComponente = !empty($componente['imagen']) && file_exists('../../../' . $componente['imagen'])
            ? getAssetUrl($componente['imagen'])
            : getAssetUrl('assets/img/equipos/componentes/default.png');
            
        $componentesData[] = [
            'id' => $componente['id'],
            'codigo' => $componente['codigo'],
            'nombre' => $componente['nombre'],
            'marca' => $componente['marca'],
            'modelo' => $componente['modelo'],
            'numero_serie' => $componente['numero_serie'],
            'tipo_orometro' => $componente['tipo_orometro'],
            'anterior_orometro' => $componente['anterior_orometro'],
            'orometro_actual' => $componente['orometro_actual'],
            'proximo_orometro' => $componente['proximo_orometro'],
            'estado' => $componente['estado'],
            'limite' => $componente['limite'],
            'notificacion' => $componente['notificacion'],
            'mantenimiento' => $componente['mantenimiento'],
            'observaciones' => $componente['observaciones'],
            'imagen' => $imagenComponente
        ];
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'id' => $equipo['id'],
            'codigo' => $equipo['codigo'],
            'nombre' => $equipo['nombre'],
            'categoria_id' => $equipo['categoria_id'],
            'categoria_nombre' => $equipo['categoria_nombre'],
            'tipo_equipo' => $equipo['tipo_equipo'],
            'marca' => $equipo['marca'],
            'modelo' => $equipo['modelo'],
            'numero_serie' => $equipo['numero_serie'],
            'capacidad' => $equipo['capacidad'],
            'fase' => $equipo['fase'],
            'linea_electrica' => $equipo['linea_electrica'],
            'ubicacion' => $equipo['ubicacion'],
            'estado' => $equipo['estado'],
            'tipo_orometro' => $equipo['tipo_orometro'],
            'anterior_orometro' => $equipo['anterior_orometro'],
            'orometro_actual' => $equipo['orometro_actual'],
            'proximo_orometro' => $equipo['proximo_orometro'],
            'limite' => $equipo['limite'],
            'notificacion' => $equipo['notificacion'],
            'mantenimiento' => $equipo['mantenimiento'],
            'observaciones' => $equipo['observaciones'],
            'imagen' => $imagen
        ],
        'componentes' => $componentesData,
        'total_componentes' => count($componentesData)
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el equipo: ' . $e->getMessage()]);
}