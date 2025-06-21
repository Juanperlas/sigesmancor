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
if (!tienePermiso("mantenimientos.preventivo.editar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para completar mantenimientos"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Verificar que se proporcionaron los datos necesarios
if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["orometro_actual"]) || $_POST["orometro_actual"] === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$id = intval($_POST["id"]);
$orometroActual = floatval($_POST["orometro_actual"]);
$observaciones = isset($_POST["observaciones"]) ? sanitizar($_POST["observaciones"]) : "";
$fechaRealizado = date("Y-m-d H:i:s"); // Use current timestamp

// Directorio para guardar imágenes - MODIFICADO PARA USAR __DIR__
$imageBasePath = __DIR__ . '/../../../assets/img/mantenimiento/preventivo/';
$imageUrlPrefix = 'assets/img/mantenimiento/preventivo/';
$imagenPath = null;

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    // Obtener datos del mantenimiento preventivo
    $sql = "
        SELECT 
            mp.*,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo
        FROM mantenimiento_preventivo mp
        WHERE mp.id = ?
    ";

    $mantenimiento = $conexion->selectOne($sql, [$id]);

    if (!$mantenimiento) {
        throw new Exception("Mantenimiento no encontrado");
    }

    if ($mantenimiento["estado"] !== "pendiente") {
        throw new Exception("Este mantenimiento ya ha sido completado o cancelado");
    }

    // Debug: Registrar información sobre FILES
    error_log("FILES: " . print_r($_FILES, true));

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
        $newFileName = "mantenimiento_{$id}_" . time() . "." . $fileExtension;
        $destPath = $imageBasePath . $newFileName;

        // Eliminar imagen anterior si existe
        if (!empty($mantenimiento["imagen"])) {
            $oldImagePath = __DIR__ . '/../../../' . $mantenimiento["imagen"];
            if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Mover el archivo al directorio
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new Exception("Error al guardar la imagen");
        }

        // Guardar la ruta relativa
        $imagenPath = $imageUrlPrefix . $newFileName;
        
        // Registrar en el log para depuración
        error_log("Imagen guardada en: " . $destPath);
        error_log("Ruta relativa guardada: " . $imagenPath);
    } else if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
        // Registrar error de subida para depuración
        error_log("Error al subir imagen: " . $_FILES["imagen"]["error"]);
        error_log("Detalles de FILES: " . print_r($_FILES, true));
    } else {
        error_log("No se recibió ninguna imagen o el tamaño es 0");
    }

    // Determinar si es equipo o componente
    $tipo = $mantenimiento["tipo"];
    $equipoId = $mantenimiento["equipo_id"];
    $componenteId = $mantenimiento["componente_id"];

    // 1. Actualizar el estado del mantenimiento a completado
    $datosMantenimiento = [
        "estado" => "completado",
        "fecha_realizado" => $fechaRealizado,
        "observaciones" => $observaciones
    ];

    // Si se subió una imagen, incluirla en la actualización
    if ($imagenPath) {
        $datosMantenimiento["imagen"] = $imagenPath;
    }

    $conexion->update("mantenimiento_preventivo", $datosMantenimiento, "id = ?", [$id]);

    // 2. Actualizar orómetro en equipo o componente y crear nuevo mantenimiento
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

        // Actualizar orómetro del equipo
        $datosEquipo = [
            "anterior_orometro" => $equipo["orometro_actual"] ?? 0,
            "orometro_actual" => $orometroActual,
            "proximo_orometro" => $proximoOrometro
        ];

        $conexion->update("equipos", $datosEquipo, "id = ?", [$equipoId]);

        // 3. Generar nuevo mantenimiento preventivo
        $descripcion = "Mantenimiento preventivo programado para el equipo {$equipo["nombre"]} (Código: {$equipo["codigo"]})";
        $fechaProgramada = calcularFechaProgramada($orometroActual, $proximoOrometro, $equipo["limite"] ?? 8);

        $datosNuevoMantenimiento = [
            "equipo_id" => $equipoId,
            "descripcion_razon" => $descripcion,
            "fecha_programada" => $fechaProgramada,
            "orometro_programado" => $proximoOrometro,
            "imagen" => null,
            "estado" => "pendiente"
        ];

        $nuevoMantenimientoId = $conexion->insert("mantenimiento_preventivo", $datosNuevoMantenimiento);
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

        // Actualizar orómetro del componente
        $datosComponente = [
            "anterior_orometro" => $componente["orometro_actual"] ?? 0,
            "orometro_actual" => $orometroActual,
            "proximo_orometro" => $proximoOrometro
        ];

        $conexion->update("componentes", $datosComponente, "id = ?", [$componenteId]);

        // 3. Generar nuevo mantenimiento preventivo
        $descripcion = "Mantenimiento preventivo programado para el componente {$componente["nombre"]} (Código: {$componente["codigo"]})";
        $fechaProgramada = calcularFechaProgramada($orometroActual, $proximoOrometro, $componente["limite"] ?? 8);

        $datosNuevoMantenimiento = [
            "componente_id" => $componenteId,
            "descripcion_razon" => $descripcion,
            "fecha_programada" => $fechaProgramada,
            "orometro_programado" => $proximoOrometro,
            "imagen" => null,
            "estado" => "pendiente"
        ];

        $nuevoMantenimientoId = $conexion->insert("mantenimiento_preventivo", $datosNuevoMantenimiento);
    }

    // 4. Registrar en historial de mantenimiento
    $datosHistorial = [
        "tipo_mantenimiento" => "preventivo",
        "mantenimiento_id" => $id,
        "equipo_id" => $equipoId,
        "componente_id" => $componenteId,
        "descripcion" => $mantenimiento["descripcion_razon"],
        "fecha_realizado" => $fechaRealizado,
        "orometro_realizado" => $orometroActual,
        "observaciones" => $observaciones,
        "imagen" => $imagenPath
    ];

    $conexion->insert("historial_mantenimiento", $datosHistorial);

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => "Mantenimiento completado correctamente",
        "nuevo_mantenimiento_id" => $nuevoMantenimientoId,
        "imagen_path" => $imagenPath // Añadir la ruta de la imagen para depuración
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al completar el mantenimiento: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en completar.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;

/**
 * Calcula la fecha programada para el mantenimiento basada en el límite diario
 * @param float $orometroActual Orómetro actual
 * @param float $proximoOrometro Próximo orómetro
 * @param float $limiteDiario Límite diario (trabajo por día)
 * @return string Fecha programada en formato Y-m-d H:i:s
 */
function calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario)
{
    // Si no hay límite diario definido, usar un valor por defecto
    if (empty($limiteDiario) || $limiteDiario <= 0) {
        $limiteDiario = 8; // 8 horas o 8 km por día por defecto
    }

    // Calcular la diferencia de orómetros
    $diferenciaOrometros = $proximoOrometro - $orometroActual;

    // Evitar fechas negativas o inválidas
    if ($diferenciaOrometros < 0) {
        $diferenciaOrometros = 0; // Programar para hoy
    }

    // Calcular días necesarios (redondeando hacia arriba)
    $diasNecesarios = ceil($diferenciaOrometros / $limiteDiario);

    // Si son menos de 1 día, establecer mínimo 1 día
    $diasNecesarios = max(1, $diasNecesarios);

    // Calcular fecha programada
    return date("Y-m-d H:i:s", strtotime("+{$diasNecesarios} days"));
}