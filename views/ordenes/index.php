<?php
$page_title = 'Órdenes de Trabajo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> Órdenes de Trabajo</h1>
    <div class="header-actions">
        <?php if (($_SESSION['usuario_rol'] ?? '') === 'administrador'): ?>
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=metricas" class="btn btn-info">
            <i class="fas fa-chart-line"></i> Métricas
        </a>
        <?php endif; ?>
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
            <input type="hidden" name="pagina" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda General</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" 
                           placeholder="Radicado, descripción, activo...">
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Activas (sin finalizadas)</option>
                        <?php foreach ($estados as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_GET['estado']) && $_GET['estado'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Por defecto no se muestran órdenes finalizadas. Seleccione "Finalizado" para verlas.</small>
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
                
                <div class="form-group">
                    <label>Registros por página</label>
                    <select name="por_pagina">
                        <option value="15" <?php echo (isset($_GET['por_pagina']) && $_GET['por_pagina'] == '15') ? 'selected' : ''; ?>>15</option>
                        <option value="25" <?php echo (!isset($_GET['por_pagina']) || $_GET['por_pagina'] == '25') ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo (isset($_GET['por_pagina']) && $_GET['por_pagina'] == '50') ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo (isset($_GET['por_pagina']) && $_GET['por_pagina'] == '100') ? 'selected' : ''; ?>>100</option>
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
        <h3><i class="fas fa-list"></i> Listado de Órdenes</h3>
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
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <?php
                $params_pag = ['action' => 'ordenes'];
                if (!empty($_GET['busqueda'])) $params_pag['busqueda'] = $_GET['busqueda'];
                if (isset($_GET['estado']) && $_GET['estado'] !== '') $params_pag['estado'] = $_GET['estado'];
                if (!empty($_GET['tipo'])) $params_pag['tipo'] = $_GET['tipo'];
                if (!empty($_GET['criticidad'])) $params_pag['criticidad'] = $_GET['criticidad'];
                if (!empty($_GET['asignado'])) $params_pag['asignado'] = $_GET['asignado'];
                if (!empty($_GET['activo'])) $params_pag['activo'] = $_GET['activo'];
                if (isset($_GET['por_pagina'])) $params_pag['por_pagina'] = (int)$_GET['por_pagina'];
                $url_base = BASE_URL . 'index.php?' . http_build_query($params_pag);
                $desde = $total_ordenes > 0 ? (($pagina_actual - 1) * $registros_por_pagina) + 1 : 0;
                $hasta = min($pagina_actual * $registros_por_pagina, $total_ordenes);
                ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Mostrando <?php echo $desde; ?> - <?php echo $hasta; ?> de <?php echo $total_ordenes; ?> órdenes
                    </div>
                    <nav class="pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="<?php echo $url_base . '&pagina=' . ($pagina_actual - 1); ?>" class="pagination-link">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        if ($inicio > 1): ?>
                            <a href="<?php echo $url_base . '&pagina=1'; ?>" class="pagination-link">1</a>
                            <?php if ($inicio > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="pagination-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $url_base . '&pagina=' . $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($fin < $total_paginas): ?>
                            <?php if ($fin < $total_paginas - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="<?php echo $url_base . '&pagina=' . $total_paginas; ?>" class="pagination-link"><?php echo $total_paginas; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo $url_base . '&pagina=' . ($pagina_actual + 1); ?>" class="pagination-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php elseif ($total_ordenes > 0): ?>
                <div class="pagination-info" style="margin-top: 15px;">
                    Mostrando <?php echo $total_ordenes; ?> de <?php echo $total_ordenes; ?> órdenes
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; flex-wrap: wrap; gap: 10px; }
.pagination-info { color: #6c757d; font-size: 0.9rem; }
.pagination { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
.pagination-link { padding: 8px 12px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; color: #495057; text-decoration: none; transition: all 0.3s; }
.pagination-link:hover:not(.disabled):not(.active) { background: #e9ecef; border-color: #adb5bd; color: #212529; }
.pagination-link.active { background: var(--primary-color, #007bff); color: #fff; border-color: var(--primary-color, #007bff); font-weight: 600; }
.pagination-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
.pagination-ellipsis { padding: 8px 4px; color: #6c757d; }
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

