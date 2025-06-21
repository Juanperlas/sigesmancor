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
if (!tienePermiso("mantenimientos.preventivo.acceder")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para acceder a este recurso"]);
    exit;
}

// Inicializar respuesta
$response = [
    "success" => false,
    "message" => "",
    "total_generados" => 0,
    "equipos_generados" => 0,
    "componentes_generados" => 0,
    "equipos_faltantes" => 0,
    "componentes_faltantes" => 0,
    "fechas_actualizadas" => 0
];

try {
    // Obtener conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Determinar si es una solicitud GET (verificación) o POST (generación)
    $esGeneracion = $_SERVER["REQUEST_METHOD"] === "POST";
    
    // Verificar si se está solicitando actualizar fechas (botón "verificar")
    $actualizarFechas = isset($_GET["actualizar_fechas"]) && $_GET["actualizar_fechas"] == 1;

    // Iniciar transacción si es generación o actualización de fechas
    if ($esGeneracion || $actualizarFechas) {
        $conn->beginTransaction();
    }

    // Si se solicita actualizar fechas, hacerlo
    if ($actualizarFechas) {
        $fechasActualizadas = actualizarFechasMantenimiento($conexion);
        
        // Confirmar transacción
        $conn->commit();
        
        // Preparar respuesta
        $response["success"] = true;
        $response["fechas_actualizadas"] = $fechasActualizadas;
        $response["message"] = "Se han actualizado las fechas de {$fechasActualizadas} mantenimientos preventivos";
    }
    // Si es generación, generar mantenimientos faltantes
    else if ($esGeneracion) {
        $equiposGenerados = verificarYGenerarMantenimientoEquipos($conexion, true);
        $componentesGenerados = verificarYGenerarMantenimientoComponentes($conexion, true);

        // Confirmar transacción
        $conn->commit();

        // Preparar respuesta
        $response["success"] = true;
        $response["total_generados"] = $equiposGenerados + $componentesGenerados;
        $response["equipos_generados"] = $equiposGenerados;
        $response["componentes_generados"] = $componentesGenerados;
        $response["message"] = "Generación de mantenimientos preventivos completada";
    } 
    // Si es verificación, solo contar faltantes
    else {
        // Solo verificar cuántos faltan
        $equiposFaltantes = verificarYGenerarMantenimientoEquipos($conexion, false);
        $componentesFaltantes = verificarYGenerarMantenimientoComponentes($conexion, false);

        // Preparar respuesta
        $response["success"] = true;
        $response["equipos_faltantes"] = $equiposFaltantes;
        $response["componentes_faltantes"] = $componentesFaltantes;
        $response["message"] = "Verificación de mantenimientos preventivos completada";
    }
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $response["success"] = false;
    $response["message"] = "Error al generar mantenimientos preventivos: " . $e->getMessage();

    // Registrar error en log
    error_log("Error en generar.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);

/**
 * Actualiza las fechas de mantenimientos preventivos pendientes
 * @param Conexion $conexion Conexión a la base de datos
 * @return int Número de fechas actualizadas
 */
function actualizarFechasMantenimiento($conexion) {
    $contador = 0;
    
    // Obtener todos los mantenimientos preventivos pendientes
    $sql = "SELECT mp.* FROM mantenimiento_preventivo mp WHERE mp.estado = 'pendiente'";
    $mantenimientos = $conexion->select($sql);
    
    foreach ($mantenimientos as $mantenimiento) {
        $fechaActualizada = false;
        
        // Determinar si es equipo o componente
        if ($mantenimiento["equipo_id"]) {
            // Es un equipo
            $equipo = $conexion->selectOne("SELECT * FROM equipos WHERE id = ?", [$mantenimiento["equipo_id"]]);
            
            if ($equipo && $equipo["limite"] > 0) {
                $orometroActual = floatval($equipo["orometro_actual"]);
                $proximoOrometro = floatval($mantenimiento["orometro_programado"]);
                $limiteDiario = floatval($equipo["limite"]);
                
                // Calcular nueva fecha programada
                $nuevaFecha = calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario);
                
                // Actualizar fecha en la base de datos
                $conexion->update(
                    "mantenimiento_preventivo",
                    ["fecha_programada" => $nuevaFecha],
                    "id = ?",
                    [$mantenimiento["id"]]
                );
                
                $fechaActualizada = true;
            }
        } else if ($mantenimiento["componente_id"]) {
            // Es un componente
            $componente = $conexion->selectOne("SELECT * FROM componentes WHERE id = ?", [$mantenimiento["componente_id"]]);
            
            if ($componente && $componente["limite"] > 0) {
                $orometroActual = floatval($componente["orometro_actual"]);
                $proximoOrometro = floatval($mantenimiento["orometro_programado"]);
                $limiteDiario = floatval($componente["limite"]);
                
                // Calcular nueva fecha programada
                $nuevaFecha = calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario);
                
                // Actualizar fecha en la base de datos
                $conexion->update(
                    "mantenimiento_preventivo",
                    ["fecha_programada" => $nuevaFecha],
                    "id = ?",
                    [$mantenimiento["id"]]
                );
                
                $fechaActualizada = true;
            }
        }
        
        if ($fechaActualizada) {
            $contador++;
        }
    }
    
    return $contador;
}

/**
 * Verifica y genera mantenimientos preventivos para equipos
 * @param Conexion $conexion Conexión a la base de datos
 * @param bool $generarRegistros Si es true, genera los registros; si es false, solo cuenta
 * @return int Número de mantenimientos generados o faltantes
 */
function verificarYGenerarMantenimientoEquipos($conexion, $generarRegistros = false)
{
    $contador = 0;

    // Obtener TODOS los equipos activos que deberían tener mantenimiento
    $sql = "SELECT e.* FROM equipos e 
            WHERE e.estado = 'activo' 
            AND e.mantenimiento > 0";

    $equipos = $conexion->select($sql);

    foreach ($equipos as $equipo) {
        // Verificar si ya existe un mantenimiento pendiente para este equipo
        $sqlVerificar = "SELECT COUNT(*) as total FROM mantenimiento_preventivo 
                         WHERE equipo_id = ? AND estado = 'pendiente'";
        $resultado = $conexion->selectOne($sqlVerificar, [$equipo["id"]]);

        // Si no existe mantenimiento pendiente, contar o generar
        if ($resultado["total"] == 0) {
            if ($generarRegistros) {
                // Calcular próximo orómetro
                $proximoOrometro = $equipo["orometro_actual"] + $equipo["mantenimiento"];
                $descripcion = "Mantenimiento preventivo programado para el equipo {$equipo["nombre"]} (Código: {$equipo["codigo"]})";

                // Calcular fecha programada basada en el límite diario y la diferencia de orómetros
                $fechaProgramada = calcularFechaProgramada($equipo["orometro_actual"], $proximoOrometro, $equipo["limite"]);

                $datos = [
                    "equipo_id" => $equipo["id"],
                    "descripcion_razon" => $descripcion,
                    "fecha_programada" => $fechaProgramada,
                    "orometro_programado" => $proximoOrometro
                ];

                $id = $conexion->insert("mantenimiento_preventivo", $datos);

                if ($id) {
                    $contador++;

                    // Actualizar próximo orómetro del equipo
                    $conexion->update(
                        "equipos",
                        ["proximo_orometro" => $proximoOrometro],
                        "id = ?",
                        [$equipo["id"]]
                    );
                }
            } else {
                // Solo contar
                $contador++;
            }
        }
    }

    return $contador;
}

/**
 * Verifica y genera mantenimientos preventivos para componentes
 * @param Conexion $conexion Conexión a la base de datos
 * @param bool $generarRegistros Si es true, genera los registros; si es false, solo cuenta
 * @return int Número de mantenimientos generados o faltantes
 */
function verificarYGenerarMantenimientoComponentes($conexion, $generarRegistros = false)
{
    $contador = 0;

    // Obtener TODOS los componentes activos que deberían tener mantenimiento
    $sql = "SELECT c.* FROM componentes c 
            WHERE c.estado = 'activo' 
            AND c.mantenimiento > 0";

    $componentes = $conexion->select($sql);

    foreach ($componentes as $componente) {
        // Verificar si ya existe un mantenimiento pendiente para este componente
        $sqlVerificar = "SELECT COUNT(*) as total FROM mantenimiento_preventivo 
                         WHERE componente_id = ? AND estado = 'pendiente'";
        $resultado = $conexion->selectOne($sqlVerificar, [$componente["id"]]);

        // Si no existe mantenimiento pendiente, contar o generar
        if ($resultado["total"] == 0) {
            if ($generarRegistros) {
                // Calcular próximo orómetro
                $proximoOrometro = $componente["orometro_actual"] + $componente["mantenimiento"];
                $descripcion = "Mantenimiento preventivo programado para el componente {$componente["nombre"]} (Código: {$componente["codigo"]})";

                // Calcular fecha programada basada en el límite diario y la diferencia de orómetros
                $fechaProgramada = calcularFechaProgramada($componente["orometro_actual"], $proximoOrometro, $componente["limite"]);

                $datos = [
                    "componente_id" => $componente["id"],
                    "descripcion_razon" => $descripcion,
                    "fecha_programada" => $fechaProgramada,
                    "orometro_programado" => $proximoOrometro
                ];

                $id = $conexion->insert("mantenimiento_preventivo", $datos);

                if ($id) {
                    $contador++;

                    // Actualizar próximo orómetro del componente
                    $conexion->update(
                        "componentes",
                        ["proximo_orometro" => $proximoOrometro],
                        "id = ?",
                        [$componente["id"]]
                    );
                }
            } else {
                // Solo contar
                $contador++;
            }
        }
    }

    return $contador;
}

/**
 * Calcula la fecha programada para el mantenimiento basada en el límite diario
 * @param float $orometroActual Orómetro actual
 * @param float $proximoOrometro Próximo orómetro
 * @param float $limiteDiario Límite diario (trabajo por día)
 * @return string Fecha programada en formato Y-m-d H:i:s
 */
function calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario)
{
    // Si no hay límite diario definido, usar un valor por defecto
    if (empty($limiteDiario) || $limiteDiario <= 0) {
        $limiteDiario = 8; // 8 horas o 8 km por día por defecto
    }

    // Calcular la diferencia de orómetros
    $diferenciaOrometros = $proximoOrometro - $orometroActual;

    // Calcular días necesarios (redondeando hacia arriba)
    $diasNecesarios = ceil($diferenciaOrometros / $limiteDiario);

    // Si son menos de 1 día, establecer mínimo 1 día
    $diasNecesarios = max(1, $diasNecesarios);

    // Calcular fecha programada
    return date("Y-m-d H:i:s", strtotime("+{$diasNecesarios} days"));
}