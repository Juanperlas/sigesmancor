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
if (!tienePermiso('estadisticas.ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para ver estadísticas']);
    exit;
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'equipo';

try {
    $conexion = new Conexion();
    $elementos = [];

    if ($tipo === 'equipo') {
        // Obtener equipos que tienen registros en historial_trabajo_diario
        $sql = "SELECT DISTINCT e.id, e.codigo, e.nombre, e.tipo_equipo
                FROM equipos e
                INNER JOIN historial_trabajo_diario htd ON e.id = htd.equipo_id
                WHERE e.estado = 'activo'
                ORDER BY e.nombre";
        
        $resultados = $conexion->select($sql);
        
        foreach ($resultados as $resultado) {
            $elementos[] = [
                'id' => intval($resultado['id']),
                'codigo' => $resultado['codigo'],
                'nombre' => $resultado['nombre'],
                'tipo_equipo' => $resultado['tipo_equipo'],
                'tipo' => 'equipo'
            ];
        }
    } else {
        // Obtener componentes que tienen registros en historial_trabajo_diario
        $sql = "SELECT DISTINCT c.id, c.codigo, c.nombre, c.modelo as tipo_equipo, e.nombre as equipo_nombre
                FROM componentes c
                INNER JOIN historial_trabajo_diario htd ON c.id = htd.componente_id
                LEFT JOIN equipos e ON c.equipo_id = e.id
                WHERE c.estado = 'activo'
                ORDER BY c.nombre";
        
        $resultados = $conexion->select($sql);
        
        foreach ($resultados as $resultado) {
            $elementos[] = [
                'id' => intval($resultado['id']),
                'codigo' => $resultado['codigo'],
                'nombre' => $resultado['nombre'],
                'tipo_equipo' => $resultado['tipo_equipo'],
                'equipo_nombre' => $resultado['equipo_nombre'],
                'tipo' => 'componente'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'elementos' => $elementos
    ]);

} catch (Exception $e) {
    error_log("Error en obtener-elementos.php: " . $e->getMessage());
    error_log("Tipo solicitado: " . $tipo);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => [
            'tipo' => $tipo,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
