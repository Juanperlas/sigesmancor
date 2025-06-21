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
if (!tienePermiso("mantenimientos.correctivo.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver mantenimientos correctivos"]);
    exit;
}

// Obtener parámetros
$itemId = isset($_GET["item_id"]) ? intval($_GET["item_id"]) : 0;
$tipoItem = isset($_GET["tipo_item"]) ? sanitizar($_GET["tipo_item"]) : "";

if (empty($itemId) || empty($tipoItem)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Parámetros requeridos no proporcionados"]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Construir consulta según el tipo de ítem
    if ($tipoItem === "equipo") {
        $sql = "
            SELECT 
                mc.fecha_hora_problema as fecha,
                'correctivo' as tipo_mantenimiento,
                mc.descripcion_problema as descripcion,
                mc.orometro_actual as orometro,
                mc.observaciones,
                mc.estado,
                e.tipo_orometro
            FROM mantenimiento_correctivo mc
            LEFT JOIN equipos e ON mc.equipo_id = e.id
            WHERE mc.equipo_id = ? AND mc.estado = 'completado'
            ORDER BY mc.fecha_hora_problema DESC
            LIMIT 20
        ";
        $params = [$itemId];
    } else if ($tipoItem === "componente") {
        $sql = "
            SELECT 
                mc.fecha_hora_problema as fecha,
                'correctivo' as tipo_mantenimiento,
                mc.descripcion_problema as descripcion,
                mc.orometro_actual as orometro,
                mc.observaciones,
                mc.estado,
                c.tipo_orometro
            FROM mantenimiento_correctivo mc
            LEFT JOIN componentes c ON mc.componente_id = c.id
            WHERE mc.componente_id = ? AND mc.estado = 'completado'
            ORDER BY mc.fecha_hora_problema DESC
            LIMIT 20
        ";
        $params = [$itemId];
    } else {
        throw new Exception("Tipo de ítem no válido");
    }

    // Ejecutar consulta
    $historial = $conexion->select($sql, $params);

    // Formatear datos
    $historialFormateado = [];
    foreach ($historial as $item) {
        $unidad = $item["tipo_orometro"] === "horas" ? "hrs" : "km";

        $historialFormateado[] = [
            "fecha" => $item["fecha"],
            "tipo_mantenimiento" => ucfirst($item["tipo_mantenimiento"]),
            "descripcion" => $item["descripcion"] ?: "-",
            "orometro" => number_format(floatval($item["orometro"]), 2),
            "unidad" => $unidad,
            "observaciones" => $item["observaciones"] ?: "-"
        ];
    }

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => $historialFormateado
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener el historial: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en historial.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
