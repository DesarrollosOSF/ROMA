<?php
$page_title = 'Mi Perfil';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user"></i> Mi Perfil</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-edit"></i> Información Personal</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=perfil&subaction=actualizar">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" 
                                   required
                                   readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" 
                                   required
                                   readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" 
                                   id="telefono" 
                                   name="telefono" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="cargo">Cargo</label>
                            <input type="text" 
                                   id="cargo" 
                                   name="cargo" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['cargo'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="area">Área</label>
                            <select id="area" name="area" class="form-control" disabled>
                                <option value="">Seleccione un área</option>
                                <?php foreach (ASSIGNED_AREAS as $key => $nombre): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo (isset($usuario['area']) && $usuario['area'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="area" value="<?php echo htmlspecialchars($usuario['area'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="rol">Rol</label>
                            <input type="text" 
                                   id="rol" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(USER_ROLES[$usuario['rol']] ?? $usuario['rol'] ?? ''); ?>" 
                                   disabled>
                            <small class="text-muted">El rol no puede ser modificado</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h4><i class="fas fa-lock"></i> Cambiar Contraseña</h4>
                    <p class="text-muted">Deje estos campos vacíos si no desea cambiar la contraseña</p>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password">Nueva Contraseña</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   minlength="6"
                                   placeholder="Mínimo 6 caracteres">
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirmar Nueva Contraseña</label>
                            <input type="password" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   class="form-control" 
                                   minlength="6"
                                   placeholder="Repita la contraseña">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="<?php echo BASE_URL; ?>index.php?action=dashboard" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Información de Cuenta</h3>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <label>ID de Usuario:</label>
                    <span class="info-value"><?php echo htmlspecialchars($usuario['id_usuario'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="info-item">
                    <label>Estado:</label>
                    <span class="info-value">
                        <?php if (isset($usuario['activo']) && $usuario['activo']): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (isset($usuario['fecha_creacion'])): ?>
                <div class="info-item">
                    <label>Fecha de Registro:</label>
                    <span class="info-value">
                        <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shield-alt"></i> Seguridad</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Para cambiar su contraseña, complete los campos de "Cambiar Contraseña" en el formulario.
                </p>
                <p class="text-muted">
                    <i class="fas fa-lock"></i> 
                    Su contraseña está encriptada de forma segura y no puede ser vista por nadie, ni siquiera los administradores.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.info-item {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-item label {
    display: block;
    font-weight: 600;
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.info-value {
    display: block;
    color: var(--text-color);
    font-size: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

