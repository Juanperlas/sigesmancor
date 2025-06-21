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
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar roles']);
    exit;
} elseif (!$id && !tienePermiso('administracion.roles_permisos.crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear roles']);
    exit;
}

// Validar campos requeridos
$camposRequeridos = ['nombre'];
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
    'descripcion' => isset($_POST['descripcion']) ? sanitizar($_POST['descripcion']) : null,
    'esta_activo' => isset($_POST['esta_activo']) ? (int)$_POST['esta_activo'] : 1
];

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el nombre ya existe (excepto para el mismo rol en caso de edición)
    $sqlVerificarNombre = "SELECT id FROM roles WHERE nombre = ? AND id != ?";
    $rolExistente = $conexion->selectOne($sqlVerificarNombre, [$datos['nombre'], $id ?: 0]);

    if ($rolExistente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre del rol ya está en uso']);
        exit;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    if ($id) {
        // Actualizar rol existente
        $conexion->update('roles', $datos, 'id = ?', [$id]);
        $mensaje = 'Rol actualizado correctamente';
    } else {
        // Crear nuevo rol
        $id = $conexion->insert('roles', $datos);
        $mensaje = 'Rol creado correctamente';
    }

    // Procesar permisos si se proporcionaron
    if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
        // Eliminar permisos actuales
        $conexion->delete('roles_permisos', 'rol_id = ?', [$id]);

        // Insertar nuevos permisos
        foreach ($_POST['permisos'] as $permisoId) {
            $conexion->insert('roles_permisos', [
                'rol_id' => $id,
                'permiso_id' => intval($permisoId)
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
    echo json_encode(['success' => false, 'message' => 'Error al guardar el rol: ' . $e->getMessage()]);
}