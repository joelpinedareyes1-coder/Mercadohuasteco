<?php
// ----- INICIO DE QUITAR_FAVORITO.PHP -----

// 1. INICIAR LA SESIÓN (¡SÚPER IMPORTANTE!)
session_start();

// 2. REVISAR SI EL USUARIO ESTÁ LOGUEADO
// (Si no, lo sacamos. Ajusta 'user_id' al nombre real de tu variable de sesión)
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// 3. REVISAR SI NOS MANDARON UN ID DE TIENDA
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // No nos mandaron ID, regresamos a favoritos
    header("Location: mis_favoritos.php");
    exit();
}

// 4. INCLUIR TU ARCHIVO DE CONEXIÓN A LA BD
// (Asegúrate de que la ruta sea correcta)
require_once 'config.php';

// 5. OBTENER LOS DATOS DE FORMA SEGURA
$id_tienda = (int)$_GET['id'];
$id_usuario = (int)$_SESSION['user_id']; // <-- ¡LA PIEZA CLAVE QUE SEGURO LE FALTABA A KIRO!

// 6. PREPARAR Y EJECUTAR EL DELETE (LA FORMA CORRECTA)
try {
    // Esta es la consulta SQL que Kiro no pudo hacer:
    $sql = "DELETE FROM favoritos WHERE tienda_id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql); // Asumo que tu conexión se llama $pdo o $conn
    
    // Ejecutamos la consulta con los dos IDs
    $stmt->execute([$id_tienda, $id_usuario]);
    
} catch (PDOException $e) {
    // Manejo de errores (opcional pero recomendado)
    // echo "Error al eliminar: " . $e->getMessage();
    // Por ahora, solo regresamos...
}

// 7. REGRESAR AL USUARIO A LA PÁGINA DE FAVORITOS
// (Ya sea que funcionó o no, el usuario regresa y verá que el ítem ya no está)
header("Location: mis_favoritos.php");
exit();

// ----- FIN DE QUITAR_FAVORITO.PHP -----
?>