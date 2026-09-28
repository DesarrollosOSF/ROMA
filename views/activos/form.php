<?php
$page_title = isset($activo) ? 'Editar Activo' : 'Nuevo Activo';
$is_edit = isset($activo);
require_once __DIR__ . '/../layout/header.php';
$ubicaciones_disponibles = $ubicaciones ?? (defined('ASSET_LOCATIONS') ? ASSET_LOCATIONS : []);
$ubicacion_actual = $activo['ubicacion'] ?? '';
?>

<div class="page-header">
    <h1>
        <i class="fas fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i> 
        <?php echo $is_edit ? 'Editar Activo' : 'Nuevo Activo'; ?>
    </h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Información del Activo</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=activos&subaction=<?php echo $is_edit ? 'actualizar&id=' . $activo['id_activo'] : 'guardar'; ?>" 
              enctype="multipart/form-data" id="activoForm">
            
            <div class="form-section">
                <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Nombre del Activo <span class="required">*</span></label>
                        <input type="text" name="nombre_activo" 
                               value="<?php echo htmlspecialchars($activo['nombre_activo'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>Código Interno</label>
                        <input type="text" name="codigo_interno" 
                               value="<?php echo htmlspecialchars($activo['codigo_interno'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Código Patrimonial</label>
                        <input type="text" name="codigo_patrimonial" 
                               value="<?php echo htmlspecialchars($activo['codigo_patrimonial'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label>Descripción General</label>
                        <textarea name="descripcion_general" rows="3" 
                                  placeholder="Descripción general del activo..."><?php echo htmlspecialchars($activo['descripcion_general'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group form-group-full">
                        <label>Foto principal del activo</label>
                        <div class="asset-photo-upload">
                            <?php if ($is_edit && !empty($activo['foto_principal'])): ?>
                                <div class="current-photo">
                                    <img src="<?php echo BASE_URL . 'uploads/' . $activo['foto_principal']; ?>" 
                                         alt="Foto actual del activo" class="asset-photo-preview">
                                    <p class="text-muted">Esta imagen se mostrará en reportes y listados. Cargue una nueva para reemplazarla.</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto_principal" id="fotoPrincipalInput" accept="image/*">
                            <div class="asset-photo-live-preview" id="assetPhotoPreview">
                                <p class="text-muted mb-2">Vista previa de la nueva imagen:</p>
                                <img src="#" alt="Vista previa del activo" class="asset-photo-preview">
                            </div>
                            <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF o WEBP. Tamaño máximo 10MB.</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Categoría <span class="required">*</span></label>
                        <select name="categoria" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($activo['categoria']) && $activo['categoria'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado Actual <span class="required">*</span></label>
                        <select name="estado_actual" required>
                            <?php foreach ($estados as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($activo['estado_actual']) && $activo['estado_actual'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-map-marker-alt"></i> Ubicación y Responsabilidad</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ubicación</label>
                        <select name="ubicacion">
                            <option value="">Seleccione una ubicación...</option>
                            <?php foreach ($ubicaciones_disponibles as $ubicacion_opcion): ?>
                                <option value="<?php echo htmlspecialchars($ubicacion_opcion); ?>"
                                    <?php echo ($ubicacion_actual === $ubicacion_opcion) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ubicacion_opcion); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (!empty($ubicacion_actual) && !in_array($ubicacion_actual, $ubicaciones_disponibles, true)): ?>
                                <option value="<?php echo htmlspecialchars($ubicacion_actual); ?>" selected>
                                    <?php echo htmlspecialchars($ubicacion_actual); ?> (Personalizada)
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Ruta <span class="required">*</span></label>
                        <select name="ruta" required>
                            <option value="">Seleccione una ruta...</option>
                            <?php foreach ($rutas as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($activo['ruta']) && $activo['ruta'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Responsable</label>
                        <select name="responsable">
                            <option value="">Seleccione un operario...</option>
                            <?php if (!empty($operarios)): ?>
                                <?php foreach ($operarios as $operario): ?>
                                    <option value="<?php echo htmlspecialchars($operario['id_usuario']); ?>" 
                                            <?php echo (isset($activo['responsable']) && $activo['responsable'] == $operario['id_usuario']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($operario['nombre'] . ' (' . $operario['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Área Asignada</label>
                        <select name="area_asignada">
                            <option value="">Seleccione un área...</option>
                            <?php foreach ($areas as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($activo['area_asignada']) && $activo['area_asignada'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-tag"></i> Especificaciones Técnicas</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" 
                               value="<?php echo htmlspecialchars($activo['marca'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Modelo</label>
                        <input type="text" name="modelo" 
                               value="<?php echo htmlspecialchars($activo['modelo'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Número de Serie</label>
                        <input type="text" name="numero_serie" 
                               value="<?php echo htmlspecialchars($activo['numero_serie'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-dollar-sign"></i> Información Financiera</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha de Adquisición</label>
                        <input type="date" name="fecha_adquisicion" 
                               value="<?php echo $activo['fecha_adquisicion'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Valor de Adquisición</label>
                        <input type="number" name="valor_adquisicion" step="0.01" 
                               value="<?php echo $activo['valor_adquisicion'] ?? 0; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Vida Útil Estimada (meses)</label>
                        <input type="number" name="vida_util_estimada" 
                               value="<?php echo $activo['vida_util_estimada'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-tachometer-alt"></i> Uso y Mantenimiento</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Kilometraje / Horas de Uso</label>
                        <input type="number" name="kilometraje" step="0.01" 
                               value="<?php echo $activo['kilometraje'] ?? 0; ?>" 
                               placeholder="Kilometraje o horas">
                    </div>
                    
                    <div class="form-group">
                        <label>Horas de Uso</label>
                        <input type="number" name="horas_uso" step="0.01" 
                               value="<?php echo $activo['horas_uso'] ?? 0; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Próximo Mantenimiento</label>
                        <input type="date" name="proximo_mantenimiento" 
                               value="<?php echo $activo['proximo_mantenimiento'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-comment"></i> Observaciones</h4>
                <div class="form-group form-group-full">
                    <textarea name="observaciones" rows="4" 
                              placeholder="Notas adicionales sobre el activo..."><?php echo htmlspecialchars($activo['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const input = document.getElementById('fotoPrincipalInput');
    const previewWrapper = document.getElementById('assetPhotoPreview');
    if (!input || !previewWrapper) {
        return;
    }

    const previewImage = previewWrapper.querySelector('img');
    previewWrapper.style.display = 'none';

    input.addEventListener('change', function(event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            previewWrapper.style.display = 'none';
            previewImage.src = '';
            return;
        }

        if (!file.type.startsWith('image/')) {
            previewWrapper.style.display = 'none';
            previewImage.src = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewWrapper.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

