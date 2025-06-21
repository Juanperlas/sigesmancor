<?php
// Incluir archivos necesarios
require_once "../../db/funciones.php";
require_once "../../db/conexion.php";

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
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver el historial de mantenimientos"]);
    exit;
}

// Verificar que se proporcionaron los datos necesarios
if (!isset($_GET["item_id"]) || empty($_GET["item_id"]) || !isset($_GET["tipo_item"]) || empty($_GET["tipo_item"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$itemId = intval($_GET["item_id"]);
$tipoItem = sanitizar($_GET["tipo_item"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Preparar consulta según el tipo de ítem
    if ($tipoItem === "equipo") {
        $sql = "
            SELECT 
                hm.tipo_mantenimiento,
                hm.descripcion,
                hm.fecha_realizado as fecha,
                hm.orometro_realizado as orometro,
                hm.observaciones,
                e.tipo_orometro
            FROM historial_mantenimiento hm
            LEFT JOIN equipos e ON hm.equipo_id = e.id
            WHERE hm.equipo_id = ?
            ORDER BY hm.fecha_realizado DESC
            LIMIT 20
        ";
        $params = [$itemId];
    } else if ($tipoItem === "componente") {
        $sql = "
            SELECT 
                hm.tipo_mantenimiento,
                hm.descripcion,
                hm.fecha_realizado as fecha,
                hm.orometro_realizado as orometro,
                hm.observaciones,
                c.tipo_orometro
            FROM historial_mantenimiento hm
            LEFT JOIN componentes c ON hm.componente_id = c.id
            WHERE hm.componente_id = ?
            ORDER BY hm.fecha_realizado DESC
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
            "tipo_mantenimiento" => $item["tipo_mantenimiento"],
            "descripcion" => $item["descripcion"] ?: "-",
            "fecha" => $item["fecha"],
            "orometro" => number_format(floatval($item["orometro"] ?: 0), 2),
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
    error_log("Error en historial.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
