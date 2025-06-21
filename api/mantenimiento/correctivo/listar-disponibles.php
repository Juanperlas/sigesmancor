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

// Obtener tipo de ítem (equipo o componente)
$tipo = isset($_GET["tipo"]) ? sanitizar($_GET["tipo"]) : "equipo";
$busqueda = isset($_GET["q"]) ? sanitizar($_GET["q"]) : "";

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Preparar respuesta
    $items = [];

    // Obtener equipos o componentes disponibles (no vendidos)
    if ($tipo === "equipo") {
        $sql = "
            SELECT id, codigo, nombre, marca, modelo, tipo_orometro, orometro_actual, estado
            FROM equipos
            WHERE estado != 'vendido'
        ";

        // Agregar filtro de búsqueda si existe
        if (!empty($busqueda)) {
            $sql .= " AND (codigo LIKE ? OR nombre LIKE ? OR marca LIKE ? OR modelo LIKE ?)";
            $params = array_fill(0, 4, "%$busqueda%");
        } else {
            $params = [];
        }

        $sql .= " ORDER BY codigo ASC";
        $resultados = $conexion->select($sql, $params);

        foreach ($resultados as $item) {
            $items[] = [
                "id" => $item["id"],
                "codigo" => $item["codigo"],
                "nombre" => $item["nombre"],
                "marca" => $item["marca"],
                "modelo" => $item["modelo"],
                "tipo_orometro" => $item["tipo_orometro"],
                "orometro_actual" => floatval($item["orometro_actual"]), // Asegurar que sea numérico
                "estado" => $item["estado"],
                "texto" => "{$item["codigo"]} - {$item["nombre"]} ({$item["marca"]} {$item["modelo"]})"
            ];
        }
    } else if ($tipo === "componente") {
        $sql = "
            SELECT c.id, c.codigo, c.nombre, c.marca, c.modelo, c.tipo_orometro, c.orometro_actual, c.estado,
                   e.nombre as equipo_nombre, e.codigo as equipo_codigo
            FROM componentes c
            LEFT JOIN equipos e ON c.equipo_id = e.id
            WHERE c.estado != 'vendido'
        ";

        // Agregar filtro de búsqueda si existe
        if (!empty($busqueda)) {
            $sql .= " AND (c.codigo LIKE ? OR c.nombre LIKE ? OR c.marca LIKE ? OR c.modelo LIKE ?)";
            $params = array_fill(0, 4, "%$busqueda%");
        } else {
            $params = [];
        }

        $sql .= " ORDER BY c.codigo ASC";
        $resultados = $conexion->select($sql, $params);

        foreach ($resultados as $item) {
            $equipoInfo = !empty($item["equipo_codigo"]) ? " (Equipo: {$item["equipo_codigo"]})" : "";
            $items[] = [
                "id" => $item["id"],
                "codigo" => $item["codigo"],
                "nombre" => $item["nombre"],
                "marca" => $item["marca"],
                "modelo" => $item["modelo"],
                "tipo_orometro" => $item["tipo_orometro"],
                "orometro_actual" => floatval($item["orometro_actual"]), // Asegurar que sea numérico
                "estado" => $item["estado"],
                "equipo_nombre" => $item["equipo_nombre"],
                "equipo_codigo" => $item["equipo_codigo"],
                "texto" => "{$item["codigo"]} - {$item["nombre"]}{$equipoInfo}"
            ];
        }
    } else {
        throw new Exception("Tipo de ítem no válido");
    }

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => $items
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en listar-disponibles.php (correctivo): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
