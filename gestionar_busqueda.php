<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$mensaje = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion === 'agregar_sinonimo') {
            $termino_principal = limpiar_entrada($_POST['termino_principal']);
            $sinonimo = limpiar_entrada($_POST['sinonimo']);
            $categoria = limpiar_entrada($_POST['categoria']);
            
            $stmt = $pdo->prepare("INSERT INTO sinonimos (termino_principal, sinonimo, categoria) VALUES (?, ?, ?)");
            $stmt->execute([$termino_principal, $sinonimo, $categoria]);
            $mensaje = "Sinónimo agregado exitosamente.";
            
        } elseif ($accion === 'agregar_categoria_termino') {
            $categoria = limpiar_entrada($_POST['categoria']);
            $termino = limpiar_entrada($_POST['termino_relacionado']);
            $relevancia = (int)$_POST['relevancia'];
            
            $stmt = $pdo->prepare("INSERT INTO categoria_terminos (categoria, termino_relacionado, relevancia) VALUES (?, ?, ?)");
            $stmt->execute([$categoria, $termino, $relevancia]);
            $mensaje = "Término relacionado agregado exitosamente.";
            
        } elseif ($accion === 'eliminar_sinonimo') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM sinonimos WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Sinónimo eliminado exitosamente.";
            
        } elseif ($accion === 'eliminar_categoria_termino') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM categoria_terminos WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Término relacionado eliminado exitosamente.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener sinónimos
try {
    $stmt = $pdo->prepare("SELECT * FROM sinonimos WHERE activo = 1 ORDER BY categoria, termino_principal");
    $stmt->execute();
    $sinonimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sinonimos = [];
}

// Obtener términos de categorías
try {
    $stmt = $pdo->prepare("SELECT * FROM categoria_terminos WHERE activo = 1 ORDER BY categoria, relevancia DESC");
    $stmt->execute();
    $categoria_terminos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoria_terminos = [];
}

// Obtener categorías disponibles
$categorias_disponibles = [
    'Servicios', 'Productos', 'Restaurantes', 'Salud y Belleza', 
    'Tecnología', 'Educación', 'Entretenimiento', 'General'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Búsqueda Inteligente - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .header {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-search"></i> Gestionar Búsqueda Inteligente
                    </h1>
                    <p class="mb-0 opacity-75">Configurar sinónimos y términos relacionados</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Gestionar Sinónimos -->
            <div class="col-md-6">
                <div class="admin-card">
                    <h3 class="mb-4">
                        <i class="bi bi-arrow-left-right"></i> Sinónimos
                    </h3>
                    
                    <!-- Formulario agregar sinónimo -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="accion" value="agregar_sinonimo">
                        
                        <div class="mb-3">
                            <label class="form-label">Término Principal</label>
                            <input type="text" class="form-control" name="termino_principal" required
                                   placeholder="ej: tenis">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sinónimo</label>
                            <input type="text" class="form-control" name="sinonimo" required
                                   placeholder="ej: zapatilla">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias_disponibles as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Agregar Sinónimo
                        </button>
                    </form>
                    
                    <!-- Lista de sinónimos -->
                    <h5>Sinónimos Existentes</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Principal</th>
                                    <th>Sinónimo</th>
                                    <th>Categoría</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sinonimos as $sin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sin['termino_principal']); ?></td>
                                    <td><?php echo htmlspecialchars($sin['sinonimo']); ?></td>
                                    <td><?php echo htmlspecialchars($sin['categoria'] ?: '-'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="accion" value="eliminar_sinonimo">
                                            <input type="hidden" name="id" value="<?php echo $sin['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('¿Eliminar este sinónimo?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gestionar Términos de Categorías -->
            <div class="col-md-6">
                <div class="admin-card">
                    <h3 class="mb-4">
                        <i class="bi bi-tags"></i> Términos Relacionados
                    </h3>
                    
                    <!-- Formulario agregar término -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="accion" value="agregar_categoria_termino">
                        
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias_disponibles as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Término Relacionado</label>
                            <input type="text" class="form-control" name="termino_relacionado" required
                                   placeholder="ej: goku">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Relevancia (1-10)</label>
                            <input type="number" class="form-control" name="relevancia" 
                                   min="1" max="10" value="5" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Agregar Término
                        </button>
                    </form>
                    
                    <!-- Lista de términos -->
                    <h5>Términos Existentes</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Término</th>
                                    <th>Relevancia</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoria_terminos as $term): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($term['categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($term['termino_relacionado']); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $term['relevancia']; ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="accion" value="eliminar_categoria_termino">
                                            <input type="hidden" name="id" value="<?php echo $term['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('¿Eliminar este término?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información -->
        <div class="admin-card">
            <h4><i class="bi bi-info-circle"></i> Cómo Funciona la Búsqueda Inteligente</h4>
            <div class="row">
                <div class="col-md-4">
                    <h6>1. Búsqueda Directa</h6>
                    <p class="small text-muted">Busca exactamente lo que el usuario escribió en nombres y descripciones.</p>
                </div>
                <div class="col-md-4">
                    <h6>2. Búsqueda por Sinónimos</h6>
                    <p class="small text-muted">Si no encuentra nada, busca usando sinónimos configurados.</p>
                </div>
                <div class="col-md-4">
                    <h6>3. Búsqueda por Categorías</h6>
                    <p class="small text-muted">Si sigue sin resultados, busca si el término está relacionado con alguna categoría.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>