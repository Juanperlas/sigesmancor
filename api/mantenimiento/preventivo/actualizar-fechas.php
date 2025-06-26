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

try {
    // Obtener conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    // Actualizar fechas de mantenimientos pendientes
    $fechasActualizadas = actualizarFechasMantenimiento($conexion);

    // Registrar la actualización de fechas
    $registroExitoso = registrarActualizacionFechas($conexion);
    if (!$registroExitoso) {
        throw new Exception("Error al registrar la fecha de actualización");
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "fechas_actualizadas" => $fechasActualizadas,
        "message" => "Se han actualizado las fechas de {$fechasActualizadas} mantenimientos preventivos"
    ];
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $response = [
        "success" => false,
        "message" => "Error al actualizar fechas de mantenimientos preventivos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en actualizar-fechas.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);

/**
 * Actualiza las fechas de mantenimientos preventivos pendientes
 * CORREGIDO: Usar los valores que ya están en equipos/componentes
 * @param Conexion $conexion Conexión a la base de datos
 * @return int Número de fechas actualizadas
 */
function actualizarFechasMantenimiento($conexion)
{
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
                $proximoOrometro = floatval($equipo["proximo_orometro"]); // USAR EL QUE YA ESTÁ EN EQUIPOS
                $limiteDiario = floatval($equipo["limite"]);

                // Calcular nueva fecha programada
                $nuevaFecha = calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario);

                // CORREGIDO: Actualizar fecha y usar el proximo_orometro que ya está en equipos
                $conexion->update(
                    "mantenimiento_preventivo",
                    [
                        "fecha_programada" => $nuevaFecha,
                        "orometro_programado" => $proximoOrometro // Sincronizar con el de equipos
                    ],
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
                $proximoOrometro = floatval($componente["proximo_orometro"]); // USAR EL QUE YA ESTÁ EN COMPONENTES
                $limiteDiario = floatval($componente["limite"]);

                // Calcular nueva fecha programada
                $nuevaFecha = calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario);

                // CORREGIDO: Actualizar fecha y usar el proximo_orometro que ya está en componentes
                $conexion->update(
                    "mantenimiento_preventivo",
                    [
                        "fecha_programada" => $nuevaFecha,
                        "orometro_programado" => $proximoOrometro // Sincronizar con el de componentes
                    ],
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
 * Calcula la fecha programada para el mantenimiento basada en el límite diario
 * CORREGIDO: Lógica correcta según explicación del usuario
 * @param float $orometroActual Orómetro actual
 * @param float $proximoOrometro Próximo orómetro (cuando se debe hacer mantenimiento)
 * @param float $limiteDiario Límite diario (horas que trabaja por día)
 * @return string Fecha programada en formato Y-m-d H:i:s
 */
function calcularFechaProgramada($orometroActual, $proximoOrometro, $limiteDiario)
{
    // Si no hay límite diario definido, usar un valor por defecto
    if (empty($limiteDiario) || $limiteDiario <= 0) {
        $limiteDiario = 8; // 8 horas por día por defecto
    }

    // Calcular cuántas horas faltan para llegar al próximo orómetro
    $horasFaltantes = $proximoOrometro - $orometroActual;

    // Si ya se pasó el próximo orómetro (valor negativo), programar para hoy
    if ($horasFaltantes <= 0) {
        return date("Y-m-d H:i:s"); // Hoy mismo
    }

    // Calcular días necesarios (redondeando hacia arriba)
    $diasNecesarios = ceil($horasFaltantes / $limiteDiario);

    // Calcular fecha programada
    return date("Y-m-d H:i:s", strtotime("+{$diasNecesarios} days"));
}
