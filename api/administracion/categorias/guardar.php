<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Establecer cabeceras para respuesta JSON
header('Content-Type: application/json');

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

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar permiso según la operación (crear o editar)
$id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
if ($id && !tienePermiso('administracion.categorias.editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar categorías']);
    exit;
} elseif (!$id && !tienePermiso('administracion.categorias.crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear categorías']);
    exit;
}

// Validar campos requeridos
if (!isset($_POST['nombre']) || empty($_POST['nombre'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit;
}

// Sanitizar y preparar datos
$datos = [
    'nombre' => sanitizar($_POST['nombre']),
    'descripcion' => isset($_POST['descripcion']) ? sanitizar($_POST['descripcion']) : null
];

// Validar longitud del nombre
if (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre debe tener entre 2 y 50 caracteres']);
    exit;
}

// Validar longitud de la descripción
if ($datos['descripcion'] && strlen($datos['descripcion']) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La descripción no puede tener más de 500 caracteres']);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el nombre ya existe (excepto para la misma categoría en caso de edición)
    $sqlVerificarNombre = "SELECT id FROM categorias_equipos WHERE nombre = ? AND id != ?";
    $categoriaExistente = $conexion->selectOne($sqlVerificarNombre, [$datos['nombre'], $id ?: 0]);

    if ($categoriaExistente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre ya está en uso por otra categoría']);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    if ($id) {
        // Actualizar categoría existente
        $conexion->update('categorias_equipos', $datos, 'id = ?', [$id]);
        $mensaje = 'Categoría actualizada correctamente';
    } else {
        // Crear nueva categoría
        $id = $conexion->insert('categorias_equipos', $datos);
        $mensaje = 'Categoría creada correctamente';
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => $mensaje,
        'id' => $id
    ];

    // Enviar respuesta
    echo json_encode($response);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conexion) && $conexion->getConexion()) {
        $conexion->getConexion()->rollBack();
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la categoría: ' . $e->getMessage()]);
}
