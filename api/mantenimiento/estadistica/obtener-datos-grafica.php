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

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['elementos']) || !is_array($input['elementos'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $elementos = $input['elementos'];
    $fechaDesde = isset($input['fecha_desde']) ? $input['fecha_desde'] : date('Y-m-d', strtotime('-30 days'));
    $fechaHasta = isset($input['fecha_hasta']) ? $input['fecha_hasta'] : date('Y-m-d');
    $tipo = isset($input['tipo']) ? $input['tipo'] : 'equipo';

    $conexion = new Conexion();

    // Generar todas las fechas en el rango
    $fechas = [];
    $fechaActual = new DateTime($fechaDesde);
    $fechaFin = new DateTime($fechaHasta);
    
    while ($fechaActual <= $fechaFin) {
        $fechas[] = $fechaActual->format('Y-m-d');
        $fechaActual->add(new DateInterval('P1D'));
    }

    // Preparar series para la gráfica
    $series = [];
    $colores = ['#4361ee', '#e74c3c', '#f39c12', '#2ecc71', '#9b59b6', '#3498db', '#e67e22', '#1abc9c', '#34495e', '#f1c40f'];
    $colorIndex = 0;

    foreach ($elementos as $elemento) {
        $elementoId = intval($elemento['id']);
        $elementoNombre = $elemento['nombre'];
        
        // Construir consulta según el tipo
        if ($tipo === 'equipo') {
            $sql = "SELECT fecha, horas_trabajadas 
                    FROM historial_trabajo_diario 
                    WHERE equipo_id = ? AND fecha BETWEEN ? AND ?
                    ORDER BY fecha";
        } else {
            $sql = "SELECT fecha, horas_trabajadas 
                    FROM historial_trabajo_diario 
                    WHERE componente_id = ? AND fecha BETWEEN ? AND ?
                    ORDER BY fecha";
        }

        $registros = $conexion->select($sql, [$elementoId, $fechaDesde, $fechaHasta]);

        // Crear array asociativo para búsqueda rápida
        $horasPorFecha = [];
        foreach ($registros as $registro) {
            $horasPorFecha[$registro['fecha']] = floatval($registro['horas_trabajadas']);
        }

        // Crear serie de datos
        $data = [];
        foreach ($fechas as $fecha) {
            $data[] = isset($horasPorFecha[$fecha]) ? $horasPorFecha[$fecha] : 0;
        }

        $series[] = [
            'name' => $elementoNombre,
            'data' => $data,
            'color' => $colores[$colorIndex % count($colores)]
        ];

        $colorIndex++;
    }

    // Formatear fechas para mostrar
    $fechasFormateadas = array_map(function($fecha) {
        return date('d/m', strtotime($fecha));
    }, $fechas);

    echo json_encode([
        'success' => true,
        'fechas' => $fechasFormateadas,
        'series' => $series
    ]);

} catch (Exception $e) {
    error_log("Error en obtener-datos-grafica.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
