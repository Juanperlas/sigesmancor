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
if (!tienePermiso('administracion.horarios.editar')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para editar horarios']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['registros']) || !is_array($input['registros'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$conexion = new Conexion();
$registrosActualizados = 0;
$registrosHistorial = 0;
$errores = [];

/**
 * Función para registrar o actualizar en historial_trabajo_diario
 */
function registrarEnHistorialTrabajo($conexion, $equipoId, $componenteId, $horas, $fuente = 'manual') {
    $fechaHoy = date('Y-m-d');
    
    // Verificar si ya existe un registro para hoy
    $whereClause = $equipoId ? "equipo_id = ? AND fecha = ?" : "componente_id = ? AND fecha = ?";
    $params = $equipoId ? [$equipoId, $fechaHoy] : [$componenteId, $fechaHoy];
    
    $registroExistente = $conexion->selectOne(
        "SELECT id, horas_trabajadas FROM historial_trabajo_diario WHERE $whereClause",
        $params
    );
    
    if ($registroExistente) {
        // Actualizar registro existente sumando las horas
        $nuevasHoras = floatval($registroExistente['horas_trabajadas']) + $horas;
        $resultado = $conexion->update(
            'historial_trabajo_diario',
            [
                'horas_trabajadas' => $nuevasHoras,
                'fuente' => $fuente,
                'actualizado_en' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$registroExistente['id']]
        );
        return $resultado ? 'actualizado' : false;
    } else {
        // Crear nuevo registro
        $datos = [
            'fecha' => $fechaHoy,
            'horas_trabajadas' => $horas,
            'fuente' => $fuente,
            'creado_en' => date('Y-m-d H:i:s')
        ];
        
        if ($equipoId) {
            $datos['equipo_id'] = $equipoId;
            $datos['componente_id'] = null;
        } else {
            $datos['componente_id'] = $componenteId;
            $datos['equipo_id'] = null;
        }
        
        $resultado = $conexion->insert('historial_trabajo_diario', $datos);
        return $resultado ? 'creado' : false;
    }
}

try {
    // Iniciar transacción
    $conexion->getConexion()->beginTransaction();

    foreach ($input['registros'] as $registro) {
        if (!isset($registro['id'], $registro['tipo'], $registro['horas']) || 
            !in_array($registro['tipo'], ['equipo', 'componente']) ||
            !is_numeric($registro['horas']) ||
            floatval($registro['horas']) <= 0) {
            continue;
        }

        $id = intval($registro['id']);
        $tipo = $registro['tipo'];
        $horas = floatval($registro['horas']);

        // Actualizar el orómetro actual
        if ($tipo === 'equipo') {
            $tabla = 'equipos';
            $equipoId = $id;
            $componenteId = null;
        } else {
            $tabla = 'componentes';
            $equipoId = null;
            $componenteId = $id;
        }

        // Obtener el orómetro actual
        $actual = $conexion->selectOne(
            "SELECT orometro_actual FROM $tabla WHERE id = ?",
            [$id]
        );

        if ($actual) {
            $nuevoOrometro = floatval($actual['orometro_actual']) + $horas;
            
            $resultado = $conexion->update(
                $tabla,
                ['orometro_actual' => $nuevoOrometro],
                'id = ?',
                [$id]
            );

            if ($resultado) {
                $registrosActualizados++;
                
                // Registrar en historial_trabajo_diario
                $resultadoHistorial = registrarEnHistorialTrabajo($conexion, $equipoId, $componenteId, $horas, 'manual');
                if ($resultadoHistorial) {
                    $registrosHistorial++;
                }
            }
        }
    }

    // Confirmar transacción
    $conexion->getConexion()->commit();

    echo json_encode([
        'success' => true,
        'message' => "Se actualizaron $registrosActualizados registros correctamente y se registraron $registrosHistorial entradas en el historial",
        'registros_actualizados' => $registrosActualizados,
        'registros_historial' => $registrosHistorial
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->getConexion()->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
