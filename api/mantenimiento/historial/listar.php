<?php
// Incluir archivos necesariosAdd commentMore actions
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
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver el historial de mantenimientos"]);
    exit;
}

// Parámetros de DataTables
$draw = isset($_POST["draw"]) ? intval($_POST["draw"]) : 1;
$start = isset($_POST["start"]) ? intval($_POST["start"]) : 0;
$length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
$search = isset($_POST["search"]["value"]) ? $_POST["search"]["value"] : "";

// Columna y dirección de ordenamiento
$orderColumn = isset($_POST["order"][0]["column"]) ? intval($_POST["order"][0]["column"]) : 0;
$orderDir = isset($_POST["order"][0]["dir"]) ? $_POST["order"][0]["dir"] : "desc";

// Mapeo de columnas para ordenamiento
$columns = [
    0 => "hm.id", // ID del historial
    1 => "imagen", // Imagen (no ordenable)
    2 => "hm.tipo_mantenimiento", // Tipo
    3 => "codigo_item", // Código
    4 => "nombre_item", // Nombre
    5 => "hm.fecha_realizado", // Fecha realizado
    6 => "hm.orometro_realizado", // Orómetro
    7 => "acciones" // Acciones (no ordenable)
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por tipo de mantenimiento
if (isset($_POST["tipo_mantenimiento"]) && $_POST["tipo_mantenimiento"] !== "") {
    $filtros[] = "hm.tipo_mantenimiento = ?";
    $params[] = $_POST["tipo_mantenimiento"];
}

// Filtro por tipo de ítem
if (isset($_POST["tipo_item"]) && $_POST["tipo_item"] !== "") {
    if ($_POST["tipo_item"] === "equipo") {
        $filtros[] = "hm.equipo_id IS NOT NULL";
    } else if ($_POST["tipo_item"] === "componente") {
        $filtros[] = "hm.componente_id IS NOT NULL";
    }
}

// Filtro por fecha desde
if (isset($_POST["fecha_desde"]) && $_POST["fecha_desde"] !== "") {
    $filtros[] = "DATE(hm.fecha_realizado) >= ?";
    $params[] = $_POST["fecha_desde"];
}

// Filtro por fecha hasta
if (isset($_POST["fecha_hasta"]) && $_POST["fecha_hasta"] !== "") {
    $filtros[] = "DATE(hm.fecha_realizado) <= ?";
    $params[] = $_POST["fecha_hasta"];
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(
        CASE 
            WHEN hm.equipo_id IS NOT NULL THEN e.codigo
            ELSE c.codigo
        END LIKE ? OR 
        CASE 
            WHEN hm.equipo_id IS NOT NULL THEN e.nombre
            ELSE c.nombre
        END LIKE ? OR 
        hm.descripcion LIKE ?
    )";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta para contar registros totales
    $sqlTotal = "SELECT COUNT(*) as total FROM historial_mantenimiento";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "
        SELECT COUNT(*) as total 
        FROM historial_mantenimiento hm
        LEFT JOIN equipos e ON hm.equipo_id = e.id
        LEFT JOIN componentes c ON hm.componente_id = c.id
        $where
    ";

    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "
        SELECT 
            hm.id,
            hm.tipo_mantenimiento,
            hm.mantenimiento_id,
            hm.equipo_id,
            hm.componente_id,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.codigo
                ELSE c.codigo
            END as codigo_item,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.nombre
                ELSE c.nombre
            END as nombre_item,
            hm.descripcion,
            hm.fecha_realizado,
            hm.orometro_realizado,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN IF(e.tipo_orometro = 'kilometros', 'km', 'hrs')
                ELSE IF(c.tipo_orometro = 'kilometros', 'km', 'hrs')
            END as unidad_orometro,
            hm.observaciones,
            hm.imagen,
            CASE 
                WHEN hm.equipo_id IS NOT NULL THEN e.imagen
                ELSE c.imagen
            END as item_imagen
        FROM historial_mantenimiento hm
        LEFT JOIN equipos e ON hm.equipo_id = e.id
        LEFT JOIN componentes c ON hm.componente_id = c.id
        $where
    ";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn]) && $columns[$orderColumn] !== "imagen" && $columns[$orderColumn] !== "acciones") {
        $orderCol = $columns[$orderColumn];
        $sql .= " ORDER BY {$orderCol} {$orderDir}";
    } else {
        // Orden por defecto: ID descendente (más reciente primero)
        $sql .= " ORDER BY hm.id DESC";
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";

    // Ejecutar consulta
    $mantenimientos = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($mantenimientos as $row) {
        // Formatear fecha y hora
        $fechaHoraFormateada = date("d/m/Y H:i", strtotime($row["fecha_realizado"]));

        // Formatear orómetro
        $unidad = $row["unidad_orometro"] ?: "hrs";
        $orometroFormateado = number_format($row["orometro_realizado"] ?: 0, 2) . " " . $unidad;

        // Determinar imagen
        $imagenUrl = "";
        if (!empty($row["imagen"])) {
            // Si hay imagen del mantenimiento, usarla
            $imagenUrl = getAssetUrl($row["imagen"]);
        } elseif (!empty($row["item_imagen"])) {
            // Si hay imagen del equipo/componente, usarla
            $imagenUrl = getAssetUrl($row["item_imagen"]);
        } else {
            // Imagen por defecto según el tipo de mantenimiento
            $defaultImage = $row["tipo_mantenimiento"] === "correctivo"
                ? "assets/img/mantenimiento/correctivo/default.png"
                : "assets/img/mantenimiento/preventivo/default.png";
            $imagenUrl = getAssetUrl($defaultImage);
        }

        // Determinar tipo de ítem
        $tipoItem = $row["equipo_id"] ? "equipo" : "componente";

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"], // ID del historial
            "mantenimiento_id" => $row["mantenimiento_id"], // ID del mantenimiento original
            "tipo_mantenimiento" => $row["tipo_mantenimiento"],
            "tipo_item" => $tipoItem,
            "equipo_id" => $row["equipo_id"],
            "componente_id" => $row["componente_id"],
            "codigo_item" => $row["codigo_item"] ?: "Sin código",
            "nombre_item" => $row["nombre_item"] ?: "Sin nombre",
            "descripcion" => $row["descripcion"],
            "fecha_realizado" => $row["fecha_realizado"],
            "fecha_formateada" => $fechaHoraFormateada,
            "orometro_realizado" => $orometroFormateado,
            "orometro_realizado_valor" => $row["orometro_realizado"],
            "unidad_orometro" => $unidad,
            "observaciones" => $row["observaciones"],
            "imagen" => $imagenUrl
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
    error_log("Error en historial/listar.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;