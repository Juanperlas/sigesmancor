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
if (!tienePermiso("mantenimientos.correctivo.editar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para completar mantenimientos correctivos"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Verificar que se proporcionaron los datos necesarios
if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["orometro_actual"]) || empty($_POST["orometro_actual"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$id = intval($_POST["id"]);
$orometroActual = floatval($_POST["orometro_actual"]);
$fechaRealizado = isset($_POST["fecha_realizado"]) ? sanitizar($_POST["fecha_realizado"]) : "";
$observaciones = isset($_POST["observaciones"]) ? sanitizar($_POST["observaciones"]) : "";

// Convertir fecha de DD/MM/YYYY a YYYY-MM-DD si es necesario
if (!empty($fechaRealizado)) {
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaRealizado)) {
        $partes = explode('/', $fechaRealizado);
        $fechaRealizado = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' ' . date('H:i:s');
    } else if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRealizado)) {
        $fechaRealizado = $fechaRealizado . ' ' . date('H:i:s');
    }
} else {
    $fechaRealizado = date("Y-m-d H:i:s");
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

    // Obtener datos del mantenimiento correctivo
    $sql = "
        SELECT 
            mc.*,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo
        FROM mantenimiento_correctivo mc
        WHERE mc.id = ?
    ";

    $mantenimiento = $conexion->selectOne($sql, [$id]);

    if (!$mantenimiento) {
        throw new Exception("Mantenimiento no encontrado");
    }

    if ($mantenimiento["estado"] !== "pendiente") {
        throw new Exception("Este mantenimiento ya ha sido completado");
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

        // Eliminar imagen anterior si existe
        if (!empty($mantenimiento["imagen"])) {
            $oldImagePath = __DIR__ . '/../../../' . $mantenimiento["imagen"];
            if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Generar nombre único para la imagen
        $newFileName = "mantenimiento_correctivo_{$id}_" . time() . "." . $fileExtension;
        $destPath = $imageBasePath . $newFileName;

        // Mover el archivo al directorio
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new Exception("Error al guardar la imagen");
        }

        // Guardar la ruta relativa
        $imagenPath = $imageUrlPrefix . $newFileName;
    }

    // Determinar si es equipo o componente
    $tipo = $mantenimiento["tipo"];
    $equipoId = $mantenimiento["equipo_id"];
    $componenteId = $mantenimiento["componente_id"];

    // 1. Actualizar el estado del mantenimiento a completado
    $datosMantenimiento = [
        "estado" => "completado",
        "fecha_realizado" => $fechaRealizado,
        "orometro_actual" => $orometroActual
    ];

    // Si hay observaciones, incluirlas
    if (!empty($observaciones)) {
        $datosMantenimiento["observaciones"] = $observaciones;
    }

    // Si se subió una imagen, incluirla
    if ($imagenPath) {
        $datosMantenimiento["imagen"] = $imagenPath;
    }

    $conexion->update("mantenimiento_correctivo", $datosMantenimiento, "id = ?", [$id]);

    // 2. Actualizar equipo o componente
    if ($tipo === "equipo") {
        // Obtener datos del equipo
        $equipo = $conexion->selectOne("SELECT * FROM equipos WHERE id = ?", [$equipoId]);

        if (!$equipo) {
            throw new Exception("Equipo no encontrado");
        }

        // Validar mantenimiento
        $mantenimientoIntervalo = floatval($equipo["mantenimiento"] ?? 0);
        if ($mantenimientoIntervalo <= 0) {
            $mantenimientoIntervalo = 1000; // Valor por defecto
        }

        // Calcular próximo orómetro
        $proximoOrometro = $orometroActual + $mantenimientoIntervalo;

        // Actualizar equipo
        $datosEquipo = [
            "anterior_orometro" => $equipo["orometro_actual"],
            "orometro_actual" => $orometroActual,
            "proximo_orometro" => $proximoOrometro,
            "estado" => "activo" // Volver a estado activo
        ];

        $conexion->update("equipos", $datosEquipo, "id = ?", [$equipoId]);
    } else {
        // Obtener datos del componente
        $componente = $conexion->selectOne("SELECT * FROM componentes WHERE id = ?", [$componenteId]);

        if (!$componente) {
            throw new Exception("Componente no encontrado");
        }

        // Validar mantenimiento
        $mantenimientoIntervalo = floatval($componente["mantenimiento"] ?? 0);
        if ($mantenimientoIntervalo <= 0) {
            $mantenimientoIntervalo = 1000; // Valor por defecto
        }

        // Calcular próximo orómetro
        $proximoOrometro = $orometroActual + $mantenimientoIntervalo;

        // Actualizar componente
        $datosComponente = [
            "anterior_orometro" => $componente["orometro_actual"],
            "orometro_actual" => $orometroActual,
            "proximo_orometro" => $proximoOrometro,
            "estado" => "activo" // Volver a estado activo
        ];

        $conexion->update("componentes", $datosComponente, "id = ?", [$componenteId]);
    }

    // 3. Registrar en historial de mantenimiento
    $datosHistorial = [
        "tipo_mantenimiento" => "correctivo",
        "mantenimiento_id" => $id,
        "equipo_id" => $equipoId,
        "componente_id" => $componenteId,
        "descripcion" => $mantenimiento["descripcion_problema"],
        "fecha_realizado" => $fechaRealizado,
        "orometro_realizado" => $orometroActual,
        "observaciones" => $observaciones ?: $mantenimiento["observaciones"],
        "imagen" => $imagenPath ?: $mantenimiento["imagen"]
    ];

    $conexion->insert("historial_mantenimiento", $datosHistorial);

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => "Mantenimiento correctivo completado correctamente"
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
        "message" => "Error al completar el mantenimiento correctivo: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en completar.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
