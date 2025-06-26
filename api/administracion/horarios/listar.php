<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Establecer cabeceras para respuesta JSON
header('Content-Type: application/json');

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no permitido']);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Verificar permiso
if (!tienePermiso('administracion.horarios.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver horarios']);
    exit;
}

$conexion = new Conexion();

try {
    // Consulta para equipos
    $sqlEquipos = "SELECT e.id, e.codigo, e.nombre, e.tipo_equipo, e.limite as horas, 'equipo' as tipo 
                   FROM equipos e 
                   WHERE e.estado = 'activo' AND e.mantenimiento > 0 AND e.limite > 0
                   ORDER BY e.nombre";
    
    // Consulta para componentes
    $sqlComponentes = "SELECT c.id, c.codigo, c.nombre, c.modelo as tipo_equipo, c.limite as horas, 'componente' as tipo 
                       FROM componentes c 
                       WHERE c.estado = 'activo' AND c.mantenimiento > 0 AND c.limite > 0
                       ORDER BY c.nombre";

    $equipos = $conexion->select($sqlEquipos);
    $componentes = $conexion->select($sqlComponentes);

    // Formatear los datos
    $equiposFormateados = array_map(function ($row) {
        return [
            'id' => $row['id'],
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'tipo_equipo' => $row['tipo_equipo'],
            'horas' => number_format($row['horas'], 2),
            'tipo' => $row['tipo']
        ];
    }, $equipos);

    $componentesFormateados = array_map(function ($row) {
        return [
            'id' => $row['id'],
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'tipo_equipo' => $row['tipo_equipo'],
            'horas' => number_format($row['horas'], 2),
            'tipo' => $row['tipo']
        ];
    }, $componentes);

    echo json_encode([
        'success' => true,
        'equipos' => $equiposFormateados,
        'componentes' => $componentesFormateados
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
