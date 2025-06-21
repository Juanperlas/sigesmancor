<?php
require_once '../../db/funciones.php';
require_once '../../db/conexion.php';

header('Content-Type: application/json');

// Verificar autenticación
verificarAutenticacion();

try {
    $conexion = new Conexion();
    $usuario_id = getUsuarioId();
    
    // Obtener preferencias del usuario
    $preferencias = $conexion->selectOne(
        "SELECT * FROM preferencias_usuarios WHERE usuario_id = ?",
        [$usuario_id]
    );
    
    if ($preferencias) {
        echo json_encode(['success' => true, 'data' => $preferencias]);
    } else {
        // Devolver valores por defecto - AGREGADO navbar_active_text_color
        $defaults = [
            'tema' => 'claro',
            'idioma' => 'es',
            'navbar_design' => 'default',
            'navbar_bg_color' => '#1571b0',
            'navbar_text_color' => '#ffffff',
            'navbar_active_bg_color' => '#125a8a',
            'navbar_active_text_color' => '#ffffff', // NUEVO
            'topbar_bg_color' => '#ffffff',
            'topbar_text_color' => '#333333',
            'pagina_inicio' => 'dashboard',
            'elementos_por_pagina' => 10
        ];
        echo json_encode(['success' => true, 'data' => $defaults]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar preferencias: ' . $e->getMessage()]);
}
?>