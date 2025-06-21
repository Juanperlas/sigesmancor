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

    // Obtener datos del mantenimiento preventivo
    $sql = "
        SELECT 
            mp.*,
            CASE 
                WHEN mp.equipo_id IS NOT NULL THEN 'equipo'
                ELSE 'componente'
            END as tipo,
            DATEDIFF(mp.fecha_programada, NOW()) as dias_restantes
        FROM mantenimiento_preventivo mp
        WHERE mp.id = ?
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
    $diasRestantesCalculados = null;
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
            if ($mantenimiento["estado"] === "completado") {
                // Si está completado, mostrar la imagen del mantenimiento o default
                if (!empty($mantenimiento["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $mantenimiento["imagen"])) {
                    $imagenUrl = getAssetUrl($mantenimiento["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/mantenimiento/preventivo/default.png");
                }
            } else {
                // Si está pendiente, mostrar la imagen del equipo o default
                if (!empty($equipo["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $equipo["imagen"])) {
                    $imagenUrl = getAssetUrl($equipo["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/equipos/equipos/default.png");
                }
            }

            // Calcular días restantes basados en el límite diario
            if (isset($equipo["limite"]) && floatval($equipo["limite"]) > 0) {
                $orometroActual = floatval($equipo["orometro_actual"]);
                $proximoOrometro = floatval($equipo["proximo_orometro"]);
                $limiteDiario = floatval($equipo["limite"]);

                $diferenciaOrometros = $proximoOrometro - $orometroActual;
                $diasRestantesCalculados = ceil($diferenciaOrometros / $limiteDiario);
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
            if ($mantenimiento["estado"] === "completado") {
                // Si está completado, mostrar la imagen del mantenimiento o default
                if (!empty($mantenimiento["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $mantenimiento["imagen"])) {
                    $imagenUrl = getAssetUrl($mantenimiento["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/mantenimiento/preventivo/default.png");
                }
            } else {
                // Si está pendiente, mostrar la imagen del componente o default
                if (!empty($componente["imagen"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/sigesmancor/" . $componente["imagen"])) {
                    $imagenUrl = getAssetUrl($componente["imagen"]);
                } else {
                    $imagenUrl = getAssetUrl("assets/img/equipos/componentes/default.png");
                }
            }

            // Calcular días restantes basados en el límite diario
            if (isset($componente["limite"]) && floatval($componente["limite"]) > 0) {
                $orometroActual = floatval($componente["orometro_actual"]);
                $proximoOrometro = floatval($componente["proximo_orometro"]);
                $limiteDiario = floatval($componente["limite"]);

                $diferenciaOrometros = $proximoOrometro - $orometroActual;
                $diasRestantesCalculados = ceil($diferenciaOrometros / $limiteDiario);
            }
        } catch (Exception $e) {
            error_log("Error al obtener componente: " . $e->getMessage());
            $componente = [];
            $unidadOrometro = "hrs";
            $imagenUrl = getAssetUrl("assets/img/equipos/componentes/default.png");
        }
    }

    // Usar días restantes calculados si están disponibles, de lo contrario usar los de la base de datos
    $diasRestantes = $diasRestantesCalculados !== null ? $diasRestantesCalculados : $mantenimiento["dias_restantes"];
    $esFechaFutura = $diasRestantes >= 0;

    // Calcular progreso del orómetro
    $orometroActual = 0;
    $proximoOrometro = 0;
    $anteriorOrometro = 0;

    if ($tipo === "equipo" && $equipo) {
        $orometroActual = floatval($equipo["orometro_actual"]);
        $proximoOrometro = floatval($equipo["proximo_orometro"]);
        $anteriorOrometro = floatval($equipo["anterior_orometro"]);
    } else if ($tipo === "componente" && $componente) {
        $orometroActual = floatval($componente["orometro_actual"]);
        $proximoOrometro = floatval($componente["proximo_orometro"]);
        $anteriorOrometro = floatval($componente["anterior_orometro"]);
    }

    // Calcular progreso
    $progreso = 0;
    $totalRecorrido = $proximoOrometro - $anteriorOrometro;
    $avanceActual = $orometroActual - $anteriorOrometro;

    // Evitar división por cero
    if ($totalRecorrido > 0) {
        $progreso = min(($avanceActual / $totalRecorrido) * 100, 100);
    }

    // Formatear fecha
    $fechaFormateada = date("d/m/Y", strtotime($mantenimiento["fecha_programada"]));

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => [
            "mantenimiento" => $mantenimiento,
            "tipo" => $tipo,
            "equipo" => $equipo,
            "componente" => $componente,
            "unidad_orometro" => $unidadOrometro,
            "dias_restantes" => $diasRestantes,
            "es_fecha_futura" => $esFechaFutura,
            "progreso" => $progreso,
            "fecha_formateada" => $fechaFormateada,
            "anterior_orometro" => $anteriorOrometro,
            "orometro_actual" => $orometroActual,
            "proximo_orometro" => $proximoOrometro,
            "imagen" => $imagenUrl
        ]
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del mantenimiento: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
