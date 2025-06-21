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

// Parámetros de DataTables
$draw = isset($_POST["draw"]) ? intval($_POST["draw"]) : 1;
$start = isset($_POST["start"]) ? intval($_POST["start"]) : 0;
$length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
$search = isset($_POST["search"]["value"]) ? $_POST["search"]["value"] : "";

// Columna y dirección de ordenamiento
$orderColumn = isset($_POST["order"][0]["column"]) ? intval($_POST["order"][0]["column"]) : 0;
$orderDir = isset($_POST["order"][0]["dir"]) ? $_POST["order"][0]["dir"] : "asc";

// Mapeo de columnas para ordenamiento
$columns = [
    "mc.fecha_hora_problema",
    "tipo_item",
    "codigo_item",
    "orometro_actual",
    "mc.estado"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por estado
if (isset($_POST["estado"]) && $_POST["estado"] !== "") {
    $filtros[] = "mc.estado = ?";
    $params[] = $_POST["estado"];
}

// Filtro por tipo (equipo o componente)
if (isset($_POST["tipo"]) && $_POST["tipo"] !== "") {
    if ($_POST["tipo"] === "equipo") {
        $filtros[] = "mc.equipo_id IS NOT NULL";
    } else if ($_POST["tipo"] === "componente") {
        $filtros[] = "mc.componente_id IS NOT NULL";
    }
}

// Filtro por fecha desde
if (isset($_POST["fecha_desde"]) && $_POST["fecha_desde"] !== "") {
    $filtros[] = "DATE(mc.fecha_hora_problema) >= ?";
    $params[] = $_POST["fecha_desde"];
}

// Filtro por fecha hasta
if (isset($_POST["fecha_hasta"]) && $_POST["fecha_hasta"] !== "") {
    $filtros[] = "DATE(mc.fecha_hora_problema) <= ?";
    $params[] = $_POST["fecha_hasta"];
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(
        e.codigo LIKE ? OR 
        e.nombre LIKE ? OR 
        c.codigo LIKE ? OR 
        c.nombre LIKE ? OR 
        mc.descripcion_problema LIKE ?
    )";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total FROM mantenimiento_correctivo";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "
        SELECT COUNT(*) as total 
        FROM mantenimiento_correctivo mc
        LEFT JOIN equipos e ON mc.equipo_id = e.id
        LEFT JOIN componentes c ON mc.componente_id = c.id
        $where
    ";

    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "
        SELECT 
            mc.id,
            mc.equipo_id,
            mc.componente_id,
            mc.descripcion_problema,
            mc.fecha_hora_problema,
            mc.orometro_actual,
            mc.estado,
            mc.fecha_realizado,
            mc.observaciones,
            mc.imagen,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN e.codigo
                ELSE c.codigo
            END as codigo_item,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN e.nombre
                ELSE c.nombre
            END as nombre_item,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN e.tipo_orometro
                ELSE c.tipo_orometro
            END as tipo_orometro,
            CASE 
                WHEN mc.equipo_id IS NOT NULL THEN e.orometro_actual
                ELSE c.orometro_actual
            END as item_orometro_actual
        FROM mantenimiento_correctivo mc
        LEFT JOIN equipos e ON mc.equipo_id = e.id
        LEFT JOIN componentes c ON mc.componente_id = c.id
        $where
    ";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY mc.fecha_hora_problema DESC";
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";

    // Ejecutar consulta
    $mantenimientos = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($mantenimientos as $row) {
        // Formatear fecha
        $fechaFormateada = date("d/m/Y H:i", strtotime($row["fecha_hora_problema"]));

        // Formatear orómetro
        $unidad = $row["tipo_orometro"] === "horas" ? "hrs" : "km";
        $orometroActualFormateado = number_format(floatval($row["orometro_actual"]), 2) . " " . $unidad;

        // Preparar imagen - SIEMPRE mostrar la imagen del mantenimiento o default
        $imagenUrl = "";
        if (!empty($row["imagen"])) {
            // Si el mantenimiento tiene imagen, usarla
            $imagenUrl = getAssetUrl($row["imagen"]);
        } else {
            // Si no tiene imagen, usar el default del mantenimiento correctivo
            $imagenUrl = getAssetUrl("assets/img/mantenimiento/correctivo/default.png");
        }

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"],
            "equipo_id" => $row["equipo_id"],
            "componente_id" => $row["componente_id"],
            "tipo_item" => $row["tipo_item"],
            "codigo_item" => $row["codigo_item"],
            "nombre_item" => $row["nombre_item"],
            "descripcion" => $row["descripcion_problema"],
            "fecha_problema" => $row["fecha_hora_problema"],
            "fecha_formateada" => $fechaFormateada,
            "orometro_actual" => $orometroActualFormateado,
            "orometro_valor" => floatval($row["orometro_actual"]),
            "estado" => $row["estado"],
            "fecha_realizado" => $row["fecha_realizado"],
            "fecha_realizado_formateada" => $row["fecha_realizado"] ? date("d/m/Y H:i", strtotime($row["fecha_realizado"])) : "-",
            "observaciones" => $row["observaciones"],
            "imagen" => $imagenUrl,
            "tipo_orometro" => $row["tipo_orometro"],
            "item_orometro_actual" => floatval($row["item_orometro_actual"]) // Orómetro actual del equipo/componente
        ];
    }

    // Preparar respuesta para DataTables
    $response = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFiltered,
        "data" => $data
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Error al obtener los datos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en listar.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
