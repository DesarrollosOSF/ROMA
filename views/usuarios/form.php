<?php
$page_title = isset($usuario) ? 'Editar Usuario' : 'Nuevo Usuario';
$is_edit = isset($usuario);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="page-header">
    <h1>
        <i class="fas fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i> 
        <?php echo $is_edit ? 'Editar Usuario' : 'Nuevo Usuario'; ?>
    </h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=usuarios" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Información del Usuario</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=<?php echo $is_edit ? 'actualizar&id=' . $usuario['id_usuario'] : 'guardar'; ?>">
            
            <div class="form-section">
                <h4><i class="fas fa-user"></i> Información Personal</h4>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Nombre Completo <span class="required">*</span></label>
                        <input type="text" name="nombre" 
                               value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" 
                               value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" 
                               value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-briefcase"></i> Información Laboral</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Rol <span class="required">*</span></label>
                        <select name="rol" required>
                            <?php foreach ($roles as $key => $nombre): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($usuario['rol']) && $usuario['rol'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cargo</label>
                        <input type="text" name="cargo" 
                               value="<?php echo htmlspecialchars($usuario['cargo'] ?? ''); ?>" 
                               placeholder="Ej: Técnico de Mantenimiento">
                    </div>
                    
                    <div class="form-group">
                        <label>Área</label>
                        <input type="text" name="area" 
                               value="<?php echo htmlspecialchars($usuario['area'] ?? ''); ?>" 
                               placeholder="Ej: Mantenimiento">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-lock"></i> Contraseña</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $is_edit ? 'Nueva Contraseña' : 'Contraseña'; ?> <?php echo $is_edit ? '' : '<span class="required">*</span>'; ?></label>
                        <input type="password" name="password" 
                               <?php echo $is_edit ? '' : 'required'; ?>
                               placeholder="<?php echo $is_edit ? 'Dejar vacío para mantener la actual' : 'Mínimo 6 caracteres'; ?>">
                        <?php if ($is_edit): ?>
                            <small>Dejar vacío si no desea cambiar la contraseña</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_edit): ?>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="activo">
                                <option value="1" <?php echo (isset($usuario['activo']) && $usuario['activo']) ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?php echo (isset($usuario['activo']) && !$usuario['activo']) ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="<?php echo BASE_URL; ?>index.php?action=usuarios" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

