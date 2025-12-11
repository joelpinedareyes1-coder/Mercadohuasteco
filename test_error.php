<?php
// Activar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP funciona correctamente<br>";

// Probar conexión a BD
try {
    require_once 'config.php';
    echo "2. config.php cargado correctamente<br>";
    echo "3. Conexión a BD: OK<br>";
} catch (Exception $e) {
    echo "ERROR en config.php: " . $e->getMessage() . "<br>";
}

// Probar funciones
try {
    require_once 'funciones_config.php';
    echo "4. funciones_config.php cargado correctamente<br>";
} catch (Exception $e) {
    echo "ERROR en funciones_config.php: " . $e->getMessage() . "<br>";
}

// Verificar sesión
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "5. Sesión activa<br>";
} else {
    echo "5. Sesión NO activa<br>";
}

// Verificar si está logueado
if (function_exists('esta_logueado')) {
    if (esta_logueado()) {
        echo "6. Usuario logueado: Sí (ID: " . $_SESSION['user_id'] . ")<br>";
    } else {
        echo "6. Usuario logueado: No<br>";
    }
} else {
    echo "6. Función esta_logueado() NO existe<br>";
}

// Verificar función esPremiumActivo
if (function_exists('esPremiumActivo')) {
    echo "7. Función esPremiumActivo() existe<br>";
} else {
    echo "7. Función esPremiumActivo() NO existe<br>";
}

// Verificar función obtenerInfoUsuario
if (function_exists('obtenerInfoUsuario')) {
    echo "8. Función obtenerInfoUsuario() existe<br>";
} else {
    echo "8. Función obtenerInfoUsuario() NO existe<br>";
}

echo "<br><strong>✅ Todas las pruebas completadas</strong>";
?>
