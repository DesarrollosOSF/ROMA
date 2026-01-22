<?php
$page_title = 'Gestión de Activos';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';
?>

<div class="page-header">
    <h1><i class="fas fa-box"></i> Gestión de Activos</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=carga_masiva" class="btn btn-success">
            <i class="fas fa-upload"></i> Carga Masiva
        </a>
        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'crear_activos')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Activo
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="activos">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda General</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" 
                           placeholder="Nombre, código, marca, modelo...">
                </div>
                
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['estado']) && $_GET['estado'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($_GET['ubicacion'] ?? ''); ?>" 
                           placeholder="Área o dependencia">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Activos -->
<div class="card">
    <div class="card-header">
        <h3>Activos Registrados (<?php echo count($activos); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($activos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No se encontraron activos con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Activo</th>
                            <th>Categoría</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Último Mantenimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activos as $activo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($activo['codigo_interno'] ?: $activo['codigo_patrimonial'] ?: 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <div class="asset-name-wrapper">
                                        <?php if (!empty($activo['foto_principal'])): ?>
                                            <img src="<?php echo BASE_URL . 'uploads/' . $activo['foto_principal']; ?>" 
                                                 alt="Foto de <?php echo htmlspecialchars($activo['nombre_activo']); ?>" 
                                                 class="asset-thumb">
                                        <?php else: ?>
                                            <span class="asset-thumb asset-thumb-placeholder">
                                                <i class="fas fa-image"></i>
                                            </span>
                                        <?php endif; ?>
                                        <div class="asset-name-content">
                                            <span class="asset-name-title"><?php echo htmlspecialchars($activo['nombre_activo']); ?></span>
                                            <?php if (!empty($activo['descripcion_general'])): ?>
                                                <span class="asset-name-subtitle text-muted">
                                                    <?php echo htmlspecialchars(strlen($activo['descripcion_general']) > 60 ? substr($activo['descripcion_general'], 0, 57) . '...' : $activo['descripcion_general']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-category">
                                        <?php echo $categorias[$activo['categoria']] ?? $activo['categoria']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($activo['ubicacion'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $activo['estado_actual']; ?>">
                                        <?php echo $estados[$activo['estado_actual']] ?? $activo['estado_actual']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $activo['ultimo_mantenimiento'] 
                                        ? date('d/m/Y', strtotime($activo['ultimo_mantenimiento'])) 
                                        : 'Nunca'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $activo['id_activo']; ?>" 
                                           class="btn-icon" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'editar_activos')): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=editar&id=<?php echo $activo['id_activo']; ?>" 
                                               class="btn-icon" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'eliminar_activos')): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=eliminar&id=<?php echo $activo['id_activo']; ?>" 
                                               class="btn-icon btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este activo?')" 
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

