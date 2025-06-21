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
if (!tienePermiso("mantenimientos.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver los detalles del mantenimiento"]);
    exit;
}

// Verificar que se proporcionaron los parámetros necesarios
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "ID del historial no proporcionado"]);
    exit;
}

$historial_id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Obtener datos del historial de mantenimiento
    $sql = "
        SELECT 
            hm.*,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.codigo
                ELSE c.codigo
            END as codigo_item,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.nombre
                ELSE c.nombre
            END as nombre_item,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN IF(e.tipo_orometro = 'kilometros', 'km', 'hrs')
                ELSE IF(c.tipo_orometro = 'kilometros', 'km', 'hrs')
            END as unidad_orometro,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.imagen
                ELSE c.imagen
            END as item_imagen
        FROM historial_mantenimiento hm
        LEFT JOIN equipos e ON hm.equipo_id = e.id
        LEFT JOIN componentes c ON hm.componente_id = c.id
        WHERE hm.id = ?
    ";

    $historial = $conexion->selectOne($sql, [$historial_id]);

    if (!$historial) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Registro de historial no encontrado"]);
        exit;
    }

    // Determinar imagen
    $imagenUrl = "";
    if (!empty($historial["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $historial["imagen"])) {
        $imagenUrl = getAssetUrl($historial["imagen"]);
    } elseif (!empty($historial["item_imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $historial["item_imagen"])) {
        $imagenUrl = getAssetUrl($historial["item_imagen"]);
    } else {
        $defaultImage = $historial["tipo_mantenimiento"] === "correctivo"
            ? "assets/img/mantenimiento/correctivo/default.png"
            : "assets/img/mantenimiento/preventivo/default.png";
        $imagenUrl = getAssetUrl($defaultImage);
    }

    // Obtener fecha del problema original (si existe)
    $fecha_problema = null;
    if ($historial["tipo_mantenimiento"] === "correctivo") {
        $problema = $conexion->selectOne(
            "SELECT fecha_hora_problema FROM mantenimiento_correctivo WHERE id = ?",
            [$historial["mantenimiento_id"]]
        );
        $fecha_problema = $problema ? $problema["fecha_hora_problema"] : null;
    } else {
        $problema = $conexion->selectOne(
            "SELECT fecha_programada FROM mantenimiento_preventivo WHERE id = ?",
            [$historial["mantenimiento_id"]]
        );
        $fecha_problema = $problema ? $problema["fecha_programada"] : null;
    }

    // Preparar respuesta con todos los campos necesarios
    $response = [
        "success" => true,
        "data" => [
            "id" => $historial["id"],
            "mantenimiento_id" => $historial["mantenimiento_id"],
            "tipo_mantenimiento" => $historial["tipo_mantenimiento"],
            "tipo_item" => $historial["tipo_item"],
            "codigo_item" => $historial["codigo_item"] ?: "Sin código",
            "nombre_item" => $historial["nombre_item"] ?: "Sin nombre",
            "descripcion" => $historial["descripcion"],
            "fecha_problema" => $fecha_problema,
            "fecha_realizado" => $historial["fecha_realizado"],
            "orometro_realizado" => $historial["orometro_realizado"],
            "unidad_orometro" => $historial["unidad_orometro"] ?: "hrs",
            "observaciones" => $historial["observaciones"],
            "imagen" => $imagenUrl,
            "historial" => $historial
        ]
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del historial: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en historial/obtener.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
