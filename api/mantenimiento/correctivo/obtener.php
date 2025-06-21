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

// Verificar que se proporcionó un ID
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "ID de mantenimiento no proporcionado"]);
    exit;
}

$id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

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
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Mantenimiento no encontrado"]);
        exit;
    }

    // Determinar si es equipo o componente
    $tipo = $mantenimiento["tipo"];
    $equipo = null;
    $componente = null;
    $unidadOrometro = "";
    $imagenUrl = "";

    if ($tipo === "equipo") {
        try {
            // Obtener datos del equipo
            $sqlEquipo = "
                SELECT 
                    e.*,
                    ce.nombre as categoria_nombre
                FROM equipos e
                LEFT JOIN categorias_equipos ce ON e.categoria_id = ce.id
                WHERE e.id = ?
            ";

            $equipo = $conexion->selectOne($sqlEquipo, [$mantenimiento["equipo_id"]]);

            if (!$equipo) {
                throw new Exception("Equipo no encontrado");
            }

            $unidadOrometro = $equipo["tipo_orometro"] === "horas" ? "hrs" : "km";

            // Determinar imagen según estado
            if ($mantenimiento["estado"] === "completado" && !empty($mantenimiento["imagen"])) {
                // Si está completado y tiene imagen, mostrar la imagen del mantenimiento
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $mantenimiento["imagen"])) {
                    $imagenUrl = getAssetUrl($mantenimiento["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/mantenimiento/correctivo/default.png");
                }
            } else {
                // Si está pendiente o no tiene imagen, mostrar la imagen del equipo
                if (!empty($equipo["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $equipo["imagen"])) {
                    $imagenUrl = getAssetUrl($equipo["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/equipos/equipos/default.png");
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener equipo: " . $e->getMessage());
            $equipo = [];
            $unidadOrometro = "hrs";
            $imagenUrl = getAssetUrl("assets/img/equipos/equipos/default.png");
        }
    } else {
        try {
            // Obtener datos del componente
            $sqlComponente = "
                SELECT 
                    c.*,
                    e.nombre as equipo_nombre,
                    e.codigo as equipo_codigo
                FROM componentes c
                LEFT JOIN equipos e ON c.equipo_id = e.id
                WHERE c.id = ?
            ";

            $componente = $conexion->selectOne($sqlComponente, [$mantenimiento["componente_id"]]);

            if (!$componente) {
                throw new Exception("Componente no encontrado");
            }

            $unidadOrometro = $componente["tipo_orometro"] === "horas" ? "hrs" : "km";

            // Determinar imagen según estado
            if ($mantenimiento["estado"] === "completado" && !empty($mantenimiento["imagen"])) {
                // Si está completado y tiene imagen, mostrar la imagen del mantenimiento
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $mantenimiento["imagen"])) {
                    $imagenUrl = getAssetUrl($mantenimiento["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/mantenimiento/correctivo/default.png");
                }
            } else {
                // Si está pendiente o no tiene imagen, mostrar la imagen del componente
                if (!empty($componente["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $componente["imagen"])) {
                    $imagenUrl = getAssetUrl($componente["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/equipos/componentes/default.png");
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener componente: " . $e->getMessage());
            $componente = [];
            $unidadOrometro = "hrs";
            $imagenUrl = getAssetUrl("assets/img/equipos/componentes/default.png");
        }
    }

    // Formatear fechas
    $fechaProblemaFormateada = date("d/m/Y H:i", strtotime($mantenimiento["fecha_hora_problema"]));
    $fechaRealizadoFormateada = $mantenimiento["fecha_realizado"] ? date("d/m/Y H:i", strtotime($mantenimiento["fecha_realizado"])) : null;

    // Obtener historial de mantenimientos para este equipo/componente
    $historial = [];
    if ($tipo === "equipo" && $equipo) {
        $sqlHistorial = "
            SELECT 
                hm.tipo_mantenimiento,
                hm.descripcion,
                hm.fecha_realizado,
                hm.orometro_realizado,
                hm.observaciones
            FROM historial_mantenimiento hm
            WHERE hm.equipo_id = ?
            ORDER BY hm.fecha_realizado DESC
            LIMIT 10
        ";
        $historial = $conexion->select($sqlHistorial, [$mantenimiento["equipo_id"]]);
    } else if ($tipo === "componente" && $componente) {
        $sqlHistorial = "
            SELECT 
                hm.tipo_mantenimiento,
                hm.descripcion,
                hm.fecha_realizado,
                hm.orometro_realizado,
                hm.observaciones
            FROM historial_mantenimiento hm
            WHERE hm.componente_id = ?
            ORDER BY hm.fecha_realizado DESC
            LIMIT 10
        ";
        $historial = $conexion->select($sqlHistorial, [$mantenimiento["componente_id"]]);
    }

    // Formatear historial
    $historialFormateado = [];
    foreach ($historial as $item) {
        $historialFormateado[] = [
            "tipo" => ucfirst($item["tipo_mantenimiento"]),
            "descripcion" => $item["descripcion"],
            "fecha" => date("d/m/Y H:i", strtotime($item["fecha_realizado"])),
            "orometro" => number_format(floatval($item["orometro_realizado"]), 2) . " " . $unidadOrometro,
            "observaciones" => $item["observaciones"] ?: "-"
        ];
    }

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => [
            "mantenimiento" => $mantenimiento,
            "tipo" => $tipo,
            "equipo" => $equipo,
            "componente" => $componente,
            "unidad_orometro" => $unidadOrometro,
            "fecha_problema_formateada" => $fechaProblemaFormateada,
            "fecha_realizado_formateada" => $fechaRealizadoFormateada,
            "imagen" => $imagenUrl,
            "historial" => $historialFormateado
        ]
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del mantenimiento: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
