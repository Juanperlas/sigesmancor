<?php
// Nombre del archivo: verificar.php
// Ubicación: api/mantenimiento/preventivo/verificar.php

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
    
    // Verificar equipos sin mantenimientos preventivos
    $equiposFaltantes = verificarYGenerarMantenimientoEquipos($conexion, false);
    
    // Verificar componentes sin mantenimientos preventivos
    $componentesFaltantes = verificarYGenerarMantenimientoComponentes($conexion, false);
    
    // Preparar lista de faltantes para la respuesta
    $faltantes = [];
    
    // Obtener detalles de equipos faltantes
    if ($equiposFaltantes > 0) {
        $sqlEquipos = "SELECT e.id, e.codigo, e.nombre, 'equipo' as tipo 
                      FROM equipos e 
                      WHERE e.estado = 'activo' 
                      AND e.mantenimiento > 0
                      AND NOT EXISTS (
                          SELECT 1 FROM mantenimiento_preventivo mp 
                          WHERE mp.equipo_id = e.id AND mp.estado = 'pendiente'
                      )";
        
        $equiposFaltantesList = $conexion->select($sqlEquipos);
        $faltantes = array_merge($faltantes, $equiposFaltantesList);
    }
    
    // Obtener detalles de componentes faltantes
    if ($componentesFaltantes > 0) {
        $sqlComponentes = "SELECT c.id, c.codigo, c.nombre, 'componente' as tipo 
                          FROM componentes c 
                          WHERE c.estado = 'activo' 
                          AND c.mantenimiento > 0
                          AND NOT EXISTS (
                              SELECT 1 FROM mantenimiento_preventivo mp 
                              WHERE mp.componente_id = c.id AND mp.estado = 'pendiente'
                          )";
        
        $componentesFaltantesList = $conexion->select($sqlComponentes);
        $faltantes = array_merge($faltantes, $componentesFaltantesList);
    }
    
    // Preparar respuesta
    $response = [
        "success" => true,
        "equipos_faltantes" => $equiposFaltantes,
        "componentes_faltantes" => $componentesFaltantes,
        "total_faltantes" => $equiposFaltantes + $componentesFaltantes,
        "faltantes" => $faltantes,
        "message" => "Verificación de mantenimientos preventivos completada"
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al verificar mantenimientos preventivos: " . $e->getMessage()
    ];
    
    // Registrar error en log
    error_log("Error en verificar.php: " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);

/**
 * Verifica y cuenta equipos sin mantenimientos preventivos pendientes
 * @param Conexion $conexion Conexión a la base de datos
 * @param bool $generarRegistros Si es true, genera los registros; si es false, solo cuenta
 * @return int Número de equipos sin mantenimientos preventivos pendientes
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

        // Si no existe mantenimiento pendiente, contar
        if ($resultado["total"] == 0) {
            $contador++;
        }
    }

    return $contador;
}

/**
 * Verifica y cuenta componentes sin mantenimientos preventivos pendientes
 * @param Conexion $conexion Conexión a la base de datos
 * @param bool $generarRegistros Si es true, genera los registros; si es false, solo cuenta
 * @return int Número de componentes sin mantenimientos preventivos pendientes
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

        // Si no existe mantenimiento pendiente, contar
        if ($resultado["total"] == 0) {
            $contador++;
        }
    }

    return $contador;
}