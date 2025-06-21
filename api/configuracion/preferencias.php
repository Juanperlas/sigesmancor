<?php
require_once '../../db/funciones.php';
require_once '../../db/conexion.php';

header('Content-Type: application/json');

// Verificar autenticación
verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $conexion = new Conexion();
    $usuario_id = getUsuarioId();
    
    // Log de datos recibidos
    error_log("POST data recibida: " . json_encode($_POST));
    
    // Datos a guardar - AGREGADO navbar_active_text_color
    $datos = [
        'tema' => $_POST['tema'] ?? 'claro',
        'idioma' => $_POST['idioma'] ?? 'es',
        'navbar_design' => $_POST['navbar_design'] ?? 'default',
        'navbar_bg_color' => $_POST['navbar_bg_color'] ?? null,
        'navbar_text_color' => $_POST['navbar_text_color'] ?? null,
        'navbar_active_bg_color' => $_POST['navbar_active_bg_color'] ?? null,
        'navbar_active_text_color' => $_POST['navbar_active_text_color'] ?? null, // NUEVO
        'topbar_bg_color' => $_POST['topbar_bg_color'] ?? null,
        'topbar_text_color' => $_POST['topbar_text_color'] ?? null,
        'pagina_inicio' => $_POST['pagina_inicio'] ?? 'dashboard',
        'elementos_por_pagina' => intval($_POST['elementos_pagina'] ?? 10)
    ];
    
    // Log para debug
    error_log("Guardando preferencias para usuario $usuario_id: " . json_encode($datos));
    
    // Verificar si ya existe configuración para este usuario
    $existente = $conexion->selectOne(
        "SELECT id FROM preferencias_usuarios WHERE usuario_id = ?",
        [$usuario_id]
    );
    
    if ($existente) {
        // Actualizar
        $resultado = $conexion->update(
            'preferencias_usuarios',
            $datos,
            'usuario_id = ?',
            [$usuario_id]
        );
        error_log("Actualización resultado: " . ($resultado ? 'éxito' : 'fallo'));
    } else {
        // Insertar
        $datos['usuario_id'] = $usuario_id;
        $resultado = $conexion->insert('preferencias_usuarios', $datos);
        error_log("Inserción resultado ID: $resultado");
    }
    
    // Verificar que se guardó correctamente
    $verificacion = $conexion->selectOne(
        "SELECT * FROM preferencias_usuarios WHERE usuario_id = ?",
        [$usuario_id]
    );
    error_log("Datos guardados verificación: " . json_encode($verificacion));
    
    echo json_encode(['success' => true, 'message' => 'Preferencias guardadas correctamente', 'data' => $verificacion]);
    
} catch (Exception $e) {
    error_log("Error guardando preferencias: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar preferencias: ' . $e->getMessage()]);
}
?>