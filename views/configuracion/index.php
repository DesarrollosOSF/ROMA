<?php
$page_title = 'Configuración del Sistema';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Configuración del Sistema</h1>
</div>

<div class="config-grid">
    <!-- Categorías de Activos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tags"></i> Categorías de Activos</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['categorias'] as $key => $nombre): ?>
                    <li>
                        <span class="badge badge-category"><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Estados de Activos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Estados de Activos</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['estados'] as $key => $nombre): ?>
                    <li>
                        <span class="badge badge-<?php echo $key; ?>"><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Tipos de Mantenimiento -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-wrench"></i> Tipos de Mantenimiento</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['tipos_mantenimiento'] as $key => $nombre): ?>
                    <li>
                        <span><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Estados de Órdenes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list"></i> Estados de Órdenes</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['estados_ordenes'] as $key => $nombre): ?>
                    <li>
                        <span><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Estados de Solicitudes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Estados de Solicitudes</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['estados_solicitudes'] as $key => $nombre): ?>
                    <li>
                        <span><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Niveles de Criticidad -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Niveles de Criticidad</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['niveles_criticidad'] as $key => $nombre): ?>
                    <li>
                        <span><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Roles de Usuario -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-shield"></i> Roles de Usuario</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['roles'] as $key => $nombre): ?>
                    <li>
                        <span class="badge badge-primary"><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Áreas Asignadas -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-building"></i> Áreas Asignadas</h3>
        </div>
        <div class="card-body">
            <ul class="config-list">
                <?php foreach ($configuraciones['areas'] as $key => $nombre): ?>
                    <li>
                        <span><?php echo htmlspecialchars($nombre); ?></span>
                        <code><?php echo htmlspecialchars($key); ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<style>
.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.config-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.config-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--light-color);
}

.config-list li:last-child {
    border-bottom: none;
}

.config-list code {
    background: var(--light-color);
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .config-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

