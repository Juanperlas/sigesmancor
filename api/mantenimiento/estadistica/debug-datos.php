<?php
// Archivo de debug para verificar los datos
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

header('Content-Type: application/json');

try {
    $conexion = new Conexion();
    
    // Verificar datos en historial_trabajo_diario
    $sql = "SELECT COUNT(*) as total FROM historial_trabajo_diario";
    $totalHistorial = $conexion->selectOne($sql);
    
    // Obtener algunos registros de ejemplo
    $sql = "SELECT htd.*, 
                   e.nombre as equipo_nombre, e.codigo as equipo_codigo,
                   c.nombre as componente_nombre, c.codigo as componente_codigo
            FROM historial_trabajo_diario htd
            LEFT JOIN equipos e ON htd.equipo_id = e.id
            LEFT JOIN componentes c ON htd.componente_id = c.id
            ORDER BY htd.fecha DESC
            LIMIT 10";
    $ejemplos = $conexion->select($sql);
    
    // Verificar equipos con historial
    $sql = "SELECT COUNT(DISTINCT e.id) as total 
            FROM equipos e 
            INNER JOIN historial_trabajo_diario htd ON e.id = htd.equipo_id 
            WHERE e.estado = 'activo'";
    $equiposConHistorial = $conexion->selectOne($sql);
    
    // Verificar componentes con historial
    $sql = "SELECT COUNT(DISTINCT c.id) as total 
            FROM componentes c 
            INNER JOIN historial_trabajo_diario htd ON c.id = htd.componente_id 
            WHERE c.estado = 'activo'";
    $componentesConHistorial = $conexion->selectOne($sql);
    
    // Verificar permisos del usuario actual
    $permisos = [];
    if (function_exists('tienePermiso')) {
        $permisos = [
            'estadisticas.ver' => tienePermiso('estadisticas.ver'),
            'estadisticas.acceder' => tienePermiso('estadisticas.acceder'),
        ];
    }
    
    echo json_encode([
        'success' => true,
        'debug_info' => [
            'total_registros_historial' => $totalHistorial['total'],
            'equipos_con_historial' => $equiposConHistorial['total'],
            'componentes_con_historial' => $componentesConHistorial['total'],
            'ejemplos_registros' => $ejemplos,
            'permisos_usuario' => $permisos,
            'usuario_autenticado' => estaAutenticado()
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error de debug: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
