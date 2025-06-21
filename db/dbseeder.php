<?php

// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'sigesmancor';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa. Iniciando el seeder...\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para ejecutar consultas preparadas
function ejecutarConsulta($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Función para obtener el último ID insertado
function ultimoId($pdo)
{
    return $pdo->lastInsertId();
}

// Función para generar contraseñas encriptadas
function encriptarContrasena($contrasena)
{
    return password_hash($contrasena, PASSWORD_BCRYPT);
}

// Desactivar restricciones de claves foráneas para limpieza
ejecutarConsulta($pdo, "SET FOREIGN_KEY_CHECKS = 0");

// Limpiar tablas relevantes y reiniciar IDs con TRUNCATE
echo "Limpiando tablas...\n";
$tablas = [
    'usuarios_roles',
    'roles_permisos',
    'preferencias_usuarios',
    'usuarios',
    'permisos',
    'roles',
    'modulos'
];

foreach ($tablas as $tabla) {
    ejecutarConsulta($pdo, "TRUNCATE TABLE $tabla");
    echo "Tabla $tabla limpiada y IDs reiniciados.\n";
}

// Reactivar restricciones de claves foráneas
ejecutarConsulta($pdo, "SET FOREIGN_KEY_CHECKS = 1");

// 1. Poblar tabla modulos
echo "Poblando modulos...\n";
$modulos = [
    ['dashboard', 'Panel principal del sistema', 1],
    ['equipos', 'Gestión de equipos, máquinas, motores y equipos eléctricos', 1],
    ['componentes', 'Gestión de componentes de equipos', 1],
    ['mantenimientos', 'Gestión de mantenimientos', 1],
    ['mantenimientos.preventivo', 'Submódulo de mantenimiento preventivo', 1],
    ['mantenimientos.correctivo', 'Submódulo de mantenimiento correctivo', 1],
    ['mantenimientos.programado', 'Submódulo de mantenimiento programado (predictivo)', 1],
    ['administracion', 'Gestión administrativa', 1],
    ['administracion.usuarios', 'Submódulo de usuarios', 1],
    ['administracion.personal', 'Submódulo de personal', 1],
    ['administracion.roles_permisos', 'Submódulo de roles y permisos', 1],
    ['administracion.categorias', 'Submódulo de categorías de equipos', 1],
];

$modulo_ids = [];
foreach ($modulos as $modulo) {
    ejecutarConsulta($pdo, "INSERT INTO modulos (nombre, descripcion, esta_activo) VALUES (?, ?, ?)", $modulo);
    $modulo_ids[$modulo[0]] = ultimoId($pdo);
}

// 2. Poblar tabla permisos
echo "Poblando permisos...\n";
$permisos = [
    // Dashboard
    [$modulo_ids['dashboard'], 'dashboard.acceder', 'Permite acceder al panel principal'],
    [$modulo_ids['dashboard'], 'dashboard.ver', 'Permite ver el panel principal'],
    // Equipos
    [$modulo_ids['equipos'], 'equipos.acceder', 'Permite acceder al módulo de equipos'],
    [$modulo_ids['equipos'], 'equipos.ver', 'Permite ver la lista de equipos'],
    [$modulo_ids['equipos'], 'equipos.crear', 'Permite crear nuevos equipos'],
    [$modulo_ids['equipos'], 'equipos.editar', 'Permite editar equipos existentes'],
    [$modulo_ids['equipos'], 'equipos.eliminar', 'Permite eliminar equipos'],
    // Componentes
    [$modulo_ids['componentes'], 'componentes.acceder', 'Permite acceder al módulo de componentes'],
    [$modulo_ids['componentes'], 'componentes.ver', 'Permite ver la lista de componentes'],
    [$modulo_ids['componentes'], 'componentes.crear', 'Permite crear nuevos componentes'],
    [$modulo_ids['componentes'], 'componentes.editar', 'Permite editar componentes existentes'],
    [$modulo_ids['componentes'], 'componentes.eliminar', 'Permite eliminar componentes'],
    // Mantenimientos
    [$modulo_ids['mantenimientos'], 'mantenimientos.acceder', 'Permite acceder al módulo de mantenimientos'],
    [$modulo_ids['mantenimientos'], 'mantenimientos.ver', 'Permite ver todos los mantenimientos'],
    // Mantenimiento Preventivo
    [$modulo_ids['mantenimientos.preventivo'], 'mantenimientos.preventivo.acceder', 'Permite acceder al submódulo de mantenimiento preventivo'],
    [$modulo_ids['mantenimientos.preventivo'], 'mantenimientos.preventivo.ver', 'Permite ver mantenimientos preventivos'],
    [$modulo_ids['mantenimientos.preventivo'], 'mantenimientos.preventivo.crear', 'Permite crear mantenimientos preventivos'],
    [$modulo_ids['mantenimientos.preventivo'], 'mantenimientos.preventivo.editar', 'Permite editar mantenimientos preventivos'],
    // Mantenimiento Correctivo
    [$modulo_ids['mantenimientos.correctivo'], 'mantenimientos.correctivo.acceder', 'Permite acceder al submódulo de mantenimiento correctivo'],
    [$modulo_ids['mantenimientos.correctivo'], 'mantenimientos.correctivo.ver', 'Permite ver mantenimientos correctivos'],
    [$modulo_ids['mantenimientos.correctivo'], 'mantenimientos.correctivo.crear', 'Permite crear mantenimientos correctivos'],
    [$modulo_ids['mantenimientos.correctivo'], 'mantenimientos.correctivo.editar', 'Permite editar mantenimientos correctivos'],
    // Mantenimiento Programado
    [$modulo_ids['mantenimientos.programado'], 'mantenimientos.programado.acceder', 'Permite acceder al submódulo de mantenimiento programado (predictivo)'],
    [$modulo_ids['mantenimientos.programado'], 'mantenimientos.programado.ver', 'Permite ver mantenimientos programados'],
    [$modulo_ids['mantenimientos.programado'], 'mantenimientos.programado.crear', 'Permite crear mantenimientos programados'],
    [$modulo_ids['mantenimientos.programado'], 'mantenimientos.programado.editar', 'Permite editar mantenimientos programados'],
    // Administracion
    [$modulo_ids['administracion'], 'administracion.acceder', 'Permite acceder al módulo de administración'],
    [$modulo_ids['administracion'], 'administracion.ver', 'Permite ver el panel de administración'],
    // Administracion Usuarios
    [$modulo_ids['administracion.usuarios'], 'administracion.usuarios.acceder', 'Permite acceder al submódulo de usuarios'],
    [$modulo_ids['administracion.usuarios'], 'administracion.usuarios.ver', 'Permite ver la lista de usuarios'],
    [$modulo_ids['administracion.usuarios'], 'administracion.usuarios.crear', 'Permite crear nuevos usuarios'],
    [$modulo_ids['administracion.usuarios'], 'administracion.usuarios.editar', 'Permite editar usuarios existentes'],
    // Administracion Personal
    [$modulo_ids['administracion.personal'], 'administracion.personal.acceder', 'Permite acceder al submദsubmódulo de personal'],
    [$modulo_ids['administracion.personal'], 'administracion.personal.ver', 'Permite ver la lista de personal'],
    [$modulo_ids['administracion.personal'], 'administracion.personal.crear', 'Permite crear nuevo personal'],
    [$modulo_ids['administracion.personal'], 'administracion.personal.editar', 'Permite editar personal existente'],
    // Administracion Roles y Permisos
    [$modulo_ids['administracion.roles_permisos'], 'administracion.roles_permisos.acceder', 'Permite acceder al submódulo de roles y permisos'],
    [$modulo_ids['administracion.roles_permisos'], 'administracion.roles_permisos.ver', 'Permite ver roles y permisos'],
    [$modulo_ids['administracion.roles_permisos'], 'administracion.roles_permisos.crear', 'Permite crear roles y permisos'],
    [$modulo_ids['administracion.roles_permisos'], 'administracion.roles_permisos.editar', 'Permite editar roles y permisos'],
    // Administracion Categorías
    [$modulo_ids['administracion.categorias'], 'administracion.categorias.acceder', 'Permite acceder al submódulo de categorías'],
    [$modulo_ids['administracion.categorias'], 'administracion.categorias.ver', 'Permite ver la lista de categorías'],
    [$modulo_ids['administracion.categorias'], 'administracion.categorias.crear', 'Permite crear nuevas categorías'],
    [$modulo_ids['administracion.categorias'], 'administracion.categorias.editar', 'Permite editar categorías existentes'],
    [$modulo_ids['administracion.categorias'], 'administracion.categorias.eliminar', 'Permite eliminar categorías existentes'],
];

$permiso_ids = [];
foreach ($permisos as $permiso) {
    ejecutarConsulta($pdo, "INSERT INTO permisos (modulo_id, nombre, descripcion) VALUES (?, ?, ?)", $permiso);
    $permiso_ids[$permiso[1]] = ultimoId($pdo);
}

// 3. Poblar tabla roles
echo "Poblando roles...\n";
$roles = [
    ['superadmin', 'Rol con acceso completo a todas las funcionalidades del sistema', 1],
    ['admin', 'Rol con acceso completo inicial, configurable posteriormente', 1],
    ['jefe', 'Rol para supervisores con acceso a equipos y mantenimientos', 1],
    ['invitado', 'Rol con permisos limitados, solo visualización', 1],
];

$rol_ids = [];
foreach ($roles as $rol) {
    ejecutarConsulta($pdo, "INSERT INTO roles (nombre, descripcion, esta_activo) VALUES (?, ?, ?)", $rol);
    $rol_ids[$rol[0]] = ultimoId($pdo);
}

// 4. Poblar tabla roles_permisos
echo "Poblando roles_permisos...\n";

// Superadmin: Todos los permisos
foreach (array_keys($permiso_ids) as $permiso_nombre) {
    ejecutarConsulta($pdo, "INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (?, ?)", [$rol_ids['superadmin'], $permiso_ids[$permiso_nombre]]);
}

// Admin: Todos los permisos
foreach (array_keys($permiso_ids) as $permiso_nombre) {
    ejecutarConsulta($pdo, "INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (?, ?)", [$rol_ids['admin'], $permiso_ids[$permiso_nombre]]);
}

// Jefe: Permisos para equipos, componentes, mantenimientos y solo ver categorías
$permisos_jefe = [
    'dashboard.acceder',
    'dashboard.ver',
    'equipos.acceder',
    'equipos.ver',
    'equipos.crear',
    'equipos.editar',
    'equipos.eliminar',
    'componentes.acceder',
    'componentes.ver',
    'componentes.crear',
    'componentes.editar',
    'componentes.eliminar',
    'mantenimientos.acceder',
    'mantenimientos.ver',
    'mantenimientos.preventivo.acceder',
    'mantenimientos.preventivo.ver',
    'mantenimientos.preventivo.crear',
    'mantenimientos.preventivo.editar',
    'mantenimientos.correctivo.acceder',
    'mantenimientos.correctivo.ver',
    'mantenimientos.correctivo.crear',
    'mantenimientos.correctivo.editar',
    'mantenimientos.programado.acceder',
    'mantenimientos.programado.ver',
    'mantenimientos.programado.crear',
    'mantenimientos.programado.editar',
    'administracion.categorias.acceder',
    'administracion.categorias.ver'
];

foreach ($permisos_jefe as $permiso_nombre) {
    ejecutarConsulta($pdo, "INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (?, ?)", [$rol_ids['jefe'], $permiso_ids[$permiso_nombre]]);
}

// Invitado: Solo visualización
$permisos_invitado = [
    'dashboard.acceder',
    'dashboard.ver',
    'equipos.acceder',
    'equipos.ver',
    'componentes.acceder',
    'componentes.ver',
    'mantenimientos.acceder',
    'mantenimientos.ver',
    'mantenimientos.preventivo.acceder',
    'mantenimientos.preventivo.ver',
    'mantenimientos.correctivo.acceder',
    'mantenimientos.correctivo.ver',
    'mantenimientos.programado.acceder',
    'mantenimientos.programado.ver',
    'administracion.categorias.acceder',
    'administracion.categorias.ver'
];

foreach ($permisos_invitado as $permiso_nombre) {
    ejecutarConsulta($pdo, "INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (?, ?)", [$rol_ids['invitado'], $permiso_ids[$permiso_nombre]]);
}

// 5. Poblar tabla usuarios (1 usuario por tipo)
echo "Poblando usuarios...\n";

// Insertar superadmin con creado_por NULL
$superadmin = ['superadmin', encriptarContrasena('password123'), 'Super Administrador', 'superadmin@sigesman.com', '12345678', '987654321', 'Av. Principal 123', 'Administración', null, null, null, 1];
ejecutarConsulta($pdo, "INSERT INTO usuarios (username, contrasena, nombre_completo, correo, dni, telefono, direccion, area, fotografia, creado_por, token_recordatorio, esta_activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $superadmin);
$superadmin_id = ultimoId($pdo);

// Insertar los demás usuarios
$usuarios = [
    ['admin1', encriptarContrasena('password123'), 'Admin Uno', 'admin1@sigesman.com', '87654321', '912345678', 'Calle Secundaria 456', 'Administración', null, $superadmin_id, null, 1],
    ['jefe1', encriptarContrasena('password123'), 'Jefe Operaciones', 'jefe1@sigesman.com', '45678912', '923456789', 'Av. Mina 789', 'Operaciones', null, $superadmin_id, null, 1],
    ['invitado1', encriptarContrasena('password123'), 'Invitado Pruebas', 'invitado1@sigesman.com', '32198765', '945678901', null, 'Externo', null, $superadmin_id, null, 1],
];

$usuario_ids = [$superadmin_id];
foreach ($usuarios as $usuario) {
    ejecutarConsulta($pdo, "INSERT INTO usuarios (username, contrasena, nombre_completo, correo, dni, telefono, direccion, area, fotografia, creado_por, token_recordatorio, esta_activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $usuario);
    $usuario_ids[] = ultimoId($pdo);
}

// 6. Poblar tabla usuarios_roles
echo "Poblando usuarios_roles...\n";
$usuarios_roles = [
    [$usuario_ids[0], $rol_ids['superadmin']],
    [$usuario_ids[1], $rol_ids['admin']],
    [$usuario_ids[2], $rol_ids['jefe']],
    [$usuario_ids[3], $rol_ids['invitado']],
];

foreach ($usuarios_roles as $ur) {
    ejecutarConsulta($pdo, "INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (?, ?)", $ur);
}

// 7. Poblar tabla preferencias_usuarios
echo "Poblando preferencias_usuarios...\n";
$preferencias = [
    // Superadmin: Tema oscuro, diseño moderno, colores personalizados
    [
        $usuario_ids[0], // usuario_id
        'oscuro',        // tema
        'es',            // idioma
        'modern',        // navbar_design
        '#2c3e50',       // navbar_bg_color
        '#ecf0f1',       // navbar_text_color
        '#3498db',       // navbar_active_bg_color
        '#2c3e50',       // topbar_bg_color
        '#ecf0f1',       // topbar_text_color
        'dashboard',     // pagina_inicio
        25               // elementos_por_pagina
    ],
    // Admin1: Tema claro, diseño clásico, colores predeterminados
    [
        $usuario_ids[1],
        'claro',
        'en',
        'classic',
        '#1571b0',
        '#ffffff',
        '#ffffff',
        '#1571b0',
        '#ffffff',
        'equipos',
        50
    ],
    // Jefe1: Tema auto, diseño minimal, colores vibrantes
    [
        $usuario_ids[2],
        'auto',
        'es',
        'minimal',
        '#e74c3c',
        '#ffffff',
        '#f1c40f',
        '#e74c3c',
        '#ffffff',
        'mantenimientos',
        10
    ],
    // Invitado1: Tema claro, diseño default, colores suaves
    [
        $usuario_ids[3],
        'claro',
        'es',
        'default',
        '#3498db',
        '#ffffff',
        '#ffffff',
        '#3498db',
        '#ffffff',
        'dashboard',
        100
    ]
];

foreach ($preferencias as $pref) {
    ejecutarConsulta(
        $pdo,
        "INSERT INTO preferencias_usuarios (
            usuario_id, tema, idioma, navbar_design, navbar_bg_color, navbar_text_color,
            navbar_active_bg_color, topbar_bg_color, topbar_text_color, pagina_inicio,
            elementos_por_pagina
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        $pref
    );
}

echo "\n=== SEEDER COMPLETADO EXITOSAMENTE ===\n";
echo "✅ Módulos creados: " . count($modulos) . "\n";
echo "✅ Permisos creados: " . count($permisos) . "\n";
echo "✅ Roles creados: " . count($roles) . "\n";
echo "✅ Usuarios creados: " . count($usuario_ids) . "\n";
echo "✅ Preferencias de usuarios creadas: " . count($preferencias) . "\n";
echo "\n🎉 Base de datos lista para usar!\n";
