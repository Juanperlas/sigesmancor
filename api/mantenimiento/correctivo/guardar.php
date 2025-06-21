<?php
// Incluir archivos necesarios
require_once "../../../db/funciones.php";
require_once "../../../db/conexion.php";

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso no permitido"]);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

// Verificar permiso
if (!tienePermiso("mantenimientos.correctivo.crear")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para crear mantenimientos correctivos"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Verificar datos obligatorios
$tipoItem = isset($_POST["tipo_item"]) ? sanitizar($_POST["tipo_item"]) : "";
$itemId = isset($_POST["item_id"]) ? intval($_POST["item_id"]) : 0;
$orometroActual = isset($_POST["orometro_actual"]) ? floatval($_POST["orometro_actual"]) : 0;
$descripcionProblema = isset($_POST["descripcion_problema"]) ? sanitizar($_POST["descripcion_problema"]) : "";
$fechaHoraProblema = isset($_POST["fecha_hora_problema"]) ? sanitizar($_POST["fecha_hora_problema"]) : "";
$observaciones = isset($_POST["observaciones"]) ? sanitizar($_POST["observaciones"]) : "";

// Convertir fecha de DD/MM/YYYY a YYYY-MM-DD si es necesario
if (!empty($fechaHoraProblema)) {
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaHoraProblema)) {
        $partes = explode('/', $fechaHoraProblema);
        $fechaHoraProblema = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' ' . date('H:i:s');
    } else if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHoraProblema)) {
        $fechaHoraProblema = $fechaHoraProblema . ' ' . date('H:i:s');
    }
} else {
    $fechaHoraProblema = date("Y-m-d H:i:s");
}

// Validar datos obligatorios
if (empty($tipoItem) || empty($itemId) || $orometroActual <= 0 || empty($descripcionProblema)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos o inválidos",
        "errors" => [
            "tipo_item" => empty($tipoItem) ? "El tipo de ítem es obligatorio" : "",
            "item_id" => empty($itemId) ? "Debe seleccionar un equipo o componente" : "",
            "orometro_actual" => $orometroActual <= 0 ? "El orómetro actual debe ser mayor a 0" : "",
            "descripcion_problema" => empty($descripcionProblema) ? "La descripción del problema es obligatoria" : ""
        ]
    ]);
    exit;
}

// Directorio para guardar imágenes
$imageBasePath = __DIR__ . '/../../../assets/img/mantenimiento/correctivo/';
$imageUrlPrefix = 'assets/img/mantenimiento/correctivo/';
$imagenPath = null;

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    // Verificar que el equipo/componente exista y no esté vendido
    if ($tipoItem === "equipo") {
        $equipo = $conexion->selectOne("SELECT * FROM equipos WHERE id = ?", [$itemId]);

        if (!$equipo) {
            throw new Exception("El equipo seleccionado no existe");
        }

        if ($equipo["estado"] === "vendido") {
            throw new Exception("No se puede crear un mantenimiento correctivo para un equipo vendido");
        }

        // Actualizar estado del equipo a "mantenimiento"
        $conexion->update("equipos", ["estado" => "mantenimiento"], "id = ?", [$itemId]);
    } else if ($tipoItem === "componente") {
        $componente = $conexion->selectOne("SELECT * FROM componentes WHERE id = ?", [$itemId]);

        if (!$componente) {
            throw new Exception("El componente seleccionado no existe");
        }

        if ($componente["estado"] === "vendido") {
            throw new Exception("No se puede crear un mantenimiento correctivo para un componente vendido");
        }

        // Actualizar estado del componente a "mantenimiento"
        $conexion->update("componentes", ["estado" => "mantenimiento"], "id = ?", [$itemId]);
    } else {
        throw new Exception("Tipo de ítem no válido");
    }

    // Manejar subida de imagen (opcional)
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK && $_FILES["imagen"]["size"] > 0) {
        // Verificar si existe el directorio, si no, crearlo
        if (!file_exists($imageBasePath)) {
            if (!mkdir($imageBasePath, 0755, true)) {
                throw new Exception("Error al crear el directorio para imágenes");
            }
        }

        // Verificar permisos de escritura
        if (!is_writable($imageBasePath)) {
            throw new Exception("El directorio de imágenes no tiene permisos de escritura");
        }

        $fileTmpPath = $_FILES["imagen"]["tmp_name"];
        $fileName = $_FILES["imagen"]["name"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ["jpg", "jpeg", "png", "gif", "webp"];

        // Validar extensión
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Formato de imagen no permitido. Use JPG, PNG, GIF o WEBP.");
        }

        // Validar tamaño (2MB)
        if ($_FILES["imagen"]["size"] > 2 * 1024 * 1024) {
            throw new Exception("La imagen excede el tamaño máximo de 2MB");
        }

        // Generar nombre único para la imagen
        $newFileName = "mantenimiento_correctivo_" . time() . "." . $fileExtension;
        $destPath = $imageBasePath . $newFileName;

        // Mover el archivo al directorio
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new Exception("Error al guardar la imagen");
        }

        // Guardar la ruta relativa
        $imagenPath = $imageUrlPrefix . $newFileName;
    }

    // Preparar datos para insertar
    $datos = [
        "equipo_id" => $tipoItem === "equipo" ? $itemId : null,
        "componente_id" => $tipoItem === "componente" ? $itemId : null,
        "descripcion_problema" => $descripcionProblema,
        "orometro_actual" => $orometroActual,
        "fecha_hora_problema" => $fechaHoraProblema,
        "estado" => "pendiente",
        "observaciones" => $observaciones,
        "imagen" => $imagenPath
    ];

    // Insertar mantenimiento correctivo
    $id = $conexion->insert("mantenimiento_correctivo", $datos);

    if (!$id) {
        throw new Exception("Error al crear el mantenimiento correctivo");
    }

    // CORRECCIÓN: Actualizar el estado del mantenimiento preventivo SOLO para el ítem específico
    // En lugar de usar OR que afecta tanto equipos como componentes, usar condiciones específicas
    if ($tipoItem === "equipo") {
        // Solo actualizar mantenimientos preventivos del equipo específico
        $conexion->update(
            "mantenimiento_preventivo",
            ["estado" => "correctivo"],
            "equipo_id = ? AND componente_id IS NULL AND estado = 'pendiente'",
            [$itemId]
        );
    } else if ($tipoItem === "componente") {
        // Solo actualizar mantenimientos preventivos del componente específico
        $conexion->update(
            "mantenimiento_preventivo",
            ["estado" => "correctivo"],
            "componente_id = ? AND estado = 'pendiente'",
            [$itemId]
        );
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => "Mantenimiento correctivo creado correctamente",
        "id" => $id
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Eliminar imagen si se subió y hubo error
    if ($imagenPath && file_exists($imageBasePath . basename($imagenPath))) {
        unlink($imageBasePath . basename($imagenPath));
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al crear el mantenimiento correctivo: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
