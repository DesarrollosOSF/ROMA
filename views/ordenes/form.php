<?php
// Asegurar que $orden existe
if (!isset($orden)) {
    $orden = [];
}
// Asegurar que $rutas existe
if (!isset($rutas)) {
    $rutas = defined('ASSET_CATEGORIES') ? ASSET_CATEGORIES : [];
}
$page_title = (isset($orden['id_orden'])) ? 'Editar Orden de Trabajo' : 'Nueva Orden de Trabajo';
require_once __DIR__ . '/../layout/header.php';
$is_editing = isset($orden['id_orden']);
$solicitante_id = $orden['id_solicitante'] ?? ($_SESSION['usuario_id'] ?? null);
$solicitante_nombre = '';
$solicitante_email = '';

if (!empty($usuarios)) {
    foreach ($usuarios as $usuario) {
        if ($usuario['id_usuario'] == $solicitante_id) {
            $solicitante_nombre = $usuario['nombre'] ?? '';
            $solicitante_email = $usuario['email'] ?? '';
            break;
        }
    }
}

if (empty($solicitante_nombre) && !empty($orden['nombre_solicitante'] ?? '')) {
    $solicitante_nombre = $orden['nombre_solicitante'];
}

if (empty($solicitante_email) && !empty($orden['email_solicitante'] ?? '')) {
    $solicitante_email = $orden['email_solicitante'];
}

if (empty($solicitante_nombre)) {
    $solicitante_nombre = $_SESSION['usuario_nombre'] ?? '';
}

if (empty($solicitante_email)) {
    $solicitante_email = $_SESSION['usuario_email'] ?? '';
}
?>

<div class="page-header">
    <h1>
        <i class="fas fa-clipboard-list"></i> 
        <?php echo $is_editing ? 'Editar Orden de Trabajo' : 'Nueva Orden de Trabajo'; ?>
    </h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-<?php echo $is_editing ? 'edit' : 'plus'; ?>"></i> 
            <?php echo $is_editing ? 'Editar Orden' : 'Crear Nueva Orden'; ?>
        </h3>
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

        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=<?php echo $is_editing ? 'actualizar&id=' . $orden['id_orden'] : 'guardar'; ?>" 
              enctype="multipart/form-data">
            
            <div class="form-grid">
                <?php if ($is_editing): ?>
                <div class="form-group">
                    <label for="numero_radicado">Número de Radicado</label>
                    <input type="text" id="numero_radicado" name="numero_radicado" 
                           value="<?php echo htmlspecialchars($orden['numero_radicado'] ?? ''); ?>" 
                           readonly class="form-control" style="background-color: #e9ecef;">
                    <small class="form-text text-muted">Generado automáticamente</small>
                </div>
                <?php 
                // Obtener la ruta del activo asociado en modo edición
                $ruta_activo_orden = null;
                if (!empty($orden['id_activo']) && !empty($activos)) {
                    foreach ($activos as $act) {
                        if ($act['id_activo'] == $orden['id_activo']) {
                            $ruta_activo_orden = $act['ruta'] ?? null;
                            break;
                        }
                    }
                }
                ?>
                <?php if ($ruta_activo_orden && !empty($rutas[$ruta_activo_orden])): ?>
                <div class="form-group">
                    <label>Ruta del Activo</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($rutas[$ruta_activo_orden]); ?>" 
                           readonly style="background-color: #e9ecef;">
                    <small class="form-text text-muted">Ruta del activo asociado</small>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!$is_editing): ?>
                <div class="form-group">
                    <label for="ruta">Ruta <span class="text-danger">*</span></label>
                    <select id="ruta" class="form-control" required>
                        <option value="">Seleccione una ruta...</option>
                        <?php if (!empty($rutas)): ?>
                            <?php foreach ($rutas as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>">
                                    <?php echo htmlspecialchars($nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Seleccione la ruta para filtrar los activos disponibles</small>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="id_activo">Activo Asociado <span class="text-danger">*</span></label>
                    <?php if ($is_editing && !empty($orden['id_activo'])): ?>
                        <input type="hidden" name="id_activo" value="<?php echo (int)$orden['id_activo']; ?>">
                    <?php endif; ?>
                    <select id="id_activo" name="<?php echo $is_editing ? 'id_activo_display' : 'id_activo'; ?>" required class="form-control" <?php echo (!$is_editing) ? 'disabled' : ''; ?>>
                        <option value=""><?php echo (!$is_editing) ? 'Seleccione primero una ruta' : 'Seleccione un activo'; ?></option>
                        <?php if ($is_editing && !empty($activos)): ?>
                            <?php foreach ($activos as $activo): ?>
                                <option value="<?php echo $activo['id_activo']; ?>" 
                                        <?php echo (isset($orden['id_activo']) && $orden['id_activo'] == $activo['id_activo']) ? 'selected' : ''; ?>
                                        data-ruta="<?php echo htmlspecialchars($activo['ruta'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($activo['nombre_activo']); ?>
                                    <?php if (!empty($activo['codigo_interno'])): ?>
                                        - <?php echo htmlspecialchars($activo['codigo_interno']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (!$is_editing): ?>
                        <small class="form-text text-muted">Este campo se habilitará después de seleccionar una ruta</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="solicitante_nombre">Solicitante <span class="text-danger">*</span></label>
                    <input type="text" id="solicitante_nombre" class="form-control" 
                           value="<?php echo htmlspecialchars(trim($solicitante_nombre . ' ' . ($solicitante_email ? '(' . $solicitante_email . ')' : ''))); ?>"
                           readonly>
                    <input type="hidden" name="id_solicitante" value="<?php echo htmlspecialchars($solicitante_id ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="id_usuario_asignado">Operario Asignado</label>
                    <select id="id_usuario_asignado" name="id_usuario_asignado" class="form-control">
                        <option value="">Sin asignar</option>
                        <?php if (!empty($operarios)): ?>
                            <?php foreach ($operarios as $operario): ?>
                                <option value="<?php echo $operario['id_usuario']; ?>" 
                                        <?php echo (isset($orden['id_usuario_asignado']) && $orden['id_usuario_asignado'] == $operario['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($operario['nombre']); ?>
                                    <?php if (!empty($operario['email'])): ?>
                                        - <?php echo htmlspecialchars($operario['email']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tipo_mantenimiento">Tipo de Mantenimiento <span class="text-danger">*</span></label>
                    <select id="tipo_mantenimiento" name="tipo_mantenimiento" required class="form-control">
                        <?php if (!empty($tipos_mantenimiento)): ?>
                            <?php foreach ($tipos_mantenimiento as $key => $nombre): ?>
                                <?php
                                $selected = '';
                                if (isset($orden['tipo_mantenimiento']) && $orden['tipo_mantenimiento'] === $key) {
                                    $selected = 'selected';
                                } elseif (!isset($orden['tipo_mantenimiento']) && $key === 'correctivo') {
                                    $selected = 'selected';
                                }
                                ?>
                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="correctivo" selected>Correctivo</option>
                            <option value="preventivo">Preventivo</option>
                            <option value="instalacion">Instalación</option>
                            <option value="documentos_tramites">Documentos y Trámites</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nivel_criticidad">Nivel de Criticidad <span class="text-danger">*</span></label>
                    <select id="nivel_criticidad" name="nivel_criticidad" required class="form-control">
                        <?php if (!empty($criticidades)): ?>
                            <?php foreach ($criticidades as $key => $nombre): ?>
                                <?php
                                $selected = '';
                                if (isset($orden['nivel_criticidad']) && $orden['nivel_criticidad'] === $key) {
                                    $selected = 'selected';
                                } elseif (!isset($orden['nivel_criticidad']) && $key === 'normal') {
                                    $selected = 'selected';
                                }
                                ?>
                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="normal" selected>Normal</option>
                            <option value="baja">Baja</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado_proceso">Estado del Proceso <span class="text-danger">*</span></label>
                    <?php if ($is_editing): ?>
                        <select id="estado_proceso" name="estado_proceso" required class="form-control">
                            <?php if (!empty($estados)): ?>
                                <?php foreach ($estados as $key => $nombre): ?>
                                    <?php
                                    $selected = '';
                                    if (isset($orden['estado_proceso']) && $orden['estado_proceso'] === $key) {
                                        $selected = 'selected';
                                    }
                                    ?>
                                    <option value="<?php echo $key; ?>" <?php echo $selected; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" id="estado_proceso" class="form-control" value="Recibido" readonly>
                        <input type="hidden" name="estado_proceso" value="recibido">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="fecha_limite_ejecucion">Fecha Límite de Ejecución</label>
                    <?php
                    // Calcular fecha por defecto: 6 días calendario desde hoy
                    $fecha_limite_default = '';
                    if (!isset($orden['fecha_limite_ejecucion']) || empty($orden['fecha_limite_ejecucion'])) {
                        $fecha_limite_default = date('Y-m-d', strtotime('+6 days'));
                    } else {
                        $fecha_limite_default = date('Y-m-d', strtotime($orden['fecha_limite_ejecucion']));
                    }
                    ?>
                    <input type="date" id="fecha_limite_ejecucion" name="fecha_limite_ejecucion" 
                           value="<?php echo htmlspecialchars($fecha_limite_default); ?>" 
                           class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion_corta">Descripción Corta <span class="text-danger">*</span></label>
                <input type="text" id="descripcion_corta" name="descripcion_corta" 
                       value="<?php echo htmlspecialchars($orden['descripcion_corta'] ?? ''); ?>" 
                       required maxlength="255" class="form-control" 
                       placeholder="Resumen breve de la orden">
            </div>

            <div class="form-group">
                <label for="descripcion_detallada">Descripción Detallada</label>
                <textarea id="descripcion_detallada" name="descripcion_detallada" 
                          rows="5" class="form-control" 
                          placeholder="Descripción completa de la orden de trabajo..."><?php echo htmlspecialchars($orden['descripcion_detallada'] ?? ''); ?></textarea>
            </div>

            <!-- Adjuntos -->
            <div class="form-group">
                <label for="adjuntos">Archivos Adjuntos</label>
                <div class="file-upload-area">
                    <input type="file" id="adjuntos" name="adjuntos[]" 
                           multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" 
                           class="file-input">
                    <div class="file-upload-info">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Arrastra archivos aquí o haz clic para seleccionar</p>
                        <small>Formatos permitidos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (Máx. 10MB por archivo)</small>
                    </div>
                    <div id="file-list" class="file-list"></div>
                </div>
                <?php if ($is_editing && !empty($orden['adjuntos'])): ?>
                    <div class="existing-files">
                        <h4>Archivos existentes:</h4>
                        <ul class="attachments-list">
                            <?php foreach ($orden['adjuntos'] as $adjunto): ?>
                                <li>
                                    <a href="<?php echo BASE_URL . 'uploads/' . basename($adjunto['ruta_archivo']); ?>" 
                                       target="_blank" class="attachment-link">
                                        <i class="fas fa-file"></i>
                                        <?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>
                                    </a>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($adjunto['fecha_subida'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Actualizar' : 'Guardar'; ?> Orden
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s;
    position: relative;
}

.file-upload-area:hover {
    border-color: var(--primary-color);
    background: #f0f4ff;
}

.file-upload-area.dragover {
    border-color: var(--primary-color);
    background: #e7f1ff;
}

.file-input {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

.file-upload-info {
    pointer-events: none;
}

.file-upload-info i {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.file-upload-info p {
    margin: 10px 0;
    font-weight: 500;
    color: var(--text-color);
}

.file-upload-info small {
    color: var(--text-light);
}

.file-list {
    margin-top: 20px;
    text-align: left;
}

.file-item {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.file-item-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.file-item-info i {
    color: var(--primary-color);
}

.file-item-remove {
    color: var(--danger-color);
    cursor: pointer;
    padding: 5px;
}

.file-item-remove:hover {
    color: #c82333;
}

.existing-files {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.existing-files h4 {
    margin-bottom: 10px;
    font-size: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('adjuntos');
    const fileList = document.getElementById('file-list');
    const uploadArea = document.querySelector('.file-upload-area');
    let selectedFiles = [];

    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });

    fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        handleFiles(files);
    });

    function handleFiles(files) {
        files.forEach(file => {
            if (validateFile(file)) {
                selectedFiles.push(file);
                displayFile(file);
            }
        });
        updateFileInput();
    }

    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (file.size > maxSize) {
            alert(`El archivo ${file.name} excede el tamaño máximo de 10MB`);
            return false;
        }

        if (!allowedExtensions.includes(extension)) {
            alert(`El archivo ${file.name} no tiene una extensión permitida`);
            return false;
        }

        return true;
    }

    function displayFile(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.dataset.fileName = file.name;

        const fileIcon = getFileIcon(file.name);
        const fileSize = formatFileSize(file.size);

        fileItem.innerHTML = `
            <div class="file-item-info">
                <i class="${fileIcon}"></i>
                <div>
                    <strong>${file.name}</strong>
                    <small class="text-muted">${fileSize}</small>
                </div>
            </div>
            <span class="file-item-remove" onclick="removeFile('${file.name}')">
                <i class="fas fa-times"></i>
            </span>
        `;

        fileList.appendChild(fileItem);
    }

    function getFileIcon(fileName) {
        const extension = fileName.split('.').pop().toLowerCase();
        const icons = {
            'pdf': 'fas fa-file-pdf text-danger',
            'jpg': 'fas fa-file-image text-info',
            'jpeg': 'fas fa-file-image text-info',
            'png': 'fas fa-file-image text-info',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary',
            'xls': 'fas fa-file-excel text-success',
            'xlsx': 'fas fa-file-excel text-success'
        };
        return icons[extension] || 'fas fa-file text-secondary';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function removeFile(fileName) {
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        const fileItem = document.querySelector(`.file-item[data-file-name="${fileName}"]`);
        if (fileItem) {
            fileItem.remove();
        }
        updateFileInput();
    }

    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fileInput.files = dataTransfer.files;
    }

    window.removeFile = removeFile;
});

// Filtro de activos por ruta
document.addEventListener('DOMContentLoaded', function() {
    const rutaSelect = document.getElementById('ruta');
    const activoSelect = document.getElementById('id_activo');
    
    if (!rutaSelect || !activoSelect) {
        return; // No está en modo creación
    }
    
    // Guardar todos los activos disponibles
    const todosLosActivos = <?php echo json_encode($activos ?? []); ?>;
    
    // Función para filtrar activos por ruta
    function filtrarActivosPorRuta(ruta) {
        // Limpiar opciones actuales (excepto la primera)
        activoSelect.innerHTML = '<option value="">Seleccione un activo</option>';
        
        if (!ruta) {
            activoSelect.disabled = true;
            activoSelect.innerHTML = '<option value="">Seleccione primero una ruta</option>';
            return;
        }
        
        // Filtrar activos por ruta
        const activosFiltrados = todosLosActivos.filter(function(activo) {
            return activo.ruta === ruta && activo.activo == 1;
        });
        
        if (activosFiltrados.length === 0) {
            activoSelect.innerHTML = '<option value="">No hay activos disponibles para esta ruta</option>';
            activoSelect.disabled = true;
            return;
        }
        
        // Habilitar el select y agregar opciones filtradas
        activoSelect.disabled = false;
        
        activosFiltrados.forEach(function(activo) {
            const option = document.createElement('option');
            option.value = activo.id_activo;
            
            let texto = activo.nombre_activo;
            if (activo.codigo_interno) {
                texto += ' - ' + activo.codigo_interno;
            }
            
            option.textContent = texto;
            activoSelect.appendChild(option);
        });
    }
    
    // Event listener para cambios en el select de ruta
    rutaSelect.addEventListener('change', function() {
        const rutaSeleccionada = this.value;
        filtrarActivosPorRuta(rutaSeleccionada);
    });
    
    // Si hay una ruta preseleccionada (en modo edición), filtrar automáticamente
    const rutaPreseleccionada = rutaSelect.value;
    if (rutaPreseleccionada) {
        filtrarActivosPorRuta(rutaPreseleccionada);
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

