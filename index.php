<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (isset($_SESSION['usuario_id'])) {
    // Si está logueado, redirigir al dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // Si no está logueado, redirigir al login
    header("Location: login.php");
    exit;
}
