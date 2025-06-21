<?php
// Iniciar sesión
session_start();

// Incluir funciones y conexión a la base de datos
require_once '../db/conexion.php';
require_once '../db/funciones.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Obtener el tipo de datos solicitados
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '-30'; // Por defecto 1 año
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'todos';

// Crear conexión a la base de datos
$conexion = new Conexion();

// Función para obtener datos según el tipo solicitado
function obtenerDatos($tipo, $periodo, $categoria, $conexion)
{
    switch ($tipo) {
        case 'estadisticas':
            return obtenerEstadisticas($conexion);
        case 'historial_mantenimiento':
            return obtenerHistorialMantenimiento($conexion, $periodo, $categoria);
        case 'tendencia_mantenimiento':
            return obtenerTendenciaMantenimiento($conexion, $periodo, $categoria);
        case 'eventos_calendario':
            return obtenerEventosCalendario($conexion);
        case 'proximos_mantenimientos':
            return obtenerProximosMantenimientos($conexion);
        default:
            return ['error' => 'Tipo de datos no válido'];
    }
}

// Función para obtener estadísticas generales
function obtenerEstadisticas($conexion)
{
    try {
        // Verificar si la tabla equipos existe
        $tablaExiste = $conexion->selectOne("SHOW TABLES LIKE 'equipos'");

        if (!$tablaExiste) {
            // Si la tabla no existe, devolver datos simulados
            return [
                'totalEquipos' => 0,
                'equiposActivos' => 0,
                'equiposAveriados' => 0,
                'mantenimientosProgramados' => 0
            ];
        }

        // Contar equipos por estado
        $totalEquipos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos");
        $totalEquipos = $totalEquipos ? $totalEquipos['total'] : 0;

        $equiposActivos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos WHERE estado = 'activo'");
        $equiposActivos = $equiposActivos ? $equiposActivos['total'] : 0;

        $equiposAveriados = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos WHERE estado = 'averiado'");
        $equiposAveriados = $equiposAveriados ? $equiposAveriados['total'] : 0;

        // Contar mantenimientos pendientes (combinando preventivos, correctivos y programados)
        $queryMantenimientos = "
            (SELECT COUNT(*) as total FROM mantenimiento_preventivo WHERE estado = 'pendiente')
            UNION ALL
            (SELECT COUNT(*) as total FROM mantenimiento_correctivo WHERE estado = 'pendiente')
            UNION ALL
            (SELECT COUNT(*) as total FROM mantenimiento_programado WHERE estado = 'pendiente')
        ";

        $resultadosMantenimientos = $conexion->select($queryMantenimientos);
        $mantenimientosProgramados = 0;

        foreach ($resultadosMantenimientos as $resultado) {
            $mantenimientosProgramados += intval($resultado['total']);
        }

        return [
            'totalEquipos' => $totalEquipos,
            'equiposActivos' => $equiposActivos,
            'equiposAveriados' => $equiposAveriados,
            'mantenimientosProgramados' => $mantenimientosProgramados
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticas: " . $e->getMessage());
        return [
            'totalEquipos' => 0,
            'equiposActivos' => 0,
            'equiposAveriados' => 0,
            'mantenimientosProgramados' => 0
        ];
    }
}

// Función para obtener historial de mantenimiento
function obtenerHistorialMantenimiento($conexion, $periodo, $categoria)
{
    try {
        // Verificar si la tabla historial_mantenimiento existe
        $tablaExiste = $conexion->selectOne("SHOW TABLES LIKE 'historial_mantenimiento'");

        if (!$tablaExiste) {
            // Si la tabla no existe, devolver datos simulados
            return [
                'fechas' => ['Sin datos'],
                'series' => [
                    [
                        'name' => 'Preventivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Correctivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Predictivo',
                        'data' => [0]
                    ]
                ]
            ];
        }

        // Determinar la fecha de inicio según el período
        $fechaInicio = determinarFechaInicio($periodo);

        // Determinar el intervalo y formato según el período
        $intervalo = determinarIntervalo($periodo);
        $formato = determinarFormato($periodo);

        // Generar fechas para el eje X
        $fechas = generarFechas($fechaInicio, $intervalo, $formato);

        // Preparar arrays para almacenar los resultados
        $preventivos = array_fill(0, count($fechas['fechasDB']), 0);
        $correctivos = array_fill(0, count($fechas['fechasDB']), 0);
        $predictivos = array_fill(0, count($fechas['fechasDB']), 0);

        // Consultar datos de mantenimientos desde la tabla historial_mantenimiento
        $whereCategoria = $categoria !== 'todos' ? " AND e.categoria_id = " . intval($categoria) : "";

        $query = "
            SELECT 
                DATE(hm.fecha_realizado) as fecha, 
                hm.tipo_mantenimiento, 
                COUNT(*) as total
            FROM 
                historial_mantenimiento hm
            LEFT JOIN 
                equipos e ON hm.equipo_id = e.id
            WHERE 
                hm.fecha_realizado >= ? $whereCategoria
            GROUP BY 
                DATE(hm.fecha_realizado), hm.tipo_mantenimiento
            ORDER BY 
                fecha
        ";

        $resultados = $conexion->select($query, [$fechaInicio]);

        // Procesar resultados
        foreach ($resultados as $resultado) {
            $fecha = $resultado['fecha'];
            $tipo = $resultado['tipo_mantenimiento'];
            $total = intval($resultado['total']);

            $indice = array_search($fecha, $fechas['fechasDB']);
            if ($indice !== false) {
                if ($tipo === 'preventivo') {
                    $preventivos[$indice] += $total;
                } else if ($tipo === 'correctivo') {
                    $correctivos[$indice] += $total;
                } else if ($tipo === 'predictivo') {
                    $predictivos[$indice] += $total;
                }
            }
        }

        // Si no hay datos, usar al menos una fecha
        if (empty($fechas['fechasFormateadas'])) {
            return [
                'fechas' => ['Sin datos'],
                'series' => [
                    [
                        'name' => 'Preventivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Correctivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Predictivo',
                        'data' => [0]
                    ]
                ]
            ];
        }

        return [
            'fechas' => $fechas['fechasFormateadas'],
            'series' => [
                [
                    'name' => 'Preventivo',
                    'data' => $preventivos
                ],
                [
                    'name' => 'Correctivo',
                    'data' => $correctivos
                ],
                [
                    'name' => 'Predictivo',
                    'data' => $predictivos
                ]
            ]
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerHistorialMantenimiento: " . $e->getMessage());
        return [
            'fechas' => ['Error'],
            'series' => [
                [
                    'name' => 'Preventivo',
                    'data' => [0]
                ],
                [
                    'name' => 'Correctivo',
                    'data' => [0]
                ],
                [
                    'name' => 'Predictivo',
                    'data' => [0]
                ]
            ]
        ];
    }
}

// Función para obtener tendencia de mantenimientos
function obtenerTendenciaMantenimiento($conexion, $periodo, $categoria)
{
    try {
        // Verificar si la tabla historial_mantenimiento existe
        $tablaExiste = $conexion->selectOne("SHOW TABLES LIKE 'historial_mantenimiento'");

        if (!$tablaExiste) {
            // Si la tabla no existe, devolver datos simulados
            return [
                'fechas' => ['Sin datos'],
                'series' => [
                    [
                        'name' => 'Preventivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Correctivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Predictivo',
                        'data' => [0]
                    ]
                ]
            ];
        }

        // Determinar la fecha de inicio según el período
        $fechaInicio = determinarFechaInicio($periodo);

        // Determinar el intervalo y formato según el período
        $intervalo = determinarIntervalo($periodo);
        $formato = determinarFormato($periodo);

        // Generar fechas para el eje X
        $fechas = generarFechas($fechaInicio, $intervalo, $formato);

        // Preparar arrays para almacenar los resultados
        $preventivos = array_fill(0, count($fechas['fechasDB']), 0);
        $correctivos = array_fill(0, count($fechas['fechasDB']), 0);
        $predictivos = array_fill(0, count($fechas['fechasDB']), 0);

        // Filtro por tipo si se especifica
        $whereTipo = "";
        if ($categoria !== 'todos') {
            $whereTipo = " AND hm.tipo_mantenimiento = '" . $conexion->escape($categoria) . "'";
        }

        // Consultar datos de mantenimientos desde la tabla historial_mantenimiento
        $query = "
            SELECT 
                DATE(hm.fecha_realizado) as fecha, 
                hm.tipo_mantenimiento, 
                COUNT(*) as total
            FROM 
                historial_mantenimiento hm
            WHERE 
                hm.fecha_realizado >= ? $whereTipo
            GROUP BY 
                DATE(hm.fecha_realizado), hm.tipo_mantenimiento
            ORDER BY 
                fecha
        ";

        $resultados = $conexion->select($query, [$fechaInicio]);

        // Procesar resultados
        foreach ($resultados as $resultado) {
            $fecha = $resultado['fecha'];
            $tipo = $resultado['tipo_mantenimiento'];
            $total = intval($resultado['total']);

            $indice = array_search($fecha, $fechas['fechasDB']);
            if ($indice !== false) {
                if ($tipo === 'preventivo') {
                    $preventivos[$indice] += $total;
                } else if ($tipo === 'correctivo') {
                    $correctivos[$indice] += $total;
                } else if ($tipo === 'predictivo') {
                    $predictivos[$indice] += $total;
                }
            }
        }

        // Si no hay datos, usar al menos una fecha
        if (empty($fechas['fechasFormateadas'])) {
            return [
                'fechas' => ['Sin datos'],
                'series' => [
                    [
                        'name' => 'Preventivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Correctivo',
                        'data' => [0]
                    ],
                    [
                        'name' => 'Predictivo',
                        'data' => [0]
                    ]
                ]
            ];
        }

        // Determinar qué series devolver según el filtro
        $series = [];
        if ($categoria === 'todos' || $categoria === 'preventivo') {
            $series[] = [
                'name' => 'Preventivo',
                'data' => $preventivos
            ];
        }

        if ($categoria === 'todos' || $categoria === 'correctivo') {
            $series[] = [
                'name' => 'Correctivo',
                'data' => $correctivos
            ];
        }

        if ($categoria === 'todos' || $categoria === 'predictivo') {
            $series[] = [
                'name' => 'Predictivo',
                'data' => $predictivos
            ];
        }

        return [
            'fechas' => $fechas['fechasFormateadas'],
            'series' => $series
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerTendenciaMantenimiento: " . $e->getMessage());
        return [
            'fechas' => ['Error'],
            'series' => [
                [
                    'name' => 'Preventivo',
                    'data' => [0]
                ],
                [
                    'name' => 'Correctivo',
                    'data' => [0]
                ],
                [
                    'name' => 'Predictivo',
                    'data' => [0]
                ]
            ]
        ];
    }
}

// Función para obtener eventos para el calendario
function obtenerEventosCalendario($conexion)
{
    try {
        $eventos = [];

        // Verificar si las tablas necesarias existen
        $tablasExisten = true;
        $tablasNecesarias = ['mantenimiento_preventivo', 'mantenimiento_correctivo', 'mantenimiento_programado', 'equipos', 'historial_mantenimiento'];

        foreach ($tablasNecesarias as $tabla) {
            $tablaExiste = $conexion->selectOne("SHOW TABLES LIKE '$tabla'");
            if (!$tablaExiste) {
                $tablasExisten = false;
                break;
            }
        }

        if (!$tablasExisten) {
            // Si alguna tabla no existe, devolver array vacío
            return [];
        }

        // 1. Obtener mantenimientos preventivos pendientes
        $preventivos = $conexion->select(
            "SELECT mp.id, e.nombre as equipo_nombre, mp.descripcion_razon as descripcion, 
                    mp.fecha_programada as fecha, 'preventivo' as tipo, 'pendiente' as estado
             FROM mantenimiento_preventivo mp
             LEFT JOIN equipos e ON mp.equipo_id = e.id
             WHERE mp.estado = 'pendiente' AND mp.fecha_programada >= CURDATE() - INTERVAL 30 DAY
             ORDER BY mp.fecha_programada
             LIMIT 50"
        );

        foreach ($preventivos as $preventivo) {
            $eventos[] = [
                'title' => ($preventivo['equipo_nombre'] ? $preventivo['equipo_nombre'] : 'Equipo') . ' - Preventivo',
                'start' => date('Y-m-d', strtotime($preventivo['fecha'])),
                'description' => $preventivo['descripcion'] ? $preventivo['descripcion'] : 'Sin descripción',
                'className' => 'preventivo',
                'tipo' => 'preventivo',
                'estado' => 'pendiente'
            ];
        }

        // 2. Obtener mantenimientos correctivos pendientes
        $correctivos = $conexion->select(
            "SELECT mc.id, e.nombre as equipo_nombre, mc.descripcion_problema as descripcion, 
                    mc.fecha_hora_problema as fecha, 'correctivo' as tipo, 'pendiente' as estado
             FROM mantenimiento_correctivo mc
             LEFT JOIN equipos e ON mc.equipo_id = e.id
             WHERE mc.estado = 'pendiente' AND mc.fecha_hora_problema >= CURDATE() - INTERVAL 30 DAY
             ORDER BY mc.fecha_hora_problema
             LIMIT 50"
        );

        foreach ($correctivos as $correctivo) {
            $eventos[] = [
                'title' => ($correctivo['equipo_nombre'] ? $correctivo['equipo_nombre'] : 'Equipo') . ' - Correctivo',
                'start' => date('Y-m-d', strtotime($correctivo['fecha'])),
                'description' => $correctivo['descripcion'] ? $correctivo['descripcion'] : 'Sin descripción',
                'className' => 'correctivo',
                'tipo' => 'correctivo',
                'estado' => 'pendiente'
            ];
        }

        // 3. Obtener mantenimientos programados (predictivos) pendientes
        $programados = $conexion->select(
            "SELECT mp.id, e.nombre as equipo_nombre, mp.descripcion_razon as descripcion, 
                    mp.fecha_programada as fecha, 'predictivo' as tipo, 'pendiente' as estado
             FROM mantenimiento_programado mp
             LEFT JOIN equipos e ON mp.equipo_id = e.id
             WHERE mp.estado = 'pendiente' AND mp.fecha_programada >= CURDATE() - INTERVAL 30 DAY
             ORDER BY mp.fecha_programada
             LIMIT 50"
        );

        foreach ($programados as $programado) {
            $eventos[] = [
                'title' => ($programado['equipo_nombre'] ? $programado['equipo_nombre'] : 'Equipo') . ' - Predictivo',
                'start' => date('Y-m-d', strtotime($programado['fecha'])),
                'description' => $programado['descripcion'] ? $programado['descripcion'] : 'Sin descripción',
                'className' => 'predictivo',
                'tipo' => 'predictivo',
                'estado' => 'pendiente'
            ];
        }

        // 4. Obtener mantenimientos completados del historial
        $historial = $conexion->select(
            "SELECT hm.id, e.nombre as equipo_nombre, hm.descripcion, 
                    hm.fecha_realizado as fecha, hm.tipo_mantenimiento as tipo, 'completado' as estado
             FROM historial_mantenimiento hm
             LEFT JOIN equipos e ON hm.equipo_id = e.id
             WHERE hm.fecha_realizado >= CURDATE() - INTERVAL 30 DAY
             ORDER BY hm.fecha_realizado
             LIMIT 100"
        );

        foreach ($historial as $item) {
            $eventos[] = [
                'title' => ($item['equipo_nombre'] ? $item['equipo_nombre'] : 'Equipo') . ' - ' . ucfirst($item['tipo']) . ' (Completado)',
                'start' => date('Y-m-d', strtotime($item['fecha'])),
                'description' => $item['descripcion'] ? $item['descripcion'] : 'Sin descripción',
                'className' => 'completado',
                'tipo' => $item['tipo'],
                'estado' => 'completado'
            ];
        }

        return $eventos;
    } catch (Exception $e) {
        error_log("Error en obtenerEventosCalendario: " . $e->getMessage());
        return [];
    }
}

// Función para obtener próximos mantenimientos
function obtenerProximosMantenimientos($conexion)
{
    try {
        $mantenimientos = [];

        // Verificar si las tablas necesarias existen
        $tablasExisten = true;
        $tablasNecesarias = ['mantenimiento_preventivo', 'mantenimiento_correctivo', 'mantenimiento_programado', 'equipos'];

        foreach ($tablasNecesarias as $tabla) {
            $tablaExiste = $conexion->selectOne("SHOW TABLES LIKE '$tabla'");
            if (!$tablaExiste) {
                $tablasExisten = false;
                break;
            }
        }

        if (!$tablasExisten) {
            // Si alguna tabla no existe, devolver array vacío
            return [];
        }

        // Consulta para obtener próximos mantenimientos (combinando preventivos, correctivos y programados)
        $query = "
            (SELECT 'preventivo' as tipo, mp.id, e.nombre as equipo, mp.descripcion_razon as descripcion,
                    mp.fecha_programada as fecha, mp.estado
             FROM mantenimiento_preventivo mp
             LEFT JOIN equipos e ON mp.equipo_id = e.id
             WHERE mp.estado = 'pendiente' AND mp.fecha_programada >= CURDATE())
            UNION
            (SELECT 'correctivo' as tipo, mc.id, e.nombre as equipo, mc.descripcion_problema as descripcion,
                    mc.fecha_hora_problema as fecha, mc.estado
             FROM mantenimiento_correctivo mc
             LEFT JOIN equipos e ON mc.equipo_id = e.id
             WHERE mc.estado = 'pendiente' AND mc.fecha_hora_problema >= CURDATE())
            UNION
            (SELECT 'predictivo' as tipo, mp.id, e.nombre as equipo, mp.descripcion_razon as descripcion,
                    mp.fecha_programada as fecha, mp.estado
             FROM mantenimiento_programado mp
             LEFT JOIN equipos e ON mp.equipo_id = e.id
             WHERE mp.estado = 'pendiente' AND mp.fecha_programada >= CURDATE())
            ORDER BY fecha
            LIMIT 10
        ";

        $resultados = $conexion->select($query);

        foreach ($resultados as $resultado) {
            $mantenimientos[] = [
                'tipo' => $resultado['tipo'],
                'equipo' => $resultado['equipo'] ?: 'Sin nombre',
                'descripcion' => $resultado['descripcion'] ?: 'Sin descripción',
                'fecha' => date('d/m/Y', strtotime($resultado['fecha'])),
                'estado' => $resultado['estado']
            ];
        }

        return $mantenimientos;
    } catch (Exception $e) {
        error_log("Error en obtenerProximosMantenimientos: " . $e->getMessage());
        return [];
    }
}

// Función para determinar la fecha de inicio según el período
function determinarFechaInicio($periodo)
{
    $fecha = new DateTime();

    switch ($periodo) {
        case '7d':
            $fecha->modify('-7 days');
            break;
        case '30d':
            $fecha->modify('-30 days');
            break;
        case '90d':
            $fecha->modify('-90 days');
            break;
        case '3m':
            $fecha->modify('-3 months');
            break;
        case '6m':
            $fecha->modify('-6 months');
            break;
        case '1y':
            $fecha->modify('-1 year');
            break;
        case 'all':
            $fecha->modify('-5 years'); // Un valor suficientemente grande
            break;
        default:
            $fecha->modify('-30 days');
    }

    return $fecha->format('Y-m-d');
}

// Función para determinar el intervalo según el período
function determinarIntervalo($periodo)
{
    switch ($periodo) {
        case '7d':
            return 'day';
        case '30d':
            return 'day';
        case '90d':
            return 'week';
        case '3m':
            return 'week';
        case '6m':
            return 'month';
        case '1y':
            return 'month';
        case 'all':
            return 'month';
        default:
            return 'day';
    }
}

// Función para determinar el formato de fecha según el período
function determinarFormato($periodo)
{
    switch ($periodo) {
        case '7d':
        case '30d':
            return 'd M';
        case '90d':
        case '3m':
            return '\Sem W';
        case '6m':
        case '1y':
            return 'd M';
        case 'all':
            return 'M Y';
        default:
            return 'd M';
    }
}

// Función para generar fechas para el eje X
function generarFechas($fechaInicio, $intervalo, $formato)
{
    $fechaInicio = new DateTime($fechaInicio);
    $fechaFin = new DateTime();
    $fechasDB = [];
    $fechasFormateadas = [];

    $interval = new DateInterval('P1' . strtoupper(substr($intervalo, 0, 1)));
    $periodo = new DatePeriod($fechaInicio, $interval, $fechaFin);

    foreach ($periodo as $fecha) {
        $fechasDB[] = $fecha->format('Y-m-d');
        $fechasFormateadas[] = $fecha->format($formato);
    }

    // Asegurar que la fecha actual esté incluida
    $fechaActual = $fechaFin->format('Y-m-d');
    if (!in_array($fechaActual, $fechasDB)) {
        $fechasDB[] = $fechaActual;
        $fechasFormateadas[] = $fechaFin->format($formato);
    }

    return [
        'fechasDB' => $fechasDB,
        'fechasFormateadas' => $fechasFormateadas
    ];
}

// Obtener y devolver los datos solicitados
try {
    $datos = obtenerDatos($tipo, $periodo, $categoria, $conexion);

    // Devolver los datos en formato JSON
    header('Content-Type: application/json');
    echo json_encode($datos);
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en dashboard_data.php: " . $e->getMessage());

    // Devolver un mensaje de error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
