<?php
$page_title = 'Detalle del Activo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';

$rol_actual = $_SESSION['usuario_rol'] ?? '';
$puede_ver_activos = Usuario::tienePermiso($rol_actual, 'ver_activos') || Usuario::tienePermiso($rol_actual, 'ver_lista_activos');
?>

<div class="page-header">
    <h1><i class="fas fa-box"></i> <?php echo htmlspecialchars($activo['nombre_activo']); ?></h1>
    <div class="header-actions">
        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'editar_activos')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=editar&id=<?php echo $activo['id_activo']; ?>" 
               class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if (!empty($activo['foto_principal'])): ?>
<div class="asset-photo-hero">
    <img src="<?php echo BASE_URL . 'uploads/' . $activo['foto_principal']; ?>" 
         alt="Foto del activo <?php echo htmlspecialchars($activo['nombre_activo']); ?>">
</div>
<?php endif; ?>

<div class="detail-grid">
    <!-- Información Principal -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Información General</h3>
        </div>
        <div class="card-body">
            <dl class="detail-list">
                <dt>Código Interno:</dt>
                <dd><?php echo htmlspecialchars($activo['codigo_interno'] ?: 'N/A'); ?></dd>
                
                <dt>Código Patrimonial:</dt>
                <dd><?php echo htmlspecialchars($activo['codigo_patrimonial'] ?: 'N/A'); ?></dd>
                
                <?php if (!empty($activo['descripcion_general'])): ?>
                    <dt>Descripción General:</dt>
                    <dd><?php echo nl2br(htmlspecialchars($activo['descripcion_general'])); ?></dd>
                <?php endif; ?>
                
                <dt>Categoría:</dt>
                <dd>
                    <span class="badge badge-category">
                        <?php echo $categorias[$activo['categoria']] ?? $activo['categoria']; ?>
                    </span>
                </dd>
                
                <dt>Estado:</dt>
                <dd>
                    <span class="badge badge-<?php echo $activo['estado_actual']; ?>">
                        <?php echo $estados[$activo['estado_actual']] ?? $activo['estado_actual']; ?>
                    </span>
                </dd>
                
                <dt>Ubicación:</dt>
                <dd><?php echo htmlspecialchars($activo['ubicacion'] ?: 'N/A'); ?></dd>
                
                <dt>Ruta:</dt>
                <dd>
                    <?php if (!empty($activo['ruta'])): ?>
                        <span class="badge badge-category">
                            <?php echo $categorias[$activo['ruta']] ?? $activo['ruta']; ?>
                        </span>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </dd>
                
                <dt>Responsable:</dt>
                <dd><?php echo htmlspecialchars($responsable_nombre ?? ($activo['responsable'] ?: 'N/A')); ?></dd>
                
                <dt>Área Asignada:</dt>
                <dd>
                    <?php 
                    $areas = ASSIGNED_AREAS;
                    if (!empty($activo['area_asignada']) && isset($areas[$activo['area_asignada']])): 
                        echo htmlspecialchars($areas[$activo['area_asignada']]);
                    else: 
                        echo htmlspecialchars($activo['area_asignada'] ?: 'N/A');
                    endif; 
                    ?>
                </dd>
            </dl>
        </div>
    </div>
    
    <!-- Especificaciones Técnicas -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-cog"></i> Especificaciones Técnicas</h3>
        </div>
        <div class="card-body">
            <dl class="detail-list">
                <dt>Marca:</dt>
                <dd><?php echo htmlspecialchars($activo['marca'] ?: 'N/A'); ?></dd>
                
                <dt>Modelo:</dt>
                <dd><?php echo htmlspecialchars($activo['modelo'] ?: 'N/A'); ?></dd>
                
                <dt>Número de Serie:</dt>
                <dd><?php echo htmlspecialchars($activo['numero_serie'] ?: 'N/A'); ?></dd>
                
                <dt>Fecha de Adquisición:</dt>
                <dd><?php echo $activo['fecha_adquisicion'] 
                    ? date('d/m/Y', strtotime($activo['fecha_adquisicion'])) 
                    : 'N/A'; ?></dd>
                
                <dt>Valor de Adquisición:</dt>
                <dd>$<?php echo number_format($activo['valor_adquisicion'] ?? 0, 2, ',', '.'); ?></dd>
                
                <dt>Vida Útil Estimada:</dt>
                <dd><?php echo $activo['vida_util_estimada'] ? $activo['vida_util_estimada'] . ' meses' : 'N/A'; ?></dd>
            </dl>
        </div>
    </div>
    
    <!-- Uso y Mantenimiento -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tachometer-alt"></i> Uso y Mantenimiento</h3>
        </div>
        <div class="card-body">
            <dl class="detail-list">
                <dt>Kilometraje / Horas:</dt>
                <dd><?php echo number_format($activo['kilometraje'] ?? 0, 2, ',', '.'); ?></dd>
                
                <dt>Horas de Uso:</dt>
                <dd><?php echo number_format($activo['horas_uso'] ?? 0, 2, ',', '.'); ?></dd>
                
                <dt>Último Mantenimiento:</dt>
                <dd>
                    <?php echo $activo['ultimo_mantenimiento'] 
                        ? date('d/m/Y', strtotime($activo['ultimo_mantenimiento'])) 
                        : 'Nunca'; ?>
                </dd>
                
                <dt>Próximo Mantenimiento:</dt>
                <dd>
                    <?php 
                    if ($activo['proximo_mantenimiento']) {
                        $proximo = strtotime($activo['proximo_mantenimiento']);
                        $hoy = time();
                        $dias = floor(($proximo - $hoy) / (60 * 60 * 24));
                        
                        echo date('d/m/Y', $proximo);
                        if ($dias < 0) {
                            echo ' <span class="badge badge-danger">Vencido</span>';
                        } elseif ($dias <= 30) {
                            echo ' <span class="badge badge-warning">Próximo</span>';
                        }
                    } else {
                        echo 'No programado';
                    }
                    ?>
                </dd>
            </dl>
        </div>
    </div>
    
    <!-- Observaciones -->
    <?php if (!empty($activo['observaciones'])): ?>
    <div class="card card-full">
        <div class="card-header">
            <h3><i class="fas fa-comment"></i> Observaciones</h3>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($activo['observaciones'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Análisis del Activo -->
<?php
$hayDatosOrdenes = !empty($estadisticasOrdenes['total']);
$hayDatosMantenimientos = !empty($estadisticasMantenimientos['total']);
?>

<?php if (($hayDatosOrdenes || $hayDatosMantenimientos) && !empty($metricasDecision['resumen'])): ?>
<div class="card card-full">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Análisis del Activo</h3>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <?php foreach ($metricasDecision['resumen'] as $tarjeta): ?>
            <div class="stat-card stat-<?php echo htmlspecialchars($tarjeta['color']); ?>">
                <div class="stat-icon">
                    <i class="fas fa-<?php echo htmlspecialchars($tarjeta['icon']); ?>"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo htmlspecialchars($tarjeta['value']); ?></h3>
                    <p><?php echo htmlspecialchars($tarjeta['label']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="analytics-insights">
            <?php if (!empty($metricasDecision['insights']['ultima_orden'])): ?>
                <?php $ultima = $metricasDecision['insights']['ultima_orden']; ?>
                <div class="insight-card">
                    <h4><i class="fas fa-clock"></i> Última Orden</h4>
                    <p><strong><?php echo htmlspecialchars($ultima['numero']); ?></strong> (<?php echo htmlspecialchars($ultima['fecha']); ?>)</p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($ultima['estado']); ?></p>
                    <p><strong>Criticidad:</strong> <?php echo htmlspecialchars($ultima['criticidad']); ?></p>
                    <p><strong>Técnico:</strong> <?php echo htmlspecialchars($ultima['tecnico']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($metricasDecision['insights']['criticidad_alta'])): ?>
            <div class="insight-card">
                <h4><i class="fas fa-bolt"></i> Órdenes Críticas/Altas</h4>
                <p><strong><?php echo number_format($metricasDecision['insights']['criticidad_alta']); ?></strong> orden(es) requieren atención prioritaria.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($metricasDecision['insights']['ultima_intervencion'])): ?>
            <div class="insight-card">
                <h4><i class="fas fa-calendar-alt"></i> Última Intervención</h4>
                <p><strong><?php echo htmlspecialchars($metricasDecision['insights']['ultima_intervencion']); ?></strong></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <?php if ($hayDatosOrdenes): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-adjust"></i> Órdenes por Estado</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartOrdenesEstadoActivo" height="220"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Órdenes por Criticidad</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartOrdenesCriticidadActivo" height="220"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-area"></i> Tendencia Mensual de Órdenes</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartTendenciaOrdenesActivo" height="220"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($hayDatosMantenimientos && !empty($estadisticasMantenimientos['costo_total'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-coins"></i> Costos Promedio</h3>
                </div>
                <div class="card-body">
                    <p><strong>Costo Promedio por Intervención:</strong><br>
                        $<?php echo number_format($estadisticasMantenimientos['costo_promedio'] ?? 0, 2, ',', '.'); ?></p>
                    <?php if (!empty($estadisticasMantenimientos['ultima_fecha'])): ?>
                        <p><strong>Último Mantenimiento:</strong><br>
                        <?php echo date('d/m/Y', strtotime($estadisticasMantenimientos['ultima_fecha'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hoja de Vida -->
<div class="card card-full">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Hoja de Vida del Activo</h3>
    </div>
    <div class="card-body">
        <!-- Filtros de Hoja de Vida -->
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="activos">
            <input type="hidden" name="subaction" value="ver">
            <input type="hidden" name="id" value="<?php echo $activo['id_activo']; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Tipo de Mantenimiento</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_mantenimiento as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (!empty($filtros['tipo_mantenimiento']) && $filtros['tipo_mantenimiento'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Técnico</label>
                    <select name="tecnico">
                        <option value="">Todos</option>
                        <?php if (!empty($operarios)): ?>
                            <?php foreach ($operarios as $operario): ?>
                                <option value="<?php echo $operario['id_usuario']; ?>" 
                                        <?php echo (!empty($filtros['tecnico']) && $filtros['tecnico'] == $operario['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($operario['nombre']); ?>
                                    <?php if (!empty($operario['email'])): ?>
                                        - <?php echo htmlspecialchars($operario['email']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $activo['id_activo']; ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
                <button type="button" class="btn btn-success" onclick="generarReportePDF(<?php echo $activo['id_activo']; ?>)">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </button>
            </div>
        </form>
        
        <!-- Historial -->
        <?php if (empty($hoja_vida)): ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>No hay registros de mantenimiento para este activo</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table hoja-vida-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Técnico</th>
                            <th>Fecha Límite</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hoja_vida as $registro): ?>
                            <?php $esOrden = !empty($registro['es_orden']); ?>
                            <tr class="<?php echo $esOrden ? 'registro-orden' : 'registro-mantenimiento'; ?>">
                                <td>
                                    <?php echo !empty($registro['fecha_intervencion']) ? date('d/m/Y', strtotime($registro['fecha_intervencion'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php if ($esOrden): ?>
                                        <span class="badge badge-info">Orden</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Mantenimiento</span>
                                    <?php endif; ?>
                                    <div><?php echo $tipos_mantenimiento[$registro['tipo_mantenimiento']] ?? $registro['tipo_mantenimiento'] ?? 'Intervención'; ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($registro['numero_orden'])): ?>
                                        <div><strong>Orden:</strong> <?php echo htmlspecialchars($registro['numero_orden']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($registro['descripcion_corta'])): ?>
                                        <div><strong>Corta:</strong> <?php echo htmlspecialchars($registro['descripcion_corta']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($registro['descripcion_detallada'])): ?>
                                        <div class="texto-detallado"><strong>Detalle:</strong> <?php echo nl2br(htmlspecialchars($registro['descripcion_detallada'])); ?></div>
                                    <?php elseif (!empty($registro['descripcion'])): ?>
                                        <div class="texto-detallado"><?php echo nl2br(htmlspecialchars($registro['descripcion'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($registro['observaciones'])): ?>
                                        <div class="texto-detallado"><em><?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?></em></div>
                                    <?php endif; ?>
                                    <?php if ($registro['kilometraje_evento'] || $registro['horas_uso_evento']): ?>
                                        <div>
                                            <?php if ($registro['kilometraje_evento']): ?>
                                                <strong>Kilometraje:</strong> <?php echo number_format($registro['kilometraje_evento'], 2, ',', '.'); ?> km
                                            <?php endif; ?>
                                            <?php if ($registro['horas_uso_evento']): ?>
                                                <strong>Horas:</strong> <?php echo number_format($registro['horas_uso_evento'], 2, ',', '.'); ?> hrs
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($registro['evidencias'])): ?>
                                        <div class="evidencias">
                                            <strong>Evidencias:</strong>
                                            <?php 
                                            $evidencias = explode(',', $registro['evidencias']);
                                            foreach ($evidencias as $evidencia):
                                                $ruta = trim($evidencia);
                                                if (!$ruta) continue;
                                            ?>
                                                <img src="<?php echo BASE_URL . 'uploads/' . $ruta; ?>" 
                                                     alt="Evidencia" class="evidencia-thumb" 
                                                     onclick="abrirImagen(this.src)">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($registro['estado_orden'])): ?>
                                        <span class="badge badge-<?php echo $registro['estado_orden']; ?>">
                                            <?php echo ORDER_STATES[$registro['estado_orden']] ?? $registro['estado_orden']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo !empty($registro['tecnico_responsable']) ? htmlspecialchars($registro['tecnico_responsable']) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php echo !empty($registro['fecha_limite_solicitud']) ? date('d/m/Y', strtotime($registro['fecha_limite_solicitud'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php if (!empty($registro['link_detalle'])): ?>
                                        <a href="<?php echo $registro['link_detalle']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-eye"></i> Ver Orden
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Mostrando <?php echo count($hoja_vida); ?> de <?php echo $total_registros; ?> registros
                    </div>
                    <nav class="pagination">
                        <?php
                        $url_base = BASE_URL . 'index.php?action=activos&subaction=ver&id=' . $activo['id_activo'];
                        $params = [];
                        if (!empty($filtros['tipo_mantenimiento'])) {
                            $params[] = 'tipo=' . urlencode($filtros['tipo_mantenimiento']);
                        }
                        if (!empty($filtros['tecnico'])) {
                            $params[] = 'tecnico=' . urlencode($filtros['tecnico']);
                        }
                        $url_params = !empty($params) ? '&' . implode('&', $params) : '';
                        ?>
                        
                        <?php if ($pagina_actual > 1): ?>
                            <a href="<?php echo $url_base . $url_params . '&pagina=' . ($pagina_actual - 1); ?>" class="pagination-link">
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
                            <a href="<?php echo $url_base . $url_params . '&pagina=1'; ?>" class="pagination-link">1</a>
                            <?php if ($inicio > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="pagination-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $url_base . $url_params . '&pagina=' . $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($fin < $total_paginas): ?>
                            <?php if ($fin < $total_paginas - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="<?php echo $url_base . $url_params . '&pagina=' . $total_paginas; ?>" class="pagination-link"><?php echo $total_paginas; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo $url_base . $url_params . '&pagina=' . ($pagina_actual + 1); ?>" class="pagination-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para imágenes -->
<div id="imageModal" class="modal" onclick="cerrarImagen()">
    <span class="modal-close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function generarReportePDF(id) {
    window.open('<?php echo BASE_URL; ?>api/reportes/pdf_hoja_vida.php?id=' + id, '_blank');
}

function abrirImagen(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'block';
}

function cerrarImagen() {
    document.getElementById('imageModal').style.display = 'none';
}
</script>

<style>
.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.9rem;
}

.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination-link {
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s;
}

.pagination-link:hover:not(.disabled):not(.active) {
    background: #e9ecef;
    border-color: #adb5bd;
    color: #212529;
}

.pagination-link.active {
    background: var(--primary-color, #007bff);
    color: #fff;
    border-color: var(--primary-color, #007bff);
    font-weight: 600;
}

.pagination-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-ellipsis {
    padding: 8px 4px;
    color: #6c757d;
}
</style>

<?php 
// $extra_scripts = []; // No se necesita script adicional
require_once __DIR__ . '/../layout/footer.php'; 
?>

