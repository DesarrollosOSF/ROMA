<?php
$page_title = 'Detalle de Orden de Trabajo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';
?>

<div class="page-header">
    <h1>
        <i class="fas fa-clipboard-list"></i> Orden de Trabajo: <?php echo htmlspecialchars($orden['numero_radicado']); ?>
    </h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'editar_ordenes')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=editar&id=<?php echo $orden['id_orden']; ?>" 
               class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] === 'success' ? 'success' : 'danger'; ?>">
        <?php 
        echo htmlspecialchars($_SESSION['mensaje']); 
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Información de la Orden</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Número de Radicado:</label>
                        <strong><?php echo htmlspecialchars($orden['numero_radicado']); ?></strong>
                    </div>

                    <div class="detail-item">
                        <label>Activo Asociado:</label>
                        <div class="asset-name-wrapper">
                            <?php if (!empty($orden['foto_principal'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . $orden['foto_principal']; ?>"
                                     alt="Foto del activo <?php echo htmlspecialchars($orden['nombre_activo'] ?? ''); ?>"
                                     class="asset-thumb">
                            <?php else: ?>
                                <span class="asset-thumb asset-thumb-placeholder">
                                    <i class="fas fa-image"></i>
                                </span>
                            <?php endif; ?>
                            <div class="asset-name-content">
                                <span class="asset-name-title"><?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?></span>
                                <?php if (!empty($orden['codigo_interno'])): ?>
                                    <span class="asset-name-subtitle text-muted">Código: <?php echo htmlspecialchars($orden['codigo_interno']); ?></span>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $orden['id_activo']; ?>" 
                                   class="btn btn-sm btn-link">
                                    <i class="fas fa-external-link-alt"></i> Ver Activo
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <label>Solicitante:</label>
                        <div>
                            <strong><?php echo htmlspecialchars($orden['nombre_solicitante'] ?? 'N/A'); ?></strong>
                            <?php if (!empty($orden['email_solicitante'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($orden['email_solicitante']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <label>Operario Asignado:</label>
                        <div>
                            <?php if ($orden['nombre_asignado']): ?>
                                <strong><?php echo htmlspecialchars($orden['nombre_asignado']); ?></strong>
                                <?php if (!empty($orden['email_asignado'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($orden['email_asignado']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Sin asignar</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <label>Tipo de Mantenimiento:</label>
                        <span class="badge badge-info">
                            <?php echo $tipos_mantenimiento[$orden['tipo_mantenimiento']] ?? $orden['tipo_mantenimiento']; ?>
                        </span>
                    </div>

                    <div class="detail-item">
                        <label>Nivel de Criticidad:</label>
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
                    </div>

                    <div class="detail-item">
                        <label>Estado del Proceso:</label>
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
                    </div>

                    <div class="detail-item">
                        <label>Fecha de Creación:</label>
                        <?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'])); ?>
                    </div>

                    <div class="detail-item">
                        <label>Fecha Límite de Ejecución:</label>
                        <?php if ($orden['fecha_limite_ejecucion']): ?>
                            <?php 
                            $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                            $hoy = new DateTime();
                            $dias_restantes = $hoy->diff($fecha_limite)->days;
                            
                            if ($fecha_limite < $hoy && $orden['estado_proceso'] !== 'finalizado') {
                                echo '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ';
                                echo $fecha_limite->format('d/m/Y');
                                echo ' (Vencida)</span>';
                            } elseif ($dias_restantes <= 3 && $orden['estado_proceso'] !== 'finalizado') {
                                echo '<span class="text-warning"><i class="fas fa-clock"></i> ';
                                echo $fecha_limite->format('d/m/Y');
                                echo ' (' . $dias_restantes . ' días restantes)</span>';
                            } else {
                                echo $fecha_limite->format('d/m/Y');
                            }
                            ?>
                        <?php else: ?>
                            <span class="text-muted">Sin fecha límite</span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <label><strong>Descripción Corta:</strong></label>
                    <p><?php echo nl2br(htmlspecialchars($orden['descripcion_corta'])); ?></p>
                </div>

                <?php if (!empty($orden['descripcion_detallada'])): ?>
                <div class="form-group">
                    <label><strong>Descripción Detallada:</strong></label>
                    <p><?php echo nl2br(htmlspecialchars($orden['descripcion_detallada'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comentarios -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-comments"></i> Comentarios</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($orden['comentarios'])): ?>
                    <div class="comments-list">
                        <?php foreach ($orden['comentarios'] as $comentario): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <strong><?php echo htmlspecialchars($comentario['nombre_usuario'] ?? 'Usuario'); ?></strong>
                                    <span class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])); ?>
                                    </span>
                                </div>
                                <div class="comment-body">
                                    <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay comentarios aún</p>
                <?php endif; ?>

                <hr>

                <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=agregar_comentario">
                    <input type="hidden" name="id_orden" value="<?php echo $orden['id_orden']; ?>">
                    <div class="form-group">
                        <label>Agregar Comentario:</label>
                        <textarea name="comentario" rows="3" class="form-control" required 
                                  placeholder="Escribe un comentario..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-comment"></i> Agregar Comentario
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <?php $extensiones_imagen = ['jpg', 'jpeg', 'png', 'gif', 'webp']; ?>
        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="action-buttons-vertical">
                    <?php 
                    $rol_actual = $_SESSION['usuario_rol'] ?? '';
                    $usuario_actual_id = $_SESSION['usuario_id'] ?? null;
                    ?>
                    
                    <button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#modalCronograma">
                        <i class="fas fa-calendar-alt"></i> Ver en Cronograma
                    </button>
                    
                    <?php 
                    // Cambiar estado (para operarios)
                    $puede_cambiar_estado = false;
                    if (Usuario::tienePermiso($rol_actual, 'cambiar_estado_orden')) {
                        if ($rol_actual === 'administrador') {
                            $puede_cambiar_estado = true;
                        } elseif ($rol_actual === 'operario' && $orden['id_usuario_asignado'] == $usuario_actual_id) {
                            $puede_cambiar_estado = true;
                        }
                    }
                    if ($puede_cambiar_estado): 
                    ?>
                        <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#modalCambiarEstado">
                            <i class="fas fa-check-circle"></i> Cambiar Estado
                        </button>
                    <?php endif; ?>
                    
                    <?php 
                    // Reasignar (para operarios y administradores)
                    $puede_reasignar = false;
                    if (Usuario::tienePermiso($rol_actual, 'reasignar_orden')) {
                        if ($rol_actual === 'administrador') {
                            $puede_reasignar = true;
                        } elseif ($rol_actual === 'operario' && $orden['id_usuario_asignado'] == $usuario_actual_id) {
                            $puede_reasignar = true;
                        }
                    }
                    if ($puede_reasignar): 
                    ?>
                        <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalReasignar">
                            <i class="fas fa-user-exchange"></i> Reasignar Orden
                        </button>
                    <?php endif; ?>
                    
                    <?php if (Usuario::tienePermiso($rol_actual, 'editar_ordenes')): ?>
                        <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalEditar">
                            <i class="fas fa-edit"></i> Editar Orden
                        </button>
                    <?php endif; ?>
                    
                    <?php if (Usuario::tienePermiso($rol_actual, 'eliminar_ordenes')): ?>
                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalEliminar">
                            <i class="fas fa-trash"></i> Eliminar Orden
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Adjuntos -->
        <?php if (!empty($orden['adjuntos'])): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
            </div>
            <div class="card-body">
                <ul class="attachments-list attachments-grid">
                    <?php foreach ($orden['adjuntos'] as $adjunto): 
                        $ext = strtolower(pathinfo($adjunto['nombre_archivo'], PATHINFO_EXTENSION));
                        $es_imagen = in_array($ext, $extensiones_imagen);
                        $url_archivo = BASE_URL . 'uploads/' . $adjunto['ruta_archivo'];
                    ?>
                        <li class="attachment-item">
                            <?php if ($es_imagen): ?>
                                <a href="<?php echo $url_archivo; ?>" class="attachment-link attachment-image-link" 
                                   data-lightbox="adjuntos" data-title="<?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>">
                                    <img src="<?php echo $url_archivo; ?>" alt="<?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>" class="attachment-thumb">
                                    <span class="attachment-name"><?php echo htmlspecialchars($adjunto['nombre_archivo']); ?></span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $url_archivo; ?>" target="_blank" class="attachment-link attachment-file-link">
                                    <span class="attachment-icon"><i class="fas fa-file-alt"></i></span>
                                    <span class="attachment-name"><?php echo htmlspecialchars($adjunto['nombre_archivo']); ?></span>
                                </a>
                            <?php endif; ?>
                            <small class="text-muted attachment-date">
                                <?php echo date('d/m/Y', strtotime($adjunto['fecha_subida'])); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de Cambios -->
        <?php if (!empty($historial)): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Historial de Cambios</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historial as $cambio): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <strong><?php echo htmlspecialchars($cambio['nombre_usuario'] ?? 'Usuario'); ?></strong>
                                    <span class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($cambio['fecha_cambio'])); ?>
                                    </span>
                                </div>
                                <div class="timeline-body">
                                    <?php
                                    $tipo_labels = [
                                        'creacion' => 'Orden creada',
                                        'estado' => 'Estado cambiado',
                                        'asignacion' => 'Asignada',
                                        'reasignacion' => 'Reasignada',
                                        'edicion' => 'Editada',
                                        'comentario' => 'Comentario agregado',
                                        'adjunto' => 'Adjunto agregado'
                                    ];
                                    ?>
                                    <strong><?php echo $tipo_labels[$cambio['tipo_cambio']] ?? $cambio['tipo_cambio']; ?></strong>
                                    <?php if ($cambio['campo_anterior'] && $cambio['campo_nuevo']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            // Mostrar etiqueta amigable del campo
                                            $campo_label = [
                                                'id_usuario_asignado' => 'Operario asignado',
                                                'estado_proceso' => 'Estado',
                                                'nivel_criticidad' => 'Criticidad',
                                                'id_activo' => 'Activo'
                                            ];
                                            $label = $campo_label[$cambio['campo_anterior']] ?? $cambio['campo_anterior'];
                                            echo htmlspecialchars($label); 
                                            ?>: 
                                            <span class="text-danger">
                                                <?php 
                                                // Si es un estado, mostrar el nombre del estado
                                                if ($cambio['campo_anterior'] === 'estado_proceso' && isset($estados[$cambio['valor_anterior']])) {
                                                    echo htmlspecialchars($estados[$cambio['valor_anterior']]);
                                                } elseif ($cambio['campo_anterior'] === 'nivel_criticidad' && isset($criticidades[$cambio['valor_anterior']])) {
                                                    echo htmlspecialchars($criticidades[$cambio['valor_anterior']]);
                                                } else {
                                                    echo htmlspecialchars($cambio['valor_anterior'] ?? 'N/A');
                                                }
                                                ?>
                                            </span>
                                            → 
                                            <span class="text-success">
                                                <?php 
                                                // Si es un estado, mostrar el nombre del estado
                                                if ($cambio['campo_nuevo'] === 'estado_proceso' && isset($estados[$cambio['valor_nuevo']])) {
                                                    echo htmlspecialchars($estados[$cambio['valor_nuevo']]);
                                                } elseif ($cambio['campo_nuevo'] === 'nivel_criticidad' && isset($criticidades[$cambio['valor_nuevo']])) {
                                                    echo htmlspecialchars($criticidades[$cambio['valor_nuevo']]);
                                                } else {
                                                    echo htmlspecialchars($cambio['valor_nuevo'] ?? 'N/A');
                                                }
                                                ?>
                                            </span>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($cambio['descripcion']): ?>
                                        <br>
                                        <em><?php echo nl2br(htmlspecialchars($cambio['descripcion'])); ?></em>
                                    <?php endif; ?>
                                    <?php if (!empty($cambio['ruta_evidencia'])): 
                                        $url_evidencia = BASE_URL . 'uploads/' . $cambio['ruta_evidencia'];
                                    ?>
                                        <div class="timeline-evidence">
                                            <a href="<?php echo $url_evidencia; ?>" class="timeline-evidence-link" data-lightbox="historial" data-title="Evidencia">
                                                <img src="<?php echo $url_evidencia; ?>" alt="Evidencia">
                                            </a>
                                        </div>
                                    <?php elseif ($cambio['tipo_cambio'] === 'adjunto' && !empty($orden['adjuntos'])): 
                                        $fecha_cambio_date = date('Y-m-d', strtotime($cambio['fecha_cambio']));
                                        $adjuntos_ese_dia = array_filter($orden['adjuntos'], function($a) use ($fecha_cambio_date) {
                                            return date('Y-m-d', strtotime($a['fecha_subida'])) === $fecha_cambio_date;
                                        });
                                        if (!empty($adjuntos_ese_dia)):
                                    ?>
                                        <div class="timeline-adjuntos-list">
                                            <?php foreach ($adjuntos_ese_dia as $a): 
                                                $ext = strtolower(pathinfo($a['nombre_archivo'], PATHINFO_EXTENSION));
                                                $es_img = in_array($ext, $extensiones_imagen);
                                                $url_a = BASE_URL . 'uploads/' . $a['ruta_archivo'];
                                            ?>
                                                <div class="timeline-adjunto-item">
                                                    <?php if ($es_img): ?>
                                                        <a href="<?php echo $url_a; ?>" class="timeline-evidence-link" data-lightbox="historial" data-title="<?php echo htmlspecialchars($a['nombre_archivo']); ?>">
                                                            <img src="<?php echo $url_a; ?>" alt="<?php echo htmlspecialchars($a['nombre_archivo']); ?>" class="timeline-adjunto-thumb">
                                                        </a>
                                                    <?php endif; ?>
                                                    <span class="timeline-adjunto-nombre"><i class="fas fa-file<?php echo $es_img ? '-image' : '-alt'; ?>"></i> <?php echo htmlspecialchars($a['nombre_archivo']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Lightbox para imágenes -->
<div class="modal modal-lightbox" id="modalLightbox" tabindex="-1">
    <div class="lightbox-backdrop">
        <button type="button" class="lightbox-close" aria-label="Cerrar">&times;</button>
        <img src="" alt="" id="lightboxImage">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>
</div>

<!-- Modal Ver Cronograma -->
<div class="modal" id="modalCronograma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ver en Cronograma</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Desea ver esta orden en el cronograma de trabajo?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=cronograma" class="btn btn-info">
                    <i class="fas fa-calendar-alt"></i> Ir al Cronograma
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Orden -->
<?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'editar_ordenes')): ?>
<div class="modal" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Orden</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Desea editar esta orden de trabajo?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=editar&id=<?php echo $orden['id_orden']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Eliminar Orden -->
<?php if (Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'eliminar_ordenes')): ?>
<div class="modal" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Orden</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>¿Está seguro de eliminar esta orden de trabajo?</strong></p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=eliminar&id=<?php echo $orden['id_orden']; ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Cambiar Estado -->
<?php if ($puede_cambiar_estado): ?>
<div class="modal" id="modalCambiarEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Cambiar Estado
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formCambiarEstado" onsubmit="cambiarEstado(event)" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_orden" value="<?php echo $orden['id_orden']; ?>">
                    
                    <div class="current-state-info">
                        <label class="text-muted">Estado Actual:</label>
                        <div class="current-state-badge">
                            <span class="badge badge-lg estado-<?php echo $orden['estado_proceso']; ?>">
                                <?php echo $estados[$orden['estado_proceso']] ?? $orden['estado_proceso']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nuevo Estado <span class="text-danger">*</span></label>
                        <select name="nuevo_estado" id="selectNuevoEstado" class="form-control" required>
                            <?php foreach ($estados as $key => $nombre): ?>
                                <?php if ($key !== $orden['estado_proceso']): ?>
                                <option value="<?php echo $key; ?>">
                                    <?php echo $nombre; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Comentario / Observación <span class="text-danger">*</span></label>
                        <textarea name="comentario" id="comentarioEstado" class="form-control" rows="3" required 
                                  placeholder="Describa el motivo del cambio..."></textarea>
                        <small class="text-muted">Mínimo 10 caracteres</small>
                    </div>

                    <div class="form-group evidencia-finalizacion" id="evidenciaFinalizacionWrapper" style="display: none;">
                        <label>Evidencia fotográfica <span class="text-danger">*</span></label>
                        <input type="file" name="evidencia_finalizacion" id="evidenciaFinalizacion" class="form-control-file"
                               accept="image/*">
                        <small class="text-muted">Formatos permitidos: JPG, PNG, GIF o WEBP. Tamaño máximo 10MB.</small>
                        <div class="evidencia-preview" id="evidenciaFinalizacionPreview" style="display: none;">
                            <img src="" alt="Vista previa evidencia de finalización">
                        </div>
                    </div>
                    
                    <div id="estadoError" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnCambiarEstado">
                        <i class="fas fa-check"></i> Cambiar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Reasignar -->
<?php if ($puede_reasignar): ?>
<div class="modal" id="modalReasignar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-exchange"></i> Reasignar Orden
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formReasignar" onsubmit="reasignarOrden(event)">
                <div class="modal-body">
                    <input type="hidden" name="id_orden" value="<?php echo $orden['id_orden']; ?>">
                    
                    <div class="current-assignment-info">
                        <label class="text-muted">Asignado Actualmente:</label>
                        <div class="current-assignment-badge">
                            <i class="fas fa-user"></i>
                            <strong><?php echo htmlspecialchars($orden['nombre_asignado'] ?? 'Sin asignar'); ?></strong>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nuevo Operario <span class="text-danger">*</span></label>
                        <select name="id_nuevo_operario" id="selectNuevoOperario" class="form-control" required>
                            <option value="">Seleccione un operario</option>
                            <?php if (!empty($operarios)): ?>
                                <?php foreach ($operarios as $operario): ?>
                                    <?php if ($orden['id_usuario_asignado'] != $operario['id_usuario']): ?>
                                    <option value="<?php echo $operario['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($operario['nombre']); ?>
                                        <?php if (!empty($operario['email'])): ?>
                                            (<?php echo htmlspecialchars($operario['email']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Comentario (Opcional)</label>
                        <textarea name="comentario" id="comentarioReasignar" class="form-control" rows="2" 
                                  placeholder="Motivo de la reasignación..."></textarea>
                    </div>
                    
                    <div id="reasignarError" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning" id="btnReasignar">
                        <i class="fas fa-user-exchange"></i> Reasignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.2s ease-in-out;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-dialog {
    background: white;
    border-radius: 0.5rem;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideDown 0.3s ease-out;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-dialog.modal-sm {
    max-width: 400px;
}

.current-state-info,
.current-assignment-info {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
    border-left: 3px solid var(--primary-color);
}

.current-state-info label,
.current-assignment-info label {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.current-state-badge,
.current-assignment-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
}

.estado-recibido {
    background-color: #17a2b8;
    color: white;
}

.estado-en_proceso {
    background-color: #ffc107;
    color: #333;
}

.estado-rechazado {
    background-color: #dc3545;
    color: white;
}

.estado-finalizado {
    background-color: #28a745;
    color: white;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
}

.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-light);
}

.close:hover {
    color: var(--text-color);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.evidencia-finalizacion .evidencia-preview {
    margin-top: 0.75rem;
}

.evidencia-finalizacion .evidencia-preview img {
    max-width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -0.5rem;
    top: 1.5rem;
    bottom: -1.5rem;
    width: 2px;
    background: var(--border-color);
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-color);
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-content {
    background: var(--light-color);
    padding: 1rem;
    border-radius: 0.5rem;
    border-left: 3px solid var(--primary-color);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.timeline-body {
    font-size: 0.9rem;
}

.timeline-evidence {
    margin-top: 0.75rem;
}

.timeline-evidence img {
    max-width: 220px;
    max-height: 160px;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: opacity 0.2s;
}

.timeline-evidence img:hover {
    opacity: 0.9;
}

.timeline-adjuntos-list {
    margin-top: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: flex-start;
}

.timeline-adjunto-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 140px;
}

.timeline-adjunto-thumb {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.35rem;
    border: 1px solid var(--border-color);
    cursor: pointer;
    margin-bottom: 0.25rem;
}

.timeline-adjunto-nombre {
    font-size: 0.8rem;
    word-break: break-word;
    text-align: center;
    color: var(--text-color);
}

.timeline-adjunto-nombre i {
    margin-right: 0.25rem;
    color: var(--primary-color);
}

/* Adjuntos: grid con miniatura y nombre */
.attachments-list.attachments-grid {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}

.attachments-list .attachment-item {
    margin: 0;
    padding: 0;
}

.attachment-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    background: var(--light-color);
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.2s, border-color 0.2s;
}

.attachment-link:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.attachment-thumb {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 0.35rem;
    margin-bottom: 0.5rem;
}

.attachment-name {
    font-size: 0.85rem;
    word-break: break-word;
    line-height: 1.2;
}

.attachment-icon {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.attachment-date {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.75rem;
}

/* Lightbox */
.modal-lightbox .lightbox-backdrop {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    max-width: 95vw;
    max-height: 95vh;
    padding: 2rem;
}

.modal-lightbox .lightbox-close {
    position: absolute;
    top: 0.5rem;
    right: 1rem;
    z-index: 10;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.modal-lightbox .lightbox-close:hover {
    background: rgba(0,0,0,0.8);
}

#lightboxImage {
    max-width: 90vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.lightbox-caption {
    margin-top: 0.75rem;
    color: white;
    text-align: center;
    font-size: 0.9rem;
}
</style>

<script>
const MAX_UPLOAD_SIZE = <?php echo (int) UPLOAD_MAX_SIZE; ?>;
// Funcionalidad mejorada de modales
document.addEventListener('DOMContentLoaded', function() {
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    const modals = document.querySelectorAll('.modal');
    
    // Abrir modales
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const modal = document.querySelector(targetId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevenir scroll del body
            }
        });
    });
    
    // Cerrar modales con botón close
    document.querySelectorAll('.close').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = ''; // Restaurar scroll
            }
        });
    });
    
    // Cerrar modales al hacer clic fuera
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = ''; // Restaurar scroll
            }
        });
    });
    
    // Prevenir cierre al hacer clic dentro del modal-dialog
    document.querySelectorAll('.modal-dialog').forEach(function(dialog) {
        dialog.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Cerrar con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(function(modal) {
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    document.body.style.overflow = ''; // Restaurar scroll
                }
            });
        }
    });

    // Lightbox para imágenes (adjuntos e historial)
    var lightboxModal = document.getElementById('modalLightbox');
    var lightboxImg = document.getElementById('lightboxImage');
    var lightboxCaption = document.getElementById('lightboxCaption');
    var lightboxClose = lightboxModal ? lightboxModal.querySelector('.lightbox-close') : null;

    function openLightbox(src, title) {
        if (!lightboxModal || !lightboxImg) return;
        lightboxImg.src = src;
        lightboxImg.alt = title || '';
        if (lightboxCaption) lightboxCaption.textContent = title || '';
        lightboxModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightboxModal) return;
        lightboxModal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.attachment-image-link, .timeline-evidence-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            var title = this.getAttribute('data-title') || (this.querySelector('img') && this.querySelector('img').alt) || '';
            openLightbox(href, title);
        });
    });

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxModal) {
        lightboxModal.addEventListener('click', function(e) {
            if (e.target === lightboxModal) closeLightbox();
        });
    }

    const selectEstado = document.getElementById('selectNuevoEstado');
    const evidenciaWrapper = document.getElementById('evidenciaFinalizacionWrapper');
    const evidenciaInput = document.getElementById('evidenciaFinalizacion');
    const evidenciaPreview = document.getElementById('evidenciaFinalizacionPreview');
    const evidenciaPreviewImg = evidenciaPreview ? evidenciaPreview.querySelector('img') : null;

    function actualizarEvidenciaFinalizacion() {
        if (!selectEstado || !evidenciaWrapper || !evidenciaInput) {
            return;
        }
        const requiere = selectEstado.value === 'finalizado';
        evidenciaWrapper.style.display = requiere ? 'block' : 'none';
        evidenciaInput.required = requiere;
        if (!requiere) {
            evidenciaInput.value = '';
            if (evidenciaPreview) {
                evidenciaPreview.style.display = 'none';
            }
        }
    }

    if (selectEstado) {
        selectEstado.addEventListener('change', actualizarEvidenciaFinalizacion);
        actualizarEvidenciaFinalizacion();
    }

    if (evidenciaInput && evidenciaPreview && evidenciaPreviewImg) {
        evidenciaInput.addEventListener('change', function() {
            const file = evidenciaInput.files && evidenciaInput.files[0];
            if (!file) {
                evidenciaPreview.style.display = 'none';
                evidenciaPreviewImg.src = '';
                return;
            }
            if (!file.type.startsWith('image/')) {
                evidenciaPreview.style.display = 'none';
                evidenciaPreviewImg.src = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                evidenciaPreviewImg.src = e.target.result;
                evidenciaPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
});

// Cambiar estado con AJAX
function cambiarEstado(event) {
    event.preventDefault();
    
    const form = event.target;
    const btnSubmit = form.querySelector('#btnCambiarEstado') || form.querySelector('button[type="submit"]');
    let errorDiv = form.querySelector('#estadoError');
    const comentarioEl = form.querySelector('#comentarioEstado') || form.querySelector('textarea[name="comentario"]');
    const selectEstado = form.querySelector('#selectNuevoEstado');
    const evidenciaInput = form.querySelector('#evidenciaFinalizacion');

    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'estadoError';
        errorDiv.className = 'alert alert-danger';
        errorDiv.style.display = 'none';
        const body = form.querySelector('.modal-body') || form;
        body.appendChild(errorDiv);
    }

    if (!btnSubmit || !comentarioEl) {
        console.error('Elementos requeridos no encontrados en el formulario de cambiar estado');
        return;
    }

    const comentario = comentarioEl.value.trim();
    
    // Validación
    if (comentario.length < 10) {
        errorDiv.textContent = 'El comentario debe tener al menos 10 caracteres';
        errorDiv.style.display = 'block';
        return;
    }

    if (selectEstado && selectEstado.value === 'finalizado') {
        if (!evidenciaInput || !evidenciaInput.files || evidenciaInput.files.length === 0) {
            errorDiv.textContent = 'Adjunte una evidencia fotográfica para finalizar la orden.';
            errorDiv.style.display = 'block';
            return;
        }
        const file = evidenciaInput.files[0];
        if (!file.type.startsWith('image/')) {
            errorDiv.textContent = 'La evidencia debe ser una imagen válida (JPG, PNG, GIF o WEBP).';
            errorDiv.style.display = 'block';
            return;
        }
        if (file.size > MAX_UPLOAD_SIZE) {
            errorDiv.textContent = 'La evidencia supera el tamaño máximo permitido (10MB).';
            errorDiv.style.display = 'block';
            return;
        }
    }
    
    const formData = new FormData(form);
    
    errorDiv.style.display = 'none';
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cambiando...';
    
    fetch('<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=cambiar_estado', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje('Estado actualizado correctamente', 'success');
            // Cerrar modal
            const modal = form.closest('.modal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            // Recargar página después de 1 segundo
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            errorDiv.textContent = data.message || 'Error al cambiar el estado';
            errorDiv.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-check"></i> Cambiar';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Error de conexión. Por favor, intente nuevamente.';
        errorDiv.style.display = 'block';
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-check"></i> Cambiar';
    });
}

// Reasignar orden con AJAX
function reasignarOrden(event) {
    event.preventDefault();
    
    const form = event.target;
    const btnSubmit = form.querySelector('#btnReasignar') || form.querySelector('button[type="submit"]');
    let errorDiv = form.querySelector('#reasignarError');
    const selectOperario = form.querySelector('#selectNuevoOperario') || form.querySelector('select[name="id_nuevo_operario"]');

    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'reasignarError';
        errorDiv.className = 'alert alert-danger';
        errorDiv.style.display = 'none';
        const body = form.querySelector('.modal-body') || form;
        body.appendChild(errorDiv);
    }

    if (!btnSubmit || !selectOperario) {
        console.error('Elementos requeridos no encontrados en el formulario de reasignación');
        return;
    }

    const nuevoOperario = selectOperario.value;
    
    // Validación
    if (!nuevoOperario) {
        errorDiv.textContent = 'Debe seleccionar un operario';
        errorDiv.style.display = 'block';
        return;
    }
    
    const formData = new FormData(form);
    
    errorDiv.style.display = 'none';
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reasignando...';
    
    fetch('<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=reasignar', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje('Orden reasignada correctamente', 'success');
            // Cerrar modal
            const modal = form.closest('.modal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            // Recargar página después de 1 segundo
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            errorDiv.textContent = data.message || 'Error al reasignar la orden';
            errorDiv.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-user-exchange"></i> Reasignar';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Error de conexión. Por favor, intente nuevamente.';
        errorDiv.style.display = 'block';
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-user-exchange"></i> Reasignar';
    });
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    mensajeDiv.style.position = 'fixed';
    mensajeDiv.style.top = '20px';
    mensajeDiv.style.right = '20px';
    mensajeDiv.style.zIndex = '9999';
    mensajeDiv.style.minWidth = '300px';
    mensajeDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    document.body.appendChild(mensajeDiv);
    
    // Auto-cerrar después de 3 segundos
    setTimeout(() => {
        mensajeDiv.remove();
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

