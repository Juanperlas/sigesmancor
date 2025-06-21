<?php
// Incluir archivos necesarios
require_once '../../db/funciones.php';
require_once '../../db/conexion.php';

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

// Obtener ID del usuario actual
$usuarioId = getUsuarioId();

// Validar campos requeridos
if (!isset($_POST['nombre_completo']) || empty($_POST['nombre_completo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre completo es requerido']);
    exit;
}

// Validar tamaño y tipo de archivo solo si se subió una nueva fotografía
$maxFileSize = 2 * 1024 * 1024; // 2MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK && $_FILES['fotografia']['size'] > 0) {
    if ($_FILES['fotografia']['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La fotografía es demasiado grande. El tamaño máximo es 2MB.']);
        exit;
    }
    if (!in_array($_FILES['fotografia']['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Por favor, seleccione una imagen válida (JPEG, PNG, GIF, WEBP).']);
        exit;
    }
}

// Sanitizar y preparar datos
$datos = [
    'nombre_completo' => sanitizar($_POST['nombre_completo']),
    'correo' => isset($_POST['correo']) ? sanitizar($_POST['correo']) : null,
    'dni' => isset($_POST['dni']) ? sanitizar($_POST['dni']) : null,
    'telefono' => isset($_POST['telefono']) ? sanitizar($_POST['telefono']) : null,
    'direccion' => isset($_POST['direccion']) ? sanitizar($_POST['direccion']) : null,
    'area' => isset($_POST['area']) ? sanitizar($_POST['area']) : null,
];

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el correo ya existe (si se proporcionó)
    if (!empty($datos['correo'])) {
        $sqlVerificarCorreo = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
        $correoExistente = $conexion->selectOne($sqlVerificarCorreo, [$datos['correo'], $usuarioId]);

        if ($correoExistente) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está en uso']);
            exit;
        }
    }

    // Verificar si el DNI ya existe (si se proporcionó)
    if (!empty($datos['dni'])) {
        $sqlVerificarDNI = "SELECT id FROM usuarios WHERE dni = ? AND id != ?";
        $dniExistente = $conexion->selectOne($sqlVerificarDNI, [$datos['dni'], $usuarioId]);

        if ($dniExistente) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El DNI ya está en uso']);
            exit;
        }
    }

    // Verificar contraseña actual si se quiere cambiar
    if (isset($_POST['contrasena_actual']) && !empty($_POST['contrasena_actual'])) {
        $usuarioActual = $conexion->selectOne("SELECT contrasena FROM usuarios WHERE id = ?", [$usuarioId]);

        if (!$usuarioActual || !password_verify($_POST['contrasena_actual'], $usuarioActual['contrasena'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
            exit;
        }

        // Añadir nueva contraseña si se proporcionó
        if (isset($_POST['contrasena_nueva']) && !empty($_POST['contrasena_nueva'])) {
            $datos['contrasena'] = password_hash($_POST['contrasena_nueva'], PASSWORD_DEFAULT);
        }
    }

    // Procesar fotografía si se ha subido
    $fotografiaGuardada = null;
    $imageBasePath = __DIR__ . '/../../assets/img/administracion/usuarios/';
    $imageUrlPrefix = 'assets/img/administracion/usuarios/';

    // Verificar si existe el directorio, si no, crearlo
    if (!file_exists($imageBasePath)) {
        if (!mkdir($imageBasePath, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear el directorio para fotografías']);
            exit;
        }
    }

    // Verificar si se ha subido una nueva fotografía
    if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK && $_FILES['fotografia']['size'] > 0) {
        // Obtener la fotografía anterior
        $fotografiaAnterior = null;
        $sqlFotografiaActual = "SELECT fotografia FROM usuarios WHERE id = ?";
        $stmtFotografia = $conexion->getConexion()->prepare($sqlFotografiaActual);
        $stmtFotografia->execute([$usuarioId]);
        $resultFotografia = $stmtFotografia->fetch(PDO::FETCH_ASSOC);
        if ($resultFotografia && !empty($resultFotografia['fotografia'])) {
            $fotografiaAnterior = $resultFotografia['fotografia'];
        }

        // Generar nombre único para la fotografía
        $extension = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'usuario_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaCompleta = $imageBasePath . $nombreArchivo;

        // Mover archivo subido
        if (!move_uploaded_file($_FILES['fotografia']['tmp_name'], $rutaCompleta)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al mover la fotografía al servidor']);
            exit;
        }

        $fotografiaGuardada = $imageUrlPrefix . $nombreArchivo;
        $datos['fotografia'] = $fotografiaGuardada;

        // Eliminar fotografía anterior si existe
        if ($fotografiaAnterior) {
            $rutaFotografiaAnterior = __DIR__ . '/../../' . $fotografiaAnterior;
            if (file_exists($rutaFotografiaAnterior)) {
                if (!unlink($rutaFotografiaAnterior)) {
                    error_log("No se pudo eliminar la fotografía anterior: " . $rutaFotografiaAnterior);
                }
            }
        }
    } elseif (isset($_POST['existing_fotografia']) && !empty($_POST['existing_fotografia']) && (!isset($_POST['removed_fotografia']) || $_POST['removed_fotografia'] != '1')) {
        // Mantener la fotografía existente
        $rutaExistente = sanitizar($_POST['existing_fotografia']);
        $prefijo = '/sigesmancor/';
        if (strpos($rutaExistente, $prefijo) === 0) {
            $rutaExistente = substr($rutaExistente, strlen($prefijo));
        }
        $datos['fotografia'] = $rutaExistente;
    } elseif (isset($_POST['removed_fotografia']) && $_POST['removed_fotografia'] == '1') {
        // Si se marcó para eliminar la fotografía
        $datos['fotografia'] = null;
        if (isset($_POST['existing_fotografia']) && !empty($_POST['existing_fotografia'])) {
            $rutaExistente = sanitizar($_POST['existing_fotografia']);
            $prefijo = '/sigesmancor/';
            if (strpos($rutaExistente, $prefijo) === 0) {
                $rutaExistente = substr($rutaExistente, strlen($prefijo));
            }
            $rutaFotografia = __DIR__ . '/../../' . $rutaExistente;
            if (file_exists($rutaFotografia)) {
                if (!unlink($rutaFotografia)) {
                    error_log("No se pudo eliminar la fotografía existente: " . $rutaFotografia);
                }
            }
        }
    }

    // Actualizar en la base de datos
    $conexion->update('usuarios', $datos, 'id = ?', [$usuarioId]);

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => 'Perfil actualizado correctamente'
    ];

    // Incluir nueva fotografía en la respuesta si se cambió
    if (isset($datos['fotografia'])) {
        $response['fotografia'] = $datos['fotografia'] ? getAssetUrl($datos['fotografia']) : getAssetUrl('assets/img/administracion/usuarios/default.png');
    }

    // Enviar respuesta
    echo json_encode($response);
} catch (Exception $e) {
    // Eliminar fotografía subida si hubo error
    if (isset($fotografiaGuardada) && $fotografiaGuardada && file_exists('../../' . $fotografiaGuardada)) {
        unlink('../../' . $fotografiaGuardada);
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
}
