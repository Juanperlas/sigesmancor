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
if ($id && !tienePermiso('administracion.personal.editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar personal']);
    exit;
} elseif (!$id && !tienePermiso('administracion.personal.crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear personal']);
    exit;
}

// Validar campos requeridos
$camposRequeridos = ['nombre', 'fecha_ingreso'];
foreach ($camposRequeridos as $campo) {
    if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El campo ' . $campo . ' es requerido']);
        exit;
    }
}

// Validar tamaño y tipo de archivo solo si se subió una nueva imagen
$maxFileSize = 2 * 1024 * 1024; // 2MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
    if ($_FILES['imagen']['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande. El tamaño máximo es 2MB.']);
        exit;
    }
    if (!in_array($_FILES['imagen']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Por favor, seleccione una imagen válida (JPEG, PNG, GIF, WEBP).']);
        exit;
    }
}

// Sanitizar y preparar datos
$datos = [
    'nombre' => sanitizar($_POST['nombre']),
    'dni' => isset($_POST['dni']) ? sanitizar($_POST['dni']) : null,
    'telefono' => isset($_POST['telefono']) ? sanitizar($_POST['telefono']) : null,
    'direccion' => isset($_POST['direccion']) ? sanitizar($_POST['direccion']) : null,
    'area' => isset($_POST['area']) ? sanitizar($_POST['area']) : null,
    'fecha_ingreso' => date('Y-m-d', strtotime($_POST['fecha_ingreso'])),
    'fecha_baja' => !empty($_POST['fecha_baja']) ? date('Y-m-d', strtotime($_POST['fecha_baja'])) : null,
    'esta_activo' => isset($_POST['esta_activo']) ? (int)$_POST['esta_activo'] : 1,
    'creado_por' => getUsuarioId(),
];

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el DNI ya existe (si se proporcionó) - excepto para el mismo registro en caso de edición
    if (!empty($datos['dni'])) {
        $sqlVerificarDNI = "SELECT id FROM personal WHERE dni = ? AND id != ?";
        $dniExistente = $conexion->selectOne($sqlVerificarDNI, [$datos['dni'], $id ?: 0]);

        if ($dniExistente) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El DNI ya está en uso']);
            exit;
        }
    }

    // Procesar imagen si se ha subido
    $imagenGuardada = null;
    $imageBasePath = __DIR__ . '/../../../assets/img/administracion/personal/';
    $imageUrlPrefix = 'assets/img/administracion/personal/';

    // Verificar si existe el directorio, si no, crearlo
    if (!file_exists($imageBasePath)) {
        if (!mkdir($imageBasePath, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear el directorio para imágenes']);
            exit;
        }
    }

    // Verificar permisos de escritura
    if (!is_writable($imageBasePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'El directorio de imágenes no tiene permisos de escritura']);
        exit;
    }

    // Verificar si se ha subido una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
        // Generar nombre único para la imagen
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'personal_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaCompleta = $imageBasePath . $nombreArchivo;

        // Si estamos editando, obtener la imagen anterior
        $imagenAnterior = null;
        if ($id) {
            $sqlImagenActual = "SELECT imagen FROM personal WHERE id = ?";
            $stmtImagen = $conexion->getConexion()->prepare($sqlImagenActual);
            $stmtImagen->execute([$id]);
            $resultImagen = $stmtImagen->fetch(PDO::FETCH_ASSOC);
            if ($resultImagen && !empty($resultImagen['imagen'])) {
                $imagenAnterior = $resultImagen['imagen'];
            }
        }

        // Mover archivo subido
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaCompleta)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al mover la imagen al servidor']);
            exit;
        }

        $imagenGuardada = $imageUrlPrefix . $nombreArchivo;
        $datos['imagen'] = $imagenGuardada;

        // Eliminar imagen anterior si existe
        if ($imagenAnterior) {
            $rutaImagenAnterior = __DIR__ . '/../../../' . $imagenAnterior;
            if (file_exists($rutaImagenAnterior)) {
                if (!unlink($rutaImagenAnterior)) {
                    error_log("No se pudo eliminar la imagen anterior: " . $rutaImagenAnterior);
                }
            }
        }
    } elseif (isset($_POST['existing_imagen']) && !empty($_POST['existing_imagen']) && (!isset($_POST['removed_imagen']) || $_POST['removed_imagen'] != '1')) {
        // Mantener la imagen existente
        $rutaExistente = sanitizar($_POST['existing_imagen']);
        $prefijo = '/sigesmancor/';
        if (strpos($rutaExistente, $prefijo) === 0) {
            $rutaExistente = substr($rutaExistente, strlen($prefijo));
        }
        $datos['imagen'] = $rutaExistente;
    } elseif (isset($_POST['removed_imagen']) && $_POST['removed_imagen'] == '1') {
        // Si se marcó para eliminar la imagen
        $datos['imagen'] = null;
        if (isset($_POST['existing_imagen']) && !empty($_POST['existing_imagen'])) {
            $rutaExistente = sanitizar($_POST['existing_imagen']);
            $prefijo = '/sigesmancor/';
            if (strpos($rutaExistente, $prefijo) === 0) {
                $rutaExistente = substr($rutaExistente, strlen($prefijo));
            }
            $rutaImagen = __DIR__ . '/../../../' . $rutaExistente;
            if (file_exists($rutaImagen)) {
                if (!unlink($rutaImagen)) {
                    error_log("No se pudo eliminar la imagen existente: " . $rutaImagen);
                }
            }
        }
    } else {
        // No se subió imagen nueva ni hay imagen existente
        $datos['imagen'] = null;
    }

    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    if ($id) {
        // Actualizar personal existente
        if (!isset($datos['imagen']) && !isset($_POST['removed_imagen'])) {
            // Si no se ha subido una nueva imagen y no se ha marcado para eliminar, mantener la actual
            unset($datos['imagen']);
        }

        // Eliminar creado_por si existe (no se debe actualizar)
        if (isset($datos['creado_por'])) {
            unset($datos['creado_por']);
        }

        // Actualizar en la base de datos
        $conexion->update('personal', $datos, 'id = ?', [$id]);
        $mensaje = 'Personal actualizado correctamente';
    } else {
        // Crear nuevo personal
        $id = $conexion->insert('personal', $datos);
        $mensaje = 'Personal creado correctamente';
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

    // Eliminar imagen subida si hubo error
    if (isset($imagenGuardada) && $imagenGuardada && file_exists('../../../' . $imagenGuardada)) {
        unlink('../../../' . $imagenGuardada);
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el personal: ' . $e->getMessage()]);
}
