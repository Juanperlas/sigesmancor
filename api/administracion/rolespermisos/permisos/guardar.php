<?php
// Incluir archivos necesarios
require_once '../../../../db/funciones.php';
require_once '../../../../db/conexion.php';

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
if ($id && !tienePermiso('administracion.roles_permisos.editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar permisos']);
    exit;
} elseif (!$id && !tienePermiso('administracion.roles_permisos.crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear permisos']);
    exit;
}

// Validar campos requeridos
$camposRequeridos = ['nombre', 'modulo_id'];
foreach ($camposRequeridos as $campo) {
    if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El campo ' . $campo . ' es requerido']);
        exit;
    }
}

// Sanitizar y preparar datos
$datos = [
    'nombre' => sanitizar($_POST['nombre']),
    'modulo_id' => intval($_POST['modulo_id']),
    'descripcion' => isset($_POST['descripcion']) ? sanitizar($_POST['descripcion']) : null
];

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el módulo existe
    $modulo = $conexion->selectOne("SELECT id FROM modulos WHERE id = ?", [$datos['modulo_id']]);
    if (!$modulo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El módulo seleccionado no existe']);
        exit;
    }

    // Verificar si el nombre ya existe (excepto para el mismo permiso en caso de edición)
    $sqlVerificarNombre = "SELECT id FROM permisos WHERE nombre = ? AND id != ?";
    $permisoExistente = $conexion->selectOne($sqlVerificarNombre, [$datos['nombre'], $id ?: 0]);

    if ($permisoExistente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre del permiso ya está en uso']);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    if ($id) {
        // Actualizar permiso existente
        $conexion->update('permisos', $datos, 'id = ?', [$id]);
        $mensaje = 'Permiso actualizado correctamente';
    } else {
        // Crear nuevo permiso
        $id = $conexion->insert('permisos', $datos);
        $mensaje = 'Permiso creado correctamente';
    }

    // Procesar roles si se proporcionaron
    if (isset($_POST['roles']) && is_array($_POST['roles'])) {
        // Eliminar asignaciones actuales
        $conexion->delete('roles_permisos', 'permiso_id = ?', [$id]);

        // Insertar nuevas asignaciones
        foreach ($_POST['roles'] as $rolId) {
            $conexion->insert('roles_permisos', [
                'rol_id' => intval($rolId),
                'permiso_id' => $id
            ]);
        }
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
    echo json_encode(['success' => false, 'message' => 'Error al guardar el permiso: ' . $e->getMessage()]);
}