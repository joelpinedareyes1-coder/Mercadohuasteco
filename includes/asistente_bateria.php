<?php
/**
 * Inclusión del Asistente Batería
 * Solo se incluye en páginas permitidas
 */

// Lista de páginas donde NO debe aparecer el asistente
$paginas_excluidas = [
    'auth.php',
    'dashboard_admin.php',
    'gestionar_usuarios.php',
    'gestionar_tiendas.php',
    'moderar_reseñas.php',
    'reportes.php'
];

// Obtener el nombre del archivo actual
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Solo incluir si no está en la lista de excluidas
if (!in_array($pagina_actual, $paginas_excluidas)): ?>
    <!-- Asistente Batería CSS -->
    <link href="css/bateria_asistente.css" rel="stylesheet">
<?php endif; ?>