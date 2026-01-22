<?php
$page_title = 'Gestión de Usuarios';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=crear" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Usuario
    </a>
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="usuarios">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" 
                           placeholder="Nombre o email...">
                </div>
                
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol">
                        <option value="">Todos</option>
                        <?php foreach ($roles as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['rol']) && $_GET['rol'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=usuarios" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Usuarios -->
<div class="card">
    <div class="card-header">
        <h3>Usuarios Registrados (<?php echo count($usuarios); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No se encontraron usuarios</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Cargo</th>
                            <th>Área</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge badge-category">
                                        <?php echo $roles[$usuario['rol']] ?? $usuario['rol']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['cargo'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($usuario['area'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $usuario['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=editar&id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="btn-icon" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id_usuario'] != $_SESSION['usuario_id']): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=eliminar&id=<?php echo $usuario['id_usuario']; ?>" 
                                               class="btn-icon btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este usuario?')" 
                                               title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

