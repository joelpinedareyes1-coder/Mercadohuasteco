# 🔗 Agregar Enlace a Reportes en Dashboard Admin

## Instrucciones

Para que puedas acceder fácilmente al panel de reportes desde tu dashboard de administrador, agrega este código en `dashboard_admin.php`:

### Opción 1: Tarjeta en el Dashboard

Busca la sección donde están las tarjetas de estadísticas y agrega:

```html
<!-- Tarjeta de Reportes -->
<div class="col-md-6 col-lg-3 mb-4">
    <div class="card text-center h-100 shadow-sm">
        <div class="card-body">
            <i class="bi bi-flag-fill text-danger" style="font-size: 3rem;"></i>
            <h5 class="card-title mt-3">Reportes</h5>
            <?php
            // Obtener número de reportes pendientes
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM reportes_tienda WHERE estado = 'pendiente'");
                $reportes_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch(PDOException $e) {
                $reportes_pendientes = 0;
            }
            ?>
            <p class="card-text display-4 text-danger"><?php echo $reportes_pendientes; ?></p>
            <p class="text-muted">Pendientes</p>
            <a href="admin_ver_reportes.php" class="btn btn-danger">
                <i class="bi bi-eye me-2"></i>Ver Reportes
            </a>
        </div>
    </div>
</div>
```

### Opción 2: Enlace en el Menú de Navegación

Si tienes un menú lateral o superior, agrega:

```html
<li class="nav-item">
    <a class="nav-link" href="admin_ver_reportes.php">
        <i class="bi bi-flag-fill me-2"></i>
        Reportes de Tiendas
        <?php if ($reportes_pendientes > 0): ?>
            <span class="badge bg-danger"><?php echo $reportes_pendientes; ?></span>
        <?php endif; ?>
    </a>
</li>
```

### Opción 3: Botón de Acceso Rápido

En cualquier parte visible del dashboard:

```html
<a href="admin_ver_reportes.php" class="btn btn-danger btn-lg">
    <i class="bi bi-flag-fill me-2"></i>
    Gestionar Reportes
    <?php if ($reportes_pendientes > 0): ?>
        <span class="badge bg-light text-danger ms-2"><?php echo $reportes_pendientes; ?></span>
    <?php endif; ?>
</a>
```

---

## Notificación Visual de Reportes Pendientes

Para mostrar una alerta cuando hay reportes pendientes, agrega al inicio del dashboard:

```php
<?php
// Verificar reportes pendientes
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reportes_tienda WHERE estado = 'pendiente'");
    $reportes_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) {
    $reportes_pendientes = 0;
}
?>

<?php if ($reportes_pendientes > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>¡Atención!</strong> Tienes <?php echo $reportes_pendientes; ?> reporte(s) pendiente(s) de revisar.
        <a href="admin_ver_reportes.php" class="alert-link">Ver reportes</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

---

## Ejemplo Completo de Sección en Dashboard

```html
<div class="container mt-4">
    <div class="row">
        <!-- Otras tarjetas existentes... -->
        
        <!-- Tarjeta de Reportes -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-danger h-100 shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-flag-fill me-2"></i>Sistema de Reportes
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php
                    try {
                        $stmt = $pdo->query("
                            SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                                SUM(CASE WHEN estado = 'resuelto' THEN 1 ELSE 0 END) as resueltos
                            FROM reportes_tienda
                        ");
                        $stats_reportes = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {
                        $stats_reportes = ['total' => 0, 'pendientes' => 0, 'resueltos' => 0];
                    }
                    ?>
                    
                    <div class="mb-3">
                        <h2 class="display-3 text-danger mb-0">
                            <?php echo $stats_reportes['pendientes']; ?>
                        </h2>
                        <p class="text-muted">Reportes Pendientes</p>
                    </div>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <p class="mb-0 text-muted small">Total</p>
                            <p class="h4 mb-0"><?php echo $stats_reportes['total']; ?></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-0 text-muted small">Resueltos</p>
                            <p class="h4 mb-0 text-success"><?php echo $stats_reportes['resueltos']; ?></p>
                        </div>
                    </div>
                    
                    <a href="admin_ver_reportes.php" class="btn btn-danger w-100">
                        <i class="bi bi-eye me-2"></i>Gestionar Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## Acceso Directo desde URL

También puedes acceder directamente a:
```
http://tu-dominio.com/admin_ver_reportes.php
```

¡Listo! Ahora tendrás acceso fácil al sistema de reportes desde tu panel de administración. 🎉
