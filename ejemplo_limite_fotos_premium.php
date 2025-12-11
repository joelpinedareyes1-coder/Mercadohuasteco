<?php
/**
 * EJEMPLO: Cómo implementar límite de fotos según membresía Premium
 * 
 * Este archivo muestra el código que debes agregar a galeria_vendedor.php
 * para limitar la cantidad de fotos según si el usuario es Premium o no.
 * 
 * INSTRUCCIONES:
 * 1. Abre galeria_vendedor.php
 * 2. Busca la sección donde se obtiene la información de la tienda
 * 3. Agrega el código marcado como "AGREGAR AQUÍ"
 */

// ============================================================================
// PASO 1: Obtener información de membresía del usuario
// ============================================================================
// AGREGAR DESPUÉS DE: $tienda_id = $tienda['id'];

// Obtener información de membresía del usuario
$stmt_usuario = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$vendedor_id]);
$usuario_info = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

// Establecer límites según membresía
$es_premium = isset($usuario_info['es_premium']) && $usuario_info['es_premium'] == 1;
$limite_fotos = $es_premium ? 10 : 2; // Premium: 10 fotos, Normal: 2 fotos

// Obtener cantidad actual de fotos
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
$stmt_count->execute([$tienda_id]);
$total_fotos_actual = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// ============================================================================
// PASO 2: Validar límite antes de subir foto
// ============================================================================
// AGREGAR AL INICIO DE: if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_foto']))

// Verificar límite de fotos
if ($total_fotos_actual >= $limite_fotos) {
    $error = "Has alcanzado el límite de $limite_fotos fotos para tu membresía. ";
    if (!$es_premium) {
        $error .= "¡Actualiza a Premium para subir hasta 10 fotos!";
    }
} else {
    // ... resto del código de subida de foto
}

// ============================================================================
// PASO 3: Mostrar información de límite en la interfaz
// ============================================================================
// AGREGAR EN LA SECCIÓN DEL FORMULARIO DE SUBIDA, DESPUÉS DEL TÍTULO

?>
<div class="alert <?php echo $es_premium ? 'alert-success' : 'alert-info'; ?> mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <?php if ($es_premium): ?>
                <i class="fas fa-star text-warning"></i>
                <strong>Membresía Premium</strong>
            <?php else: ?>
                <i class="fas fa-info-circle"></i>
                <strong>Membresía Normal</strong>
            <?php endif; ?>
        </div>
        <div>
            <span class="badge <?php echo $total_fotos_actual >= $limite_fotos ? 'bg-danger' : 'bg-primary'; ?>">
                <?php echo $total_fotos_actual; ?> / <?php echo $limite_fotos; ?> fotos
            </span>
        </div>
    </div>
    
    <?php if (!$es_premium): ?>
        <hr>
        <small>
            <i class="fas fa-arrow-up"></i>
            ¿Quieres subir más fotos? 
            <strong>Actualiza a Premium</strong> y obtén hasta 10 fotos en tu galería.
        </small>
    <?php endif; ?>
</div>

<?php
// ============================================================================
// PASO 4: Deshabilitar botón de subida si se alcanzó el límite
// ============================================================================
// MODIFICAR EL BOTÓN DE SUBIR FOTO

?>
<button type="submit" name="subir_foto" class="btn-modern btn-primary w-100" 
        <?php echo $total_fotos_actual >= $limite_fotos ? 'disabled' : ''; ?>>
    <i class="fas fa-upload"></i>
    <?php if ($total_fotos_actual >= $limite_fotos): ?>
        Límite Alcanzado
    <?php else: ?>
        Subir Foto (<?php echo $limite_fotos - $total_fotos_actual; ?> disponibles)
    <?php endif; ?>
</button>

<?php
// ============================================================================
// CÓDIGO COMPLETO PARA COPIAR Y PEGAR
// ============================================================================
?>

<!-- 
RESUMEN DE CAMBIOS EN galeria_vendedor.php:

1. Después de obtener $tienda_id, agregar:
-->
<?php
// Obtener información de membresía
$stmt_usuario = $pdo->prepare("SELECT es_premium FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$vendedor_id]);
$usuario_info = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

$es_premium = isset($usuario_info['es_premium']) && $usuario_info['es_premium'] == 1;
$limite_fotos = $es_premium ? 20 : 5;

$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
$stmt_count->execute([$tienda_id]);
$total_fotos_actual = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!-- 
2. Al inicio del procesamiento de subida de foto, agregar:
-->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_foto'])) {
    // Verificar límite PRIMERO
    if ($total_fotos_actual >= $limite_fotos) {
        $error = "Has alcanzado el límite de $limite_fotos fotos. ";
        if (!$es_premium) {
            $error .= "¡Actualiza a Premium para subir hasta 10 fotos!";
        }
    } else {
        // Código original de subida de foto aquí...
        $descripcion = limpiar_entrada($_POST['descripcion']);
        // ... resto del código
    }
}
?>

<!-- 
3. En el HTML del formulario, agregar el alert de información:
-->
<div class="alert <?php echo $es_premium ? 'alert-success' : 'alert-info'; ?> mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <?php if ($es_premium): ?>
                <i class="fas fa-star text-warning"></i> <strong>Premium</strong>
            <?php else: ?>
                <i class="fas fa-info-circle"></i> <strong>Normal</strong>
            <?php endif; ?>
        </div>
        <div>
            <span class="badge <?php echo $total_fotos_actual >= $limite_fotos ? 'bg-danger' : 'bg-primary'; ?>">
                <?php echo $total_fotos_actual; ?> / <?php echo $limite_fotos; ?> fotos
            </span>
        </div>
    </div>
    <?php if (!$es_premium): ?>
        <hr>
        <small>¿Quieres más fotos? <strong>Actualiza a Premium</strong> para 10 fotos.</small>
    <?php endif; ?>
</div>

<!-- 
4. Modificar el botón de subir:
-->
<button type="submit" name="subir_foto" class="btn-modern btn-primary w-100" 
        <?php echo $total_fotos_actual >= $limite_fotos ? 'disabled' : ''; ?>>
    <i class="fas fa-upload"></i>
    <?php if ($total_fotos_actual >= $limite_fotos): ?>
        Límite Alcanzado
    <?php else: ?>
        Subir Foto (<?php echo $limite_fotos - $total_fotos_actual; ?> disponibles)
    <?php endif; ?>
</button>
