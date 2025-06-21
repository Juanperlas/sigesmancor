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

// Verificar que se proporcionaron los datos necesarios
if (!isset($_GET["tipo"]) || empty($_GET["tipo"]) || !isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$tipo = sanitizar($_GET["tipo"]);
$id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar tipo y obtener datos
    if ($tipo === "equipo") {
        $sql = "SELECT * FROM equipos WHERE id = ?";
        $item = $conexion->selectOne($sql, [$id]);

        if (!$item) {
            throw new Exception("Equipo no encontrado");
        }

        if ($item["estado"] === "vendido") {
            throw new Exception("No se puede crear un mantenimiento correctivo para un equipo vendido");
        }

        // Preparar respuesta
        $response = [
            "success" => true,
            "data" => [
                "id" => $item["id"],
                "codigo" => $item["codigo"],
                "nombre" => $item["nombre"],
                "marca" => $item["marca"],
                "modelo" => $item["modelo"],
                "tipo_orometro" => $item["tipo_orometro"],
                "orometro_actual" => $item["orometro_actual"],
                "estado" => $item["estado"],
                "imagen" => !empty($item["imagen"]) ? getAssetUrl($item["imagen"]) : getAssetUrl("assets/img/equipos/equipos/default.png"),
                "unidad" => $item["tipo_orometro"] === "horas" ? "hrs" : "km"
            ]
        ];
    } else if ($tipo === "componente") {
        $sql = "
            SELECT c.*, e.nombre as equipo_nombre, e.codigo as equipo_codigo 
            FROM componentes c
            LEFT JOIN equipos e ON c.equipo_id = e.id
            WHERE c.id = ?
        ";
        $item = $conexion->selectOne($sql, [$id]);

        if (!$item) {
            throw new Exception("Componente no encontrado");
        }

        if ($item["estado"] === "vendido") {
            throw new Exception("No se puede crear un mantenimiento correctivo para un componente vendido");
        }

        // Preparar respuesta
        $response = [
            "success" => true,
            "data" => [
                "id" => $item["id"],
                "codigo" => $item["codigo"],
                "nombre" => $item["nombre"],
                "marca" => $item["marca"],
                "modelo" => $item["modelo"],
                "tipo_orometro" => $item["tipo_orometro"],
                "orometro_actual" => $item["orometro_actual"],
                "estado" => $item["estado"],
                "equipo_nombre" => $item["equipo_nombre"],
                "equipo_codigo" => $item["equipo_codigo"],
                "imagen" => !empty($item["imagen"]) ? getAssetUrl($item["imagen"]) : getAssetUrl("assets/img/equipos/componentes/default.png"),
                "unidad" => $item["tipo_orometro"] === "horas" ? "hrs" : "km"
            ]
        ];
    } else {
        throw new Exception("Tipo de ítem no válido");
    }
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en verificar-equipo.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;