<?php
$page_title = 'Órdenes de Trabajo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> Órdenes de Trabajo</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=cronograma" class="btn btn-info">
            <i class="fas fa-calendar-alt"></i> Cronograma
        </a>
        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'crear_ordenes')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Orden
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
            <input type="hidden" name="action" value="ordenes">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda General</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" 
                           placeholder="Radicado, descripción, activo...">
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
                    <label>Tipo de Mantenimiento</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_mantenimiento as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['tipo']) && $_GET['tipo'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Criticidad</label>
                    <select name="criticidad">
                        <option value="">Todas</option>
                        <?php foreach ($criticidades as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['criticidad']) && $_GET['criticidad'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (in_array($_SESSION['usuario_rol'] ?? '', ['administrador'])): ?>
                <div class="form-group">
                    <label>Asignado a</label>
                    <select name="asignado">
                        <option value="">Todos</option>
                        <?php foreach ($operarios as $operario): ?>
                            <option value="<?php echo $operario['id_usuario']; ?>" 
                                    <?php echo (isset($_GET['asignado']) && $_GET['asignado'] == $operario['id_usuario']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($operario['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Activo</label>
                    <select name="activo">
                        <option value="">Todos</option>
                        <?php foreach ($activos as $activo): ?>
                            <option value="<?php echo $activo['id_activo']; ?>" 
                                    <?php echo (isset($_GET['activo']) && $_GET['activo'] == $activo['id_activo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($activo['nombre_activo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listado de Órdenes -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Listado de Órdenes (<?php echo count($ordenes); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] === 'success' ? 'success' : 'danger'; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['mensaje']); 
                unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($ordenes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No se encontraron órdenes de trabajo</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Radicado</th>
                            <th>Activo</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Criticidad</th>
                            <th>Estado</th>
                            <th>Asignado a</th>
                            <th>Fecha Límite</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($orden['numero_radicado']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?>
                                    <?php if (!empty($orden['codigo_interno'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($orden['codigo_interno']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($orden['descripcion_corta']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $tipos_mantenimiento[$orden['tipo_mantenimiento']] ?? $orden['tipo_mantenimiento']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $criticidad_class = [
                                        'critica' => 'badge-danger',
                                        'alta' => 'badge-warning',
                                        'normal' => 'badge-info',
                                        'baja' => 'badge-secondary'
                                    ];
                                    $class = $criticidad_class[$orden['nivel_criticidad']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $class; ?>">
                                        <?php echo $criticidades[$orden['nivel_criticidad']] ?? $orden['nivel_criticidad']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $estado_class = [
                                        'recibido' => 'badge-recibido',
                                        'en_proceso' => 'badge-en_proceso',
                                        'rechazado' => 'badge-rechazado',
                                        'finalizado' => 'badge-finalizado'
                                    ];
                                    $class = $estado_class[$orden['estado_proceso']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $class; ?>">
                                        <?php echo $estados[$orden['estado_proceso']] ?? $orden['estado_proceso']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($orden['nombre_asignado']): ?>
                                        <?php echo htmlspecialchars($orden['nombre_asignado']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($orden['fecha_limite_ejecucion']): ?>
                                        <?php 
                                        $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                        $hoy = new DateTime();
                                        $dias_restantes = $hoy->diff($fecha_limite)->days;
                                        
                                        if ($fecha_limite < $hoy && $orden['estado_proceso'] !== 'finalizado') {
                                            echo '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ';
                                        } elseif ($dias_restantes <= 3 && $orden['estado_proceso'] !== 'finalizado') {
                                            echo '<span class="text-warning"><i class="fas fa-clock"></i> ';
                                        } else {
                                            echo '<span>';
                                        }
                                        echo $fecha_limite->format('d/m/Y');
                                        echo '</span>';
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin fecha</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=ver&id=<?php echo $orden['id_orden']; ?>" 
                                           class="btn btn-sm btn-info" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'editar_ordenes')): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=editar&id=<?php echo $orden['id_orden']; ?>" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'eliminar_ordenes')): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=eliminar&id=<?php echo $orden['id_orden']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar esta orden?');" 
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

