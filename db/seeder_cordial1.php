<?php

// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'appsalud_db_sigesmancor';
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

// Desactivar restricciones de claves foráneas para limpieza
ejecutarConsulta($pdo, "SET FOREIGN_KEY_CHECKS = 0");

// Limpiar tablas relevantes y reiniciar IDs con TRUNCATE
echo "Limpiando tablas...\n";
$tablas = [
    'equipos',
    'categorias_equipos'
];

foreach ($tablas as $tabla) {
    ejecutarConsulta($pdo, "TRUNCATE TABLE $tabla");
    echo "Tabla $tabla limpiada y IDs reiniciados.\n";
}

// Reactivar restricciones de claves foráneas
ejecutarConsulta($pdo, "SET FOREIGN_KEY_CHECKS = 1");

// 1. Poblar tabla categorias_equipos
echo "Poblando categorias_equipos...\n";
$categorias = [
    ['Filtración', 'Equipos para filtración de agua'],
    ['Tratamiento Químico', 'Equipos para tratamiento químico del agua'],
    ['Almacenamiento', 'Tanques para almacenamiento de agua'],
    ['Bombeo', 'Equipos de bombeo de agua'],
    ['Ósmosis', 'Equipos de ósmosis inversa'],
    ['Control', 'Equipos de control y medición'],
];

$categoria_ids = [];
foreach ($categorias as $categoria) {
    ejecutarConsulta($pdo, "INSERT INTO categorias_equipos (nombre, descripcion) VALUES (?, ?)", $categoria);
    $categoria_ids[$categoria[0]] = ultimoId($pdo);
}

// 2. Poblar tabla equipos
echo "Poblando equipos...\n";
$equipos = [
    [
        $categoria_ids['Ósmosis'],
        'EQP-001',
        'Máquina de Ósmosis',
        'ósmosis inversa',
        null,
        'SOBLE PASO',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Purifica agua eliminando partículas en suspensión',
        null,
        'horas'
    ],
    [
        $categoria_ids['Filtración'],
        'EQP-002',
        'Filtro Multimedia',
        'filtro',
        null,
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        null,
        null,
        'horas'
    ],
    [
        $categoria_ids['Tratamiento Químico'],
        'EQP-003',
        'Ablandador',
        'suavizador',
        null,
        'SUAVISADOR',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Suaviza agua mediante resina y grava de cuarzo',
        null,
        'horas'
    ],
    [
        $categoria_ids['Tratamiento Químico'],
        'EQP-004',
        'Tanque de Salmuera',
        'tanque',
        null,
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        null,
        null,
        'horas'
    ],
    [
        $categoria_ids['Filtración'],
        'EQP-005',
        'Filtro de Carbón Activado',
        'filtro',
        null,
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        null,
        null,
        'horas'
    ],
    [
        $categoria_ids['Almacenamiento'],
        'EQP-006',
        'Tanque Hidroneumático',
        'tanque',
        null,
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        null,
        null,
        'horas'
    ],
    [
        $categoria_ids['Filtración'],
        'EQP-007',
        'Filtro Ultravioleta',
        'filtro',
        null,
        'HIBRIDO',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Elimina microorganismos mediante luz UV',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-008',
        'Presostato',
        'control',
        'SQUARE D',
        '9013 FSG-2',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Controla y supervisa la presión del fluido',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-009',
        'Manómetro',
        'control',
        'ASHCROFT',
        '316',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Mide la presión de un fluido',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-010',
        'Válvula de Alivio',
        'control',
        null,
        'ROSCADO',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Alivia presión excesiva',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-011',
        'Sensor de Nivel',
        'control',
        'VARIADO',
        'INTERRUPTOR',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Mide la altura del líquido',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-012',
        'Flujómetro',
        'control',
        'HYDRONIX',
        'PANEL/LINEAL',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Mide el caudal del líquido',
        null,
        'horas'
    ],
    [
        $categoria_ids['Bombeo'],
        'EQP-013',
        'Electrobomba de Agua Dura',
        'bomba',
        'VARIADO',
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Aspira e impulsa agua dura',
        null,
        'horas'
    ],
    [
        $categoria_ids['Bombeo'],
        'EQP-014',
        'Electrobomba de Anillos de Recirculación',
        'bomba',
        'VARIADO',
        'MULTIETAPA',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Desarrolla altas presiones para recirculación',
        null,
        'horas'
    ],
    [
        $categoria_ids['Control'],
        'EQP-015',
        'Tablero Eléctrico',
        'control',
        'VARIADO',
        'ON/OF',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Controla la instalación eléctrica',
        null,
        'horas'
    ],
    [
        $categoria_ids['Almacenamiento'],
        'EQP-016',
        'Tanque Cónico de Agua Tratada',
        'tanque',
        'VARIADO',
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Almacena agua tratada',
        null,
        'horas'
    ],
    [
        $categoria_ids['Bombeo'],
        'EQP-017',
        'Anillo de Recirculación',
        'sistema',
        null,
        null,
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Sistema de recirculación de agua',
        null,
        'horas'
    ],
    [
        $categoria_ids['Filtración'],
        'EQP-018',
        'Filtro de Sedimento',
        'filtro',
        'VARIOS',
        '0.2/0.45/5 Y 50',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Remueve sedimentos del agua',
        null,
        'horas'
    ],
    [
        $categoria_ids['Tratamiento Químico'],
        'EQP-019',
        'Kit de Reactivos',
        'reactivo',
        'QUIMICA CLINICA',
        'EVALUAR',
        null,
        null,
        null,
        'Planta de Agua',
        'activo',
        0,
        0,
        null,
        0,
        0,
        0,
        'Analiza componentes del agua',
        null,
        'horas'
    ],
];

foreach ($equipos as $equipo) {
    ejecutarConsulta(
        $pdo,
        "INSERT INTO equipos (categoria_id, codigo, nombre, tipo_equipo, marca, modelo, numero_serie, capacidad, fase, ubicacion, estado, orometro_actual, anterior_orometro, proximo_orometro, limite, notificacion, mantenimiento, observaciones, imagen, tipo_orometro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        $equipo
    );
}

echo "\n=== SEEDER COMPLETADO EXITOSAMENTE ===\n";
echo "✅ Categorías creadas: " . count($categorias) . "\n";
echo "✅ Equipos creados: " . count($equipos) . "\n";
echo "\n🎉 Tablas de categorías y equipos listas para usar!\n";
