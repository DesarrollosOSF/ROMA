<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema ROMA - <?php echo $page_title ?? 'Gestión de Mantenimiento'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/favicon.ico">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Variable global para BASE_URL en JavaScript
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <i class="fas fa-cogs"></i>
                <div class="brand-text">
                    <span>Sistema ROMA</span>
                    <small class="brand-legend">Registro y Operación de Mantenimiento Automatizado</small>
                </div>
            </div>
            <button class="navbar-toggle" id="navbarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Búsqueda Global Rápida -->
            <div class="global-search-container">
                <div class="global-search">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="globalSearch" 
                           placeholder="Buscar activos, órdenes, usuarios... (Ctrl+K)" 
                           autocomplete="off">
                    <div id="searchResults" class="search-results"></div>
                </div>
            </div>

            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="<?php echo BASE_URL; ?>index.php?action=dashboard" 
                       class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <?php 
                // Mostrar Activos para todos los roles (administrador siempre, operario puede ver)
                $rol_actual = $_SESSION['usuario_rol'] ?? '';
                $mostrar_activos = false;
                if ($rol_actual === 'administrador') {
                    $mostrar_activos = true;
                } elseif (Usuario::tienePermiso($rol_actual, 'ver_lista_activos')) {
                    $mostrar_activos = true;
                }
                
                if ($mostrar_activos): 
                ?>
                <li class="nav-item-dropdown">
                    <a href="<?php echo BASE_URL; ?>index.php?action=activos" 
                       class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'activos') ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Activos
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>index.php?action=activos">
                            <i class="fas fa-list"></i> Lista de Activos
                        </a></li>
                        <?php if (Usuario::tienePermiso($rol_actual, 'crear_activos')): ?>
                        <li><a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=crear">
                            <i class="fas fa-plus-circle"></i> Nuevo Activo
                        </a></li>
                        <li><a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=carga_masiva">
                            <i class="fas fa-file-upload"></i> Carga Masiva
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'jefe', 'director', 'operario'])): ?>
                    <li class="nav-item-dropdown">
                        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" 
                           class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'ordenes' && (!isset($_GET['subaction']) || $_GET['subaction'] === 'index')) ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i> Órdenes
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>index.php?action=ordenes">
                                <i class="fas fa-clipboard-list"></i> Lista de Órdenes
                            </a></li>
                            <?php if (Usuario::tienePermiso($rol_actual, 'crear_ordenes')): ?>
                            <li><a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=crear">
                                <i class="fas fa-plus-square"></i> Nueva Orden
                            </a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=cronograma"
                                   class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'ordenes' && isset($_GET['subaction']) && $_GET['subaction'] === 'cronograma') ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-alt"></i> Cronograma
                            </a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['jefe', 'director'])): ?>
                    <li><a href="#" class="disabled">
                        <i class="fas fa-file-alt"></i> Solicitudes
                    </a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador'): ?>
                    <li class="nav-item-dropdown">
                        <a href="<?php echo BASE_URL; ?>index.php?action=usuarios" 
                           class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'usuarios') ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Usuarios
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>index.php?action=usuarios">
                                <i class="fas fa-address-book"></i> Lista de Usuarios
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=crear">
                                <i class="fas fa-user-plus"></i> Nuevo Usuario
                            </a></li>
                        </ul>
                    </li>
                    <li><a href="<?php echo BASE_URL; ?>index.php?action=configuracion" 
                           class="<?php echo (isset($_GET['action']) && $_GET['action'] === 'configuracion') ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Configuración
                    </a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['usuario_autenticado'])): ?>
                    <!-- Notificaciones -->
                    <li class="navbar-notifications">
                        <a href="#" class="notifications-toggle" id="notificationsToggle">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        </a>
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notifications-header">
                                <h4>Notificaciones</h4>
                                <button class="mark-all-read" id="markAllRead">Marcar todas como leídas</button>
                            </div>
                            <div class="notifications-list" id="notificationsList">
                                <div class="notification-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No hay notificaciones</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    <li class="navbar-user">
                        <a href="#" class="user-menu-toggle">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                            <span class="user-role-badge"><?php echo USER_ROLES[$_SESSION['usuario_rol']] ?? $_SESSION['usuario_rol']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <ul class="user-menu">
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php?action=perfil">
                                    <i class="fas fa-user"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php?action=auth&subaction=logout">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'info'; ?>">
                    <?php 
                    echo $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                    ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

