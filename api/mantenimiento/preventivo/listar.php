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
if (!tienePermiso("mantenimientos.preventivo.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver mantenimientos preventivos"]);
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
    "mp.fecha_programada",
    "tipo_item",
    "codigo_item",
    "orometro_actual_valor",
    "proximo_orometro_valor",
    "mp.estado"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por estado
if (isset($_POST["estado"]) && $_POST["estado"] !== "") {
    $filtros[] = "mp.estado = ?";
    $params[] = $_POST["estado"];
}

// Filtro por tipo (equipo o componente)
if (isset($_POST["tipo"]) && $_POST["tipo"] !== "") {
    if ($_POST["tipo"] === "equipo") {
        $filtros[] = "mp.equipo_id IS NOT NULL";
    } else if ($_POST["tipo"] === "componente") {
        $filtros[] = "mp.componente_id IS NOT NULL";
    }
}

// Filtro por fecha desde
if (isset($_POST["fecha_desde"]) && $_POST["fecha_desde"] !== "") {
    $filtros[] = "DATE(mp.fecha_programada) >= ?";
    $params[] = $_POST["fecha_desde"];
}

// Filtro por fecha hasta
if (isset($_POST["fecha_hasta"]) && $_POST["fecha_hasta"] !== "") {
    $filtros[] = "DATE(mp.fecha_programada) <= ?";
    $params[] = $_POST["fecha_hasta"];
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(
        e.codigo LIKE ? OR 
        e.nombre LIKE ? OR 
        c.codigo LIKE ? OR 
        c.nombre LIKE ? OR 
        mp.descripcion_razon LIKE ?
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
    $sqlTotal = "SELECT COUNT(*) as total FROM mantenimiento_preventivo";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "
        SELECT COUNT(*) as total 
        FROM mantenimiento_preventivo mp
        LEFT JOIN equipos e ON mp.equipo_id = e.id
        LEFT JOIN componentes c ON mp.componente_id = c.id
        $where
    ";

    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "
        SELECT 
            mp.id,
            mp.equipo_id,
            mp.componente_id,
            mp.descripcion_razon as descripcion,
            mp.fecha_programada,
            mp.orometro_programado,
            mp.estado,
            mp.fecha_realizado,
            mp.observaciones,
            mp.imagen,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo_item,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.codigo
                ELSE c.codigo
            END as codigo_item,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.orometro_actual
                ELSE c.orometro_actual
            END as orometro_actual_valor,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.anterior_orometro
                ELSE c.anterior_orometro
            END as anterior_orometro_valor,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.proximo_orometro
                ELSE c.proximo_orometro
            END as proximo_orometro_valor,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.tipo_orometro
                ELSE c.tipo_orometro
            END as tipo_orometro,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.limite
                ELSE c.limite
            END as limite_valor,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN e.imagen
                ELSE c.imagen
            END as item_imagen
        FROM mantenimiento_preventivo mp
        LEFT JOIN equipos e ON mp.equipo_id = e.id
        LEFT JOIN componentes c ON mp.componente_id = c.id
        $where
    ";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY mp.fecha_programada ASC";
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";

    // Ejecutar consulta
    $mantenimientos = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($mantenimientos as $row) {
        // Calcular días restantes basados en el límite diario
        $diasRestantes = null;
        if (!empty($row["limite_valor"]) && $row["limite_valor"] > 0) {
            $orometroActual = floatval($row["orometro_actual_valor"]);
            $proximoOrometro = floatval($row["proximo_orometro_valor"]);
            $limiteDiario = floatval($row["limite_valor"]);

            $diferenciaOrometros = $proximoOrometro - $orometroActual;
            $diasRestantes = ceil($diferenciaOrometros / $limiteDiario);
        } else {
            // Fallback: Calcular días restantes desde la fecha programada
            $diasRestantes = floor((strtotime($row["fecha_programada"]) - time()) / (60 * 60 * 24));
        }

        $esFechaFutura = $diasRestantes >= 0;

        if ($esFechaFutura) {
            if ($diasRestantes <= 3) {
                $diasRestantesClase = "text-danger fw-bold";
            } else if ($diasRestantes <= 7) {
                $diasRestantesClase = "text-warning fw-bold";
            } else {
                $diasRestantesClase = "text-success";
            }
        } else {
            $diasRestantesClase = "text-danger fw-bold";
        }

        // Calcular progreso del orómetro
        $orometroActual = floatval($row["orometro_actual_valor"]);
        $proximoOrometro = floatval($row["proximo_orometro_valor"]);
        $anteriorOrometro = floatval($row["anterior_orometro_valor"]);

        // Calcular progreso
        $totalRecorrido = $proximoOrometro - $anteriorOrometro;
        $avanceActual = $orometroActual - $anteriorOrometro;

        // Evitar división por cero
        if ($totalRecorrido > 0) {
            $progreso = min(($avanceActual / $totalRecorrido) * 100, 100);
        } else {
            $progreso = 0;
        }

        // Determinar clase para la barra de progreso
        if ($progreso >= 90) {
            $progresoClase = "bg-danger";
        } else if ($progreso >= 75) {
            $progresoClase = "bg-warning";
        } else {
            $progresoClase = "bg-success";
        }

        // Determinar clase para el estado
        switch ($row["estado"]) {
            case "pendiente":
                $estadoClase = "estado-pendiente";
                break;
            case "completado":
                $estadoClase = "estado-completado";
                break;
            case "cancelado":
                $estadoClase = "estado-cancelado";
                break;
            default:
                $estadoClase = "";
        }

        // Formatear fecha
        $fechaFormateada = date("d/m/Y", strtotime($row["fecha_programada"]));

        // Formatear orómetro
        $unidad = $row["tipo_orometro"] === "horas" ? "hrs" : "km";
        $orometroActualFormateado = number_format($orometroActual, 2) . " " . $unidad;
        $proximoOrometroFormateado = number_format($proximoOrometro, 2) . " " . $unidad;

        /* Preparar imagen
        $imagenUrl = "";
        if ($row["estado"] === "completado" && !empty($row["imagen"])) {
            $imagenUrl = getAssetUrl($row["imagen"]);
        } elseif (!empty($row["item_imagen"])) {
            $imagenUrl = getAssetUrl($row["item_imagen"]);
        } else {
            $imagenUrl = getAssetUrl(
                $row["tipo_item"] === "equipo"
                    ? "assets/img/equipos/equipos/default.png"
                    : "assets/img/equipos/componentes/default.png"
            );
        }*/

        $imagenUrl = "";
        if (!empty($row["item_imagen"])) {
            $imagenUrl = getAssetUrl($row["item_imagen"]);
        } else {
            $imagenUrl = getAssetUrl(
                $row["tipo_item"] === "equipo"
                    ? "assets/img/equipos/equipos/default.png"
                    : "assets/img/equipos/componentes/default.png"
            );
        }

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"],
            "equipo_id" => $row["equipo_id"],
            "componente_id" => $row["componente_id"],
            "tipo_item" => $row["tipo_item"],
            "codigo_item" => $row["codigo_item"],
            "descripcion" => $row["descripcion"],
            "fecha_programada" => $row["fecha_programada"],
            "fecha_formateada" => $fechaFormateada,
            "orometro_actual" => $orometroActualFormateado,
            "orometro_actual_valor" => $orometroActual,
            "proximo_orometro" => $proximoOrometroFormateado,
            "proximo_orometro_valor" => $proximoOrometro,
            "estado" => $row["estado"],
            "estado_clase" => $estadoClase,
            "dias_restantes" => $diasRestantes,
            "dias_restantes_clase" => $diasRestantesClase,
            "es_fecha_futura" => $esFechaFutura,
            "progreso" => $progreso,
            "progreso_clase" => $progresoClase,
            "anterior_orometro" => $anteriorOrometro,
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
    error_log("Error en listar.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
