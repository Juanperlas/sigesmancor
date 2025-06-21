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
if ($id && !tienePermiso('componentes.editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar componentes']);
    exit;
} elseif (!$id && !tienePermiso('componentes.crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear componentes']);
    exit;
}

// Validar campos requeridos
$camposRequeridos = ['codigo', 'nombre', 'equipo_id', 'tipo_orometro', 'estado'];
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
    'codigo' => sanitizar($_POST['codigo']),
    'nombre' => sanitizar($_POST['nombre']),
    'equipo_id' => intval($_POST['equipo_id']),
    'marca' => isset($_POST['marca']) ? sanitizar($_POST['marca']) : null,
    'modelo' => isset($_POST['modelo']) ? sanitizar($_POST['modelo']) : null,
    'numero_serie' => isset($_POST['numero_serie']) ? sanitizar($_POST['numero_serie']) : null,
    'estado' => sanitizar($_POST['estado']),
    'tipo_orometro' => sanitizar($_POST['tipo_orometro']),
    'anterior_orometro' => isset($_POST['anterior_orometro']) && is_numeric($_POST['anterior_orometro']) ? floatval($_POST['anterior_orometro']) : 0,
    'orometro_actual' => isset($_POST['orometro_actual']) && is_numeric($_POST['orometro_actual']) ? floatval($_POST['orometro_actual']) : 0,
    'limite' => isset($_POST['limite']) && is_numeric($_POST['limite']) ? floatval($_POST['limite']) : null,
    'notificacion' => isset($_POST['notificacion']) && is_numeric($_POST['notificacion']) ? floatval($_POST['notificacion']) : null,
    'mantenimiento' => isset($_POST['mantenimiento']) && is_numeric($_POST['mantenimiento']) ? floatval($_POST['mantenimiento']) : null,
    'observaciones' => isset($_POST['observaciones']) ? sanitizar($_POST['observaciones']) : null
];

// Validar tipo_orometro
$validTiposOrometro = ['horas', 'kilometros'];
if (!in_array($datos['tipo_orometro'], $validTiposOrometro)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de orómetro inválido']);
    exit;
}

// Validar campos numéricos
if (isset($datos['limite']) && ($datos['limite'] < 0 || $datos['limite'] > 1000000)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El límite debe estar entre 0 y 1,000,000']);
    exit;
}
if (isset($datos['notificacion']) && ($datos['notificacion'] < 0 || $datos['notificacion'] > 1000)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La notificación debe estar entre 0 y 1,000']);
    exit;
}
if (isset($datos['mantenimiento']) && $datos['mantenimiento'] < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El mantenimiento debe ser mayor o igual a 0']);
    exit;
}

// Calcular proximo_orometro solo si no se proporcionó un valor original
if (isset($_POST['proximo_orometro_original']) && !empty($_POST['proximo_orometro_original'])) {
    // Mantener el valor original de proximo_orometro
    $datos['proximo_orometro'] = floatval($_POST['proximo_orometro_original']);
} else if (isset($datos['mantenimiento']) && $datos['mantenimiento'] > 0) {
    // Calcular nuevo valor de proximo_orometro usando la fórmula correcta
    $datos['proximo_orometro'] = $datos['anterior_orometro'] + $datos['mantenimiento'];
} else {
    $datos['proximo_orometro'] = null;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar si el código ya existe (excepto para el mismo componente en caso de edición)
    $sqlVerificarCodigo = "SELECT id FROM componentes WHERE codigo = ? AND id != ?";
    $componenteExistente = $conexion->selectOne($sqlVerificarCodigo, [$datos['codigo'], $id ?: 0]);

    if ($componenteExistente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El código ya está en uso por otro componente']);
        exit;
    }

    // Verificar si el equipo existe
    $equipoExiste = $conexion->selectOne("SELECT id FROM equipos WHERE id = ?", [$datos['equipo_id']]);
    if (!$equipoExiste) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El equipo seleccionado no existe']);
        exit;
    }

    // Procesar imagen si se ha subido
    $imagenGuardada = null;
    $imageBasePath = __DIR__ . '/../../../assets/img/equipos/componentes/';
    $imageUrlPrefix = 'assets/img/equipos/componentes/';

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
        $nombreArchivo = 'componente_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaCompleta = $imageBasePath . $nombreArchivo;

        // Si estamos editando, obtener la imagen anterior
        $imagenAnterior = null;
        if ($id) {
            $sqlImagenActual = "SELECT imagen FROM componentes WHERE id = ?";
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
        // Actualizar componente existente
        if (!isset($datos['imagen']) && !isset($_POST['removed_imagen'])) {
            // Si no se ha subido una nueva imagen y no se ha marcado para eliminar, mantener la actual
            unset($datos['imagen']);
        } elseif (isset($datos['imagen']) && $imagenGuardada) {
            // Si se ha subido una nueva imagen, eliminar la anterior
            $componenteAnterior = $conexion->selectOne("SELECT imagen FROM componentes WHERE id = ?", [$id]);
            if ($componenteAnterior && !empty($componenteAnterior['imagen']) && file_exists('../../../' . $componenteAnterior['imagen'])) {
                unlink('../../../' . $componenteAnterior['imagen']);
            }
        }

        // Actualizar en la base de datos
        $conexion->update('componentes', $datos, 'id = ?', [$id]);
        $mensaje = 'Componente actualizado correctamente';
    } else {
        // Crear nuevo componente
        $id = $conexion->insert('componentes', $datos);
        $mensaje = 'Componente creado correctamente';
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
    echo json_encode(['success' => false, 'message' => 'Error al guardar el componente: ' . $e->getMessage()]);
}
