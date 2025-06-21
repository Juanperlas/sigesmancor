<?php
require_once __DIR__ . '/conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool
 */
function estaAutenticado()
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario tiene el rol de administrador o superadmin
 * @return bool
 */
function esAdmin()
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    $roles = $conexion->getUserRoles($_SESSION['usuario_id']);
    return in_array('admin', $roles) || in_array('superadmin', $roles);
}

/**
 * Verifica si el usuario tiene el rol de superadmin
 * @return bool
 */
function esSuperAdmin()
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    $roles = $conexion->getUserRoles($_SESSION['usuario_id']);
    return in_array('superadmin', $roles);
}

/**
 * Verifica si el usuario tiene un permiso específico
 * @param string $permiso
 * @return bool
 */
function tienePermiso($permiso)
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    return $conexion->hasPermission($_SESSION['usuario_id'], $permiso);
}

/**
 * Redirige al usuario al login si no está autenticado
 */
function verificarAutenticacion()
{
    if (!estaAutenticado()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirigir_despues_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . getPageUrl('login.php'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene permisos de administrador, si no, redirige
 */
function verificarAdmin()
{
    verificarAutenticacion();
    if (!esAdmin()) {
        setMensaje('error', 'No tienes permisos para acceder a esta sección');
        header("Location: " . getPageUrl('dashboard.php'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene permisos de superadmin, si no, redirige
 */
function verificarSuperAdmin()
{
    verificarAutenticacion();
    if (!esSuperAdmin()) {
        setMensaje('error', 'No tienes permisos para acceder a esta sección');
        header("Location: " . getPageUrl('dashboard.php'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene un permiso específico, si no, redirige
 * @param string $permiso
 */
function verificarPermiso($permiso)
{
    verificarAutenticacion();
    if (!tienePermiso($permiso)) {
        setMensaje('error', 'No tienes permisos para realizar esta acción');
        header("Location: " . getPageUrl('dashboard.php'));
        exit;
    }
}

/**
 * Establece un mensaje en la sesión
 * @param string $tipo Tipo de mensaje (success, error, warning, info)
 * @param string $texto Texto del mensaje
 */
function setMensaje($tipo, $texto)
{
    $_SESSION['mensaje'] = [
        'tipo' => $tipo,
        'texto' => $texto
    ];
}

/**
 * Obtiene el mensaje de la sesión y lo elimina
 * @return array|null
 */
function getMensaje()
{
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
    return null;
}

/**
 * Obtiene los datos del usuario actual
 * @return array|null
 */
function getUsuarioActual()
{
    if (!estaAutenticado()) {
        return null;
    }

    $conexion = new Conexion();
    $usuario = $conexion->selectOne(
        "SELECT u.id, u.username, u.nombre_completo AS nombre, u.correo, u.fotografia,
                GROUP_CONCAT(r.nombre) AS roles
         FROM usuarios u
         LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id
         LEFT JOIN roles r ON ur.rol_id = r.id
         WHERE u.id = ? AND u.esta_activo = 1
         GROUP BY u.id",
        [$_SESSION['usuario_id']]
    );

    if ($usuario) {
        $usuario['roles'] = $usuario['roles'] ? explode(',', $usuario['roles']) : [];
    }

    return $usuario;
}

/**
 * Función para sanitizar entradas
 * @param string $data
 * @return string
 */
function sanitizar($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Genera una URL relativa a la raíz del proyecto
 * @param string $path
 * @return string
 */
function url($path)
{
    // Determinar la ruta base
    $base = '/sigesmancor';

    // Eliminar barras iniciales y finales
    $path = trim($path, '/');

    // Devolver la URL completa
    return $base . '/' . $path;
}

/**
 * Registra el inicio de sesión de un usuario
 * @param int $usuario_id
 * @return int ID de la sesión creada
 */
function registrarInicioSesion($usuario_id)
{
    $conexion = new Conexion();
    return $conexion->insert('sesiones_usuarios', [
        'usuario_id' => $usuario_id,
        'esta_activa' => 1
    ]);
}

/**
 * Obtiene el ID del usuario actualmente autenticado
 * @return int|null
 */
function getUsuarioId()
{
    if (!estaAutenticado()) {
        return null;
    }
    return $_SESSION['usuario_id'];
}

/**
 * Registra el cierre de sesión de un usuario
 * @param int $usuario_id
 * @return bool
 */
function registrarCierreSesion($usuario_id)
{
    $conexion = new Conexion();
    $conexion->update(
        'sesiones_usuarios',
        [
            'fin_sesion' => date('Y-m-d H:i:s'),
            'esta_activa' => 0
        ],
        'usuario_id = ? AND esta_activa = 1',
        [$usuario_id]
    );
    return true;
}

/**
 * Obtiene estadísticas para el dashboard
 * @return array
 */
function obtenerEstadisticasDashboard()
{
    $conexion = new Conexion();

    // Contar equipos por estado
    $totalEquipos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos");
    $totalEquipos = $totalEquipos ? $totalEquipos['total'] : 0;

    $equiposActivos = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos WHERE estado = 'activo'");
    $equiposActivos = $equiposActivos ? $equiposActivos['total'] : 0;

    $equiposAveriados = $conexion->selectOne("SELECT COUNT(*) as total FROM equipos WHERE estado = 'averiado'");
    $equiposAveriados = $equiposAveriados ? $equiposAveriados['total'] : 0;

    // Contar mantenimientos programados pendientes
    $mantenimientosProgramados = $conexion->selectOne(
        "SELECT COUNT(*) as total FROM mantenimiento_programado WHERE estado = 'pendiente'"
    );
    $mantenimientosProgramados = $mantenimientosProgramados ? $mantenimientosProgramados['total'] : 0;

    // Si no hay datos reales, usar datos simulados para demostración
    if ($totalEquipos == 0) {
        $totalEquipos = 125;
        $equiposActivos = 98;
        $equiposAveriados = 27;
        $mantenimientosProgramados = 15;
    }

    return [
        'totalEquipos' => $totalEquipos,
        'equiposActivos' => $equiposActivos,
        'equiposAveriados' => $equiposAveriados,
        'mantenimientosProgramados' => $mantenimientosProgramados
    ];
}

/**
 * Obtiene los últimos equipos registrados
 * @param int $limite
 * @return array
 */
function obtenerUltimosEquipos($limite = 5)
{
    $conexion = new Conexion();

    $equipos = $conexion->select(
        "SELECT id, codigo, nombre, marca, modelo, estado, ubicacion 
         FROM equipos 
         ORDER BY creado_en DESC 
         LIMIT ?",
        [$limite]
    );

    // Si no hay datos reales, usar datos simulados para demostración
    if (empty($equipos)) {
        return [
            [
                'id' => 1,
                'nombre' => 'Excavadora CAT 336',
                'modelo' => '336',
                'marca' => 'Caterpillar',
                'estado' => 'activo',
                'ubicacion' => 'Mina Norte'
            ],
            [
                'id' => 2,
                'nombre' => 'Cargador Frontal 966H',
                'modelo' => '966H',
                'marca' => 'Caterpillar',
                'estado' => 'activo',
                'ubicacion' => 'Mina Sur'
            ],
            [
                'id' => 3,
                'nombre' => 'Camión Minero 777F',
                'modelo' => '777F',
                'marca' => 'Caterpillar',
                'estado' => 'averiado',
                'ubicacion' => 'Taller Central'
            ],
            [
                'id' => 4,
                'nombre' => 'Perforadora DM45',
                'modelo' => 'DM45',
                'marca' => 'Atlas Copco',
                'estado' => 'activo',
                'ubicacion' => 'Mina Este'
            ],
            [
                'id' => 5,
                'nombre' => 'Motoniveladora 140M',
                'modelo' => '140M',
                'marca' => 'Caterpillar',
                'estado' => 'activo',
                'ubicacion' => 'Mina Oeste'
            ]
        ];
    }

    return $equipos;
}

/**
 * Obtiene la distribución de equipos por ubicación
 * @return array
 */
function obtenerDistribucionEquiposPorUbicacion()
{
    $conexion = new Conexion();

    $distribucion = $conexion->select(
        "SELECT ubicacion as nombre, COUNT(*) as total 
         FROM equipos 
         GROUP BY ubicacion 
         ORDER BY total DESC"
    );

    // Si no hay datos reales, usar datos simulados para demostración
    if (empty($distribucion)) {
        return [
            ['nombre' => 'Mina Norte', 'total' => 35],
            ['nombre' => 'Mina Sur', 'total' => 28],
            ['nombre' => 'Mina Este', 'total' => 22],
            ['nombre' => 'Mina Oeste', 'total' => 18],
            ['nombre' => 'Taller Central', 'total' => 12],
            ['nombre' => 'Almacén', 'total' => 10]
        ];
    }

    return $distribucion;
}

/**
 * Obtiene la ruta base del proyecto
 * @return string
 */
function getProjectRoot()
{
    return '/sigesmancor/';
}

/**
 * Obtiene la URL para assets
 * @param string $path
 * @return string
 */
function getAssetUrl($path = '')
{
    return getProjectRoot() . ltrim($path, '/');
}

/**
 * Obtiene la URL para páginas
 * @param string $path
 * @return string
 */
function getPageUrl($path = '')
{
    return getProjectRoot() . ltrim($path, '/');
}
/**
 * Obtiene la fecha y hora de la última actualización de fechas de mantenimientos preventivos.
 * @param Conexion $conexion Conexión a la base de datos
 * @return string|null Fecha y hora en formato Y-m-d H:i:s, o null si no hay registros
 */
function obtenerUltimaActualizacionFechas(Conexion $conexion)
{
    $sql = "SELECT MAX(fecha_actualizacion) as ultima_fecha FROM log_actualizaciones_fechas";
    $resultado = $conexion->selectOne($sql);

    return $resultado && $resultado["ultima_fecha"] ? $resultado["ultima_fecha"] : null;
}

/**
 * Registra la fecha y hora de la actualización de fechas de mantenimientos preventivos.
 * @param Conexion $conexion Conexión a la base de datos
 * @return bool True si se registró correctamente, false en caso contrario
 */
function registrarActualizacionFechas(Conexion $conexion)
{
    $sql = "INSERT INTO log_actualizaciones_fechas (fecha_actualizacion) VALUES (NOW())";
    return $conexion->insert("log_actualizaciones_fechas", []) !== false;
}
