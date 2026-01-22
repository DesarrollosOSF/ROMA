<?php
$page_title = 'Cronograma de Trabajo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';

$rol_actual = $_SESSION['usuario_rol'] ?? '';
$es_administrador = ($rol_actual === 'administrador');
$usuario_id = $_SESSION['usuario_id'] ?? null;
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Cronograma de Trabajo</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Órdenes
        </a>
        <?php if (Usuario::tienePermiso($rol_actual, 'crear_ordenes')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Orden
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Panel de Estadísticas de Operarios -->
<?php if ($es_administrador && !empty($estadisticas_operarios)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Resumen de Operarios</h3>
    </div>
    <div class="card-body">
        <div class="operarios-stats-grid">
            <?php foreach ($estadisticas_operarios as $operario_id => $stats): ?>
                <div class="operario-stat-card" data-operario-id="<?php echo $operario_id; ?>">
                    <div class="operario-stat-header">
                        <h4><?php echo htmlspecialchars($stats['nombre']); ?></h4>
                        <span class="badge badge-primary"><?php echo $stats['total']; ?> órdenes</span>
                    </div>
                    <div class="operario-stat-body">
                        <div class="stat-row">
                            <span class="stat-label">En Proceso:</span>
                            <span class="stat-value text-warning"><?php echo $stats['en_proceso']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Finalizadas:</span>
                            <span class="stat-value text-success"><?php echo $stats['finalizado']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Críticas:</span>
                            <span class="stat-value text-danger"><?php echo $stats['critica']; ?></span>
                        </div>
                        <?php if ($stats['atrasadas'] > 0): ?>
                        <div class="stat-row">
                            <span class="stat-label">Atrasadas:</span>
                            <span class="stat-value text-danger"><strong><?php echo $stats['atrasadas']; ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Controles del Cronograma -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Filtros y Vista</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form" id="cronograma-filters">
            <input type="hidden" name="action" value="ordenes">
            <input type="hidden" name="subaction" value="cronograma">
            
            <?php if ($rol_actual === 'operario'): ?>
                <input type="hidden" name="filtro_operario" value="<?php echo htmlspecialchars($usuario_id); ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Vista</label>
                    <select name="vista" id="vista-select" class="form-control" onchange="actualizarFechasPorVista()">
                        <option value="semanal" <?php echo ($vista === 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                        <option value="mensual" <?php echo ($vista === 'mensual') ? 'selected' : ''; ?>>Mensual</option>
                        <option value="diaria" <?php echo ($vista === 'diaria') ? 'selected' : ''; ?>>Diaria</option>
                    </select>
                </div>
                
                <?php if ($es_administrador): ?>
                <div class="form-group">
                    <label>Filtrar por Operario</label>
                    <select name="filtro_operario" class="form-control">
                        <option value="">Todos los operarios</option>
                        <?php foreach ($operarios as $operario): ?>
                            <option value="<?php echo $operario['id_usuario']; ?>" 
                                    <?php echo (isset($filtro_operario) && $filtro_operario == $operario['id_usuario']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($operario['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Filtrar por Estado</label>
                    <select name="filtro_estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($filtro_estado) && $filtro_estado === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Filtrar por Criticidad</label>
                    <select name="filtro_criticidad" class="form-control">
                        <option value="">Todas las criticidades</option>
                        <?php foreach ($criticidades as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($filtro_criticidad) && $filtro_criticidad === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fecha Desde</label>
                    <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" class="form-control" id="fecha-desde">
                </div>
                
                <div class="form-group">
                    <label>Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" class="form-control" id="fecha-hasta">
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="navegarSemana(-1)">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="navegarSemana(1)">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="btn btn-info" onclick="irHoy()">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Cronograma Visual -->
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-calendar-week"></i> 
            Cronograma <?php echo ucfirst($vista); ?>
            <span class="text-muted">
                (<?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>)
            </span>
        </h3>
        <div class="header-actions">
            <span class="badge badge-info"><?php echo count($ordenes); ?> órdenes</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($ordenes)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No hay órdenes programadas en este período</p>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=crear" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Nueva Orden
                </a>
            </div>
        <?php else: ?>
            <div id="cronograma-container" class="cronograma-container" data-vista="<?php echo $vista; ?>">
                <?php if ($vista === 'semanal'): ?>
                    <?php 
                        $fecha_inicio_periodo = new DateTime($fecha_desde);
                        $fecha_fin_periodo = new DateTime($fecha_hasta);
                        $total_dias_periodo = max(1, $fecha_inicio_periodo->diff($fecha_fin_periodo)->days + 1);
                    ?>
                    <!-- Vista Semanal Mejorada -->
                    <div class="cronograma-semanal">
                        <div class="cronograma-header" style="--column-count: <?php echo $total_dias_periodo; ?>;">
                            <div class="cronograma-col-header cronograma-col-operario">
                                <strong>Operario</strong>
                            </div>
                            <?php
                            $fecha_actual = new DateTime($fecha_desde);
                            $fecha_fin = new DateTime($fecha_hasta);
                            while ($fecha_actual <= $fecha_fin):
                                $es_hoy = ($fecha_actual->format('Y-m-d') === date('Y-m-d'));
                                $es_fin_semana = in_array($fecha_actual->format('w'), [0, 6]);
                            ?>
                                <div class="cronograma-col-header <?php echo $es_hoy ? 'hoy' : ''; ?> <?php echo $es_fin_semana ? 'fin-semana' : ''; ?>">
                                    <div class="fecha-dia"><?php echo $fecha_actual->format('d'); ?></div>
                                    <div class="fecha-dia-nombre"><?php 
                                        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                                        echo $dias[(int)$fecha_actual->format('w')];
                                    ?></div>
                                    <div class="fecha-mes"><?php echo $fecha_actual->format('M'); ?></div>
                                </div>
                            <?php
                                $fecha_actual->modify('+1 day');
                            endwhile;
                            ?>
                        </div>
                        
                        <?php
                        // Agrupar órdenes por operario
                        $ordenes_por_operario = [];
                        foreach ($ordenes as $orden) {
                            $operario_id = $orden['id_usuario_asignado'] ?? 'sin_asignar';
                            $operario_nombre = $orden['nombre_asignado'] ?? 'Sin Asignar';
                            if (!isset($ordenes_por_operario[$operario_id])) {
                                $ordenes_por_operario[$operario_id] = [
                                    'nombre' => $operario_nombre,
                                    'email' => $orden['email_asignado'] ?? '',
                                    'ordenes' => []
                                ];
                            }
                            $ordenes_por_operario[$operario_id]['ordenes'][] = $orden;
                        }
                        
                        // Agregar operarios sin órdenes para mostrar filas vacías
                        foreach ($operarios as $operario) {
                            if (!isset($ordenes_por_operario[$operario['id_usuario']])) {
                                $ordenes_por_operario[$operario['id_usuario']] = [
                                    'nombre' => $operario['nombre'],
                                    'email' => $operario['email'] ?? '',
                                    'ordenes' => []
                                ];
                            }
                        }
                        
                        // Agregar fila para órdenes sin asignar si existen
                        if (isset($ordenes_por_operario['sin_asignar']) && !empty($ordenes_por_operario['sin_asignar']['ordenes'])) {
                            // Ya está en el array
                        } elseif (!empty($ordenes_por_operario['sin_asignar'])) {
                            unset($ordenes_por_operario['sin_asignar']);
                        }
                        
                        foreach ($ordenes_por_operario as $operario_id => $data):
                            $stats = $estadisticas_operarios[$operario_id] ?? null;
                        ?>
                            <div class="cronograma-row" data-operario-id="<?php echo $operario_id; ?>" style="--column-count: <?php echo $total_dias_periodo; ?>;">
                                <div class="cronograma-col-fixed">
                                    <div class="operario-info">
                                        <strong><?php echo htmlspecialchars($data['nombre']); ?></strong>
                                        <?php if ($stats): ?>
                                            <div class="operario-stats-mini">
                                                <span class="badge badge-sm badge-warning"><?php echo $stats['en_proceso']; ?> en proceso</span>
                                                <?php if ($stats['atrasadas'] > 0): ?>
                                                    <span class="badge badge-sm badge-danger"><?php echo $stats['atrasadas']; ?> atrasadas</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                                $fecha_actual = new DateTime($fecha_desde);
                                $fecha_fin = new DateTime($fecha_hasta);
                                while ($fecha_actual <= $fecha_fin):
                                    $fecha_str = $fecha_actual->format('Y-m-d');
                                    $es_hoy = ($fecha_str === date('Y-m-d'));
                                    $es_pasado = ($fecha_str < date('Y-m-d'));
                                ?>
                                    <div class="cronograma-cell <?php echo $es_hoy ? 'hoy' : ''; ?> <?php echo $es_pasado ? 'pasado' : ''; ?>" 
                                         data-fecha="<?php echo $fecha_str; ?>"
                                         data-operario-id="<?php echo $operario_id; ?>"
                                         ondrop="drop(event)" 
                                         ondragover="allowDrop(event)"
                                         ondragenter="dragEnter(event)"
                                         ondragleave="dragLeave(event)">
                                        <?php
                                        $ordenes_dia = [];
                                        foreach ($data['ordenes'] as $orden):
                                            $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                            if ($fecha_limite->format('Y-m-d') === $fecha_str):
                                                $ordenes_dia[] = $orden;
                                            endif;
                                        endforeach;
                                        
                                        foreach ($ordenes_dia as $orden):
                                            $estado_class = [
                                                'recibido' => 'orden-recibido',
                                                'en_proceso' => 'orden-en_proceso',
                                                'rechazado' => 'orden-rechazado',
                                                'finalizado' => 'orden-finalizado'
                                            ];
                                            $criticidad_class = [
                                                'critica' => 'criticidad-critica',
                                                'alta' => 'criticidad-alta',
                                                'normal' => 'criticidad-normal',
                                                'baja' => 'criticidad-baja'
                                            ];
                                            $class = $estado_class[$orden['estado_proceso']] ?? '';
                                            $criticidad = $criticidad_class[$orden['nivel_criticidad']] ?? '';
                                            $es_atrasada = (new DateTime($orden['fecha_limite_ejecucion']) < new DateTime() && $orden['estado_proceso'] !== 'finalizado');
                                        ?>
                                            <div class="orden-item <?php echo $class . ' ' . $criticidad; ?> <?php echo $es_atrasada ? 'atrasada' : ''; ?>" 
                                                 draggable="true" 
                                                 ondragstart="drag(event)"
                                                 data-orden-id="<?php echo $orden['id_orden']; ?>"
                                                 data-estado="<?php echo $orden['estado_proceso']; ?>"
                                                 data-criticidad="<?php echo $orden['nivel_criticidad']; ?>"
                                                 onclick="verDetalleOrden(<?php echo $orden['id_orden']; ?>)"
                                                 title="<?php echo htmlspecialchars($orden['descripcion_corta']); ?>">
                                                <div class="orden-numero">
                                                    <?php echo htmlspecialchars($orden['numero_radicado']); ?>
                                                    <?php if ($es_atrasada): ?>
                                                        <i class="fas fa-exclamation-triangle" title="Atrasada"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="orden-descripcion">
                                                    <?php echo htmlspecialchars(substr($orden['descripcion_corta'], 0, 25)); ?>
                                                    <?php if (strlen($orden['descripcion_corta']) > 25) echo '...'; ?>
                                                </div>
                                                <div class="orden-activo">
                                                    <small><i class="fas fa-cog"></i> <?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?></small>
                                                </div>
                                                <div class="orden-estado-badge">
                                                    <span class="badge badge-sm"><?php echo $estados[$orden['estado_proceso']] ?? ''; ?></span>
                                                </div>
                                            </div>
                                        <?php
                                        endforeach;
                                        ?>
                                    </div>
                                <?php
                                    $fecha_actual->modify('+1 day');
                                endwhile;
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($vista === 'mensual'): ?>
                    <!-- Vista Mensual -->
                    <div class="cronograma-mensual">
                        <div class="calendar-grid">
                            <?php
                            $fecha_actual = new DateTime($fecha_desde);
                            $fecha_fin = new DateTime($fecha_hasta);
                            $dias = [];
                            while ($fecha_actual <= $fecha_fin) {
                                $dias[] = clone $fecha_actual;
                                $fecha_actual->modify('+1 day');
                            }
                            
                            // Agrupar por semanas
                            $semanas = array_chunk($dias, 7);
                            foreach ($semanas as $semana):
                            ?>
                                <div class="calendar-week">
                                    <?php foreach ($semana as $dia): 
                                        $es_hoy = ($dia->format('Y-m-d') === date('Y-m-d'));
                                    ?>
                                        <div class="calendar-day <?php echo $es_hoy ? 'hoy' : ''; ?>" 
                                             data-fecha="<?php echo $dia->format('Y-m-d'); ?>"
                                             ondrop="drop(event)" 
                                             ondragover="allowDrop(event)">
                                            <div class="calendar-day-header">
                                                <?php echo $dia->format('d'); ?><br>
                                                <small><?php 
                                                    $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                                                    echo $dias[(int)$dia->format('w')];
                                                ?></small>
                                            </div>
                                            <div class="calendar-day-body">
                                                <?php
                                                foreach ($ordenes as $orden):
                                                    $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                                    if ($fecha_limite->format('Y-m-d') === $dia->format('Y-m-d')):
                                                        $estado_class = [
                                                            'recibido' => 'orden-recibido',
                                                            'en_proceso' => 'orden-en_proceso',
                                                            'rechazado' => 'orden-rechazado',
                                                            'finalizado' => 'orden-finalizado'
                                                        ];
                                                        $criticidad_class = [
                                                            'critica' => 'criticidad-critica',
                                                            'alta' => 'criticidad-alta',
                                                            'normal' => 'criticidad-normal',
                                                            'baja' => 'criticidad-baja'
                                                        ];
                                                        $class = $estado_class[$orden['estado_proceso']] ?? '';
                                                        $criticidad = $criticidad_class[$orden['nivel_criticidad']] ?? '';
                                                ?>
                                                        <div class="orden-item-small <?php echo $class . ' ' . $criticidad; ?>" 
                                                             draggable="true" 
                                                             ondragstart="drag(event)"
                                                             data-orden-id="<?php echo $orden['id_orden']; ?>"
                                                             onclick="verDetalleOrden(<?php echo $orden['id_orden']; ?>)"
                                                             title="<?php echo htmlspecialchars($orden['descripcion_corta']); ?>">
                                                            <?php echo htmlspecialchars($orden['numero_radicado']); ?>
                                                        </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Vista Diaria -->
                    <div class="cronograma-diaria">
                        <?php
                        $fecha_actual = new DateTime($fecha_desde);
                        $fecha_fin = new DateTime($fecha_hasta);
                        while ($fecha_actual <= $fecha_fin):
                            $fecha_str = $fecha_actual->format('Y-m-d');
                            $ordenes_dia = array_filter($ordenes, function($orden) use ($fecha_str) {
                                $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                return $fecha_limite->format('Y-m-d') === $fecha_str;
                            });
                        ?>
                            <div class="cronograma-dia" data-fecha="<?php echo $fecha_str; ?>">
                                <h4>
                                    <i class="fas fa-calendar-day"></i>
                                    <?php echo $fecha_actual->format('d/m/Y - l'); ?>
                                    <span class="badge badge-info"><?php echo count($ordenes_dia); ?> órdenes</span>
                                </h4>
                                <div class="ordenes-dia">
                                    <?php if (empty($ordenes_dia)): ?>
                                        <p class="text-muted">No hay órdenes programadas para este día</p>
                                    <?php else: ?>
                                        <?php foreach ($ordenes_dia as $orden): 
                                            $estado_class = [
                                                'recibido' => 'orden-recibido',
                                                'en_proceso' => 'orden-en_proceso',
                                                'rechazado' => 'orden-rechazado',
                                                'finalizado' => 'orden-finalizado'
                                            ];
                                        ?>
                                            <div class="orden-item-detalle" 
                                                 draggable="true" 
                                                 ondragstart="drag(event)"
                                                 data-orden-id="<?php echo $orden['id_orden']; ?>"
                                                 onclick="verDetalleOrden(<?php echo $orden['id_orden']; ?>)">
                                                <div class="orden-header">
                                                    <strong><?php echo htmlspecialchars($orden['numero_radicado']); ?></strong>
                                                    <span class="badge <?php echo $estado_class[$orden['estado_proceso']] ?? ''; ?>">
                                                        <?php echo $estados[$orden['estado_proceso']] ?? ''; ?>
                                                    </span>
                                                </div>
                                                <div class="orden-body">
                                                    <p><?php echo htmlspecialchars($orden['descripcion_corta']); ?></p>
                                                    <small>
                                                        <strong>Activo:</strong> <?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?><br>
                                                        <strong>Asignado a:</strong> <?php echo htmlspecialchars($orden['nombre_asignado'] ?? 'Sin asignar'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                            $fecha_actual->modify('+1 day');
                        endwhile;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Leyenda -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Leyenda</h3>
    </div>
    <div class="card-body">
        <div class="legend">
            <div class="legend-item">
                <h4>Estados:</h4>
                <span class="badge orden-recibido">Recibido</span>
                <span class="badge orden-en_proceso">En Proceso</span>
                <span class="badge orden-rechazado">Rechazado</span>
                <span class="badge orden-finalizado">Finalizado</span>
            </div>
            <div class="legend-item">
                <h4>Criticidad:</h4>
                <span class="badge criticidad-critica">Crítica</span>
                <span class="badge criticidad-alta">Alta</span>
                <span class="badge criticidad-normal">Normal</span>
                <span class="badge criticidad-baja">Baja</span>
            </div>
            <div class="legend-item">
                <h4>Indicadores:</h4>
                <span><i class="fas fa-exclamation-triangle text-danger"></i> Atrasada</span>
                <span><i class="fas fa-calendar-day text-info"></i> Día actual</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos mejorados para el cronograma */
.operarios-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.operario-stat-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.operario-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.operario-stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.operario-stat-header h4 {
    margin: 0;
    font-size: 1rem;
}

.operario-stat-body {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.stat-label {
    color: #6c757d;
}

.stat-value {
    font-weight: 600;
}

.cronograma-container {
    overflow-x: auto;
    margin-top: 20px;
}

.cronograma-semanal {
    min-width: 100%;
}

.cronograma-header {
    display: grid;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: 2px solid #dee2e6;
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 25;
    grid-template-columns: 220px repeat(var(--column-count, 7), minmax(150px, 1fr));
}

.cronograma-col-header {
    padding: 12px 8px;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,0.2);
}

.cronograma-col-header.hoy {
    background: rgba(255,255,255,0.2);
    font-weight: bold;
}

.cronograma-col-header.fin-semana {
    background: rgba(0,0,0,0.1);
}

.cronograma-col-operario {
    background: rgba(0,0,0,0.15);
    position: sticky;
    left: 0;
    z-index: 18;
    top: 0;
}

.fecha-dia {
    font-size: 1.2rem;
    font-weight: bold;
}

.fecha-dia-nombre {
    font-size: 0.85rem;
    opacity: 0.9;
}

.fecha-mes {
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
}

.cronograma-row {
    display: grid;
    border-bottom: 1px solid #dee2e6;
    min-height: 120px;
    transition: background-color 0.2s;
    grid-template-columns: 220px repeat(var(--column-count, 7), minmax(150px, 1fr));
}

.cronograma-row:hover {
    background-color: #f8f9fa;
}

.cronograma-col-fixed {
    padding: 12px;
    background: #f8f9fa;
    border-right: 2px solid #dee2e6;
    font-weight: bold;
    display: flex;
    align-items: center;
    position: sticky;
    left: 0;
    z-index: 12;
}

.operario-info {
    width: 100%;
}

.operario-stats-mini {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}

.cronograma-cell {
    padding: 8px;
    border-right: 1px solid #dee2e6;
    min-height: 120px;
    position: relative;
    background: white;
    display: flex;
    flex-direction: column;
    gap: 6px;
    overflow: hidden;
}

.cronograma-cell.hoy {
    background: #e7f3ff;
    border-left: 3px solid #007bff;
}

.cronograma-cell.pasado {
    background: #f8f9fa;
    opacity: 0.8;
}

.cronograma-cell.drag-over {
    background: #d4edda;
    border: 2px dashed #28a745;
}

.orden-item {
    background: #fff;
    border: 2px solid #007bff;
    border-radius: 4px;
    padding: 6px;
    margin-bottom: 5px;
    cursor: move;
    transition: all 0.2s;
    font-size: 0.85rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    width: 100%;
    box-sizing: border-box;
}

.orden-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    z-index: 20;
}

.orden-item.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

.orden-item.atrasada {
    border-color: #dc3545;
    border-width: 3px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.orden-numero {
    font-weight: bold;
    font-size: 0.9rem;
    color: #333;
    margin-bottom: 3px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.orden-descripcion {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 3px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.orden-activo {
    font-size: 0.75rem;
    color: #999;
}

.orden-estado-badge {
    margin-top: 4px;
}

/* Estados de órdenes */
.orden-recibido {
    border-color: #17a2b8;
    background: #d1ecf1;
}

.orden-en_proceso {
    border-color: #ffc107;
    background: #fff3cd;
}

.orden-rechazado {
    border-color: #dc3545;
    background: #f8d7da;
}

.orden-finalizado {
    border-color: #28a745;
    background: #d4edda;
}

/* Criticidad */
.criticidad-critica {
    border-left: 4px solid #dc3545;
}

.criticidad-alta {
    border-left: 4px solid #fd7e14;
}

.criticidad-normal {
    border-left: 4px solid #ffc107;
}

.criticidad-baja {
    border-left: 4px solid #28a745;
}

/* Vista mensual */
.calendar-grid {
    display: grid;
    gap: 10px;
}

.calendar-week {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.calendar-day {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px;
    min-height: 100px;
}

.calendar-day.hoy {
    background: #e7f3ff;
    border: 2px solid #007bff;
}

.calendar-day-header {
    font-weight: bold;
    margin-bottom: 5px;
    text-align: center;
}

.calendar-day-body {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.orden-item-small {
    background: #fff;
    border: 1px solid #007bff;
    border-radius: 3px;
    padding: 4px;
    font-size: 0.75rem;
    cursor: move;
    text-align: center;
    font-weight: bold;
}

/* Vista diaria */
.cronograma-diaria .orden-item-detalle {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
    transition: box-shadow 0.2s;
}

.cronograma-diaria .orden-item-detalle:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.orden-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}

.legend-item h4 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.legend-item .badge {
    margin-right: 0.5rem;
    margin-bottom: 0.25rem;
}

/* Responsive */
@media (max-width: 768px) {
    .operarios-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .cronograma-header {
        grid-template-columns: 180px repeat(var(--column-count, 7), minmax(120px, 1fr));
    }
    
    .cronograma-row {
        grid-template-columns: 180px repeat(var(--column-count, 7), minmax(120px, 1fr));
    }
    
    .cronograma-col-fixed,
    .cronograma-col-header {
        font-size: 0.85em;
    }
    
    .calendar-week {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Funciones de Drag & Drop mejoradas
let draggedElement = null;
let draggedData = null;

function allowDrop(ev) {
    ev.preventDefault();
}

function dragEnter(ev) {
    ev.preventDefault();
    if (ev.currentTarget.classList.contains('cronograma-cell')) {
        ev.currentTarget.classList.add('drag-over');
    }
}

function dragLeave(ev) {
    if (ev.currentTarget.classList.contains('cronograma-cell')) {
        ev.currentTarget.classList.remove('drag-over');
    }
}

function drag(ev) {
    draggedElement = ev.target.closest('.orden-item, .orden-item-small, .orden-item-detalle');
    if (!draggedElement) return;
    
    draggedData = {
        ordenId: draggedElement.getAttribute('data-orden-id'),
        estado: draggedElement.getAttribute('data-estado'),
        criticidad: draggedElement.getAttribute('data-criticidad')
    };
    
    ev.dataTransfer.effectAllowed = "move";
    ev.dataTransfer.setData("text/html", draggedElement.outerHTML);
    draggedElement.classList.add('dragging');
}

function drop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');
    
    if (!draggedElement || !draggedData) return;
    
    const targetCell = ev.currentTarget;
    const ordenId = draggedData.ordenId;
    const nuevaFecha = targetCell.getAttribute('data-fecha');
    let nuevoOperarioId = targetCell.getAttribute('data-operario-id');
    if (!nuevoOperarioId || nuevoOperarioId === 'sin_asignar' || nuevoOperarioId === 'null') {
        nuevoOperarioId = null;
    }
    
    if (!nuevaFecha) {
        draggedElement.classList.remove('dragging');
        draggedElement = null;
        draggedData = null;
        return;
    }
    
    // Confirmar acción
    if (!confirm('¿Desea mover esta orden a la nueva fecha/operario?')) {
        draggedElement.classList.remove('dragging');
        draggedElement = null;
        draggedData = null;
        return;
    }
    
    // Mostrar loading
    const originalHTML = draggedElement.innerHTML;
    draggedElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
    
    // Actualizar en el servidor
    fetch('<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=actualizar_asignacion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id_orden: ordenId,
            fecha_limite: nuevaFecha,
            id_usuario_asignado: nuevoOperarioId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar la página para reflejar los cambios
            window.location.reload();
        } else {
            alert('Error al actualizar: ' + (data.message || 'Error desconocido'));
            draggedElement.innerHTML = originalHTML;
            draggedElement.classList.remove('dragging');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar la asignación');
        draggedElement.innerHTML = originalHTML;
        draggedElement.classList.remove('dragging');
    });
    
    draggedElement = null;
    draggedData = null;
}

// Restaurar opacidad si se cancela el drag
document.addEventListener('dragend', function(e) {
    if (draggedElement) {
        draggedElement.classList.remove('dragging');
        draggedElement = null;
        draggedData = null;
    }
});

// Navegación de semanas
function navegarSemana(direccion) {
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    const vista = document.getElementById('vista-select').value;
    
    let desde = new Date(fechaDesde.value);
    let hasta = new Date(fechaHasta.value);
    const diff = Math.ceil((hasta - desde) / (1000 * 60 * 60 * 24));
    
    if (vista === 'semanal') {
        desde.setDate(desde.getDate() + (direccion * 7));
        hasta.setDate(hasta.getDate() + (direccion * 7));
    } else if (vista === 'mensual') {
        desde.setMonth(desde.getMonth() + direccion);
        hasta = new Date(desde);
        hasta.setMonth(hasta.getMonth() + 1);
        hasta.setDate(0); // Último día del mes
    } else {
        desde.setDate(desde.getDate() + direccion);
        hasta = new Date(desde);
    }
    
    fechaDesde.value = desde.toISOString().split('T')[0];
    fechaHasta.value = hasta.toISOString().split('T')[0];
    document.getElementById('cronograma-filters').submit();
}

function irHoy() {
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    const vista = document.getElementById('vista-select').value;
    const hoy = new Date();
    
    if (vista === 'semanal') {
        const inicioSemana = new Date(hoy);
        inicioSemana.setDate(hoy.getDate() - hoy.getDay());
        const finSemana = new Date(inicioSemana);
        finSemana.setDate(inicioSemana.getDate() + 6);
        
        fechaDesde.value = inicioSemana.toISOString().split('T')[0];
        fechaHasta.value = finSemana.toISOString().split('T')[0];
    } else if (vista === 'mensual') {
        const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        
        fechaDesde.value = inicioMes.toISOString().split('T')[0];
        fechaHasta.value = finMes.toISOString().split('T')[0];
    } else {
        fechaDesde.value = hoy.toISOString().split('T')[0];
        fechaHasta.value = hoy.toISOString().split('T')[0];
    }
    
    document.getElementById('cronograma-filters').submit();
}

function actualizarFechasPorVista() {
    const vista = document.getElementById('vista-select').value;
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    const hoy = new Date();
    
    if (vista === 'semanal') {
        const inicioSemana = new Date(hoy);
        inicioSemana.setDate(hoy.getDate() - hoy.getDay());
        const finSemana = new Date(inicioSemana);
        finSemana.setDate(inicioSemana.getDate() + 6);
        
        fechaDesde.value = inicioSemana.toISOString().split('T')[0];
        fechaHasta.value = finSemana.toISOString().split('T')[0];
    } else if (vista === 'mensual') {
        const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        
        fechaDesde.value = inicioMes.toISOString().split('T')[0];
        fechaHasta.value = finMes.toISOString().split('T')[0];
    } else {
        fechaDesde.value = hoy.toISOString().split('T')[0];
        fechaHasta.value = hoy.toISOString().split('T')[0];
    }
}

function limpiarFiltros() {
    const form = document.getElementById('cronograma-filters');
    form.reset();
    actualizarFechasPorVista();
    form.submit();
}

function verDetalleOrden(idOrden) {
    window.location.href = '<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=ver&id=' + idOrden;
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
