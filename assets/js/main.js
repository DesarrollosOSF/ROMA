/**
 * JavaScript Principal del Sistema ROMA
 */

// Toggle del menú móvil
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.getElementById('navbarMenu');

    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (!navbarToggle.contains(event.target) && !navbarMenu.contains(event.target)) {
                navbarMenu.classList.remove('active');
            }
        });
    }

    // Manejar dropdowns del menú en móvil
    const dropdownItems = document.querySelectorAll('.nav-item-dropdown > a');
    dropdownItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const parent = this.parentElement;
                parent.classList.toggle('active');
            }
        });
    });
    
    // Auto-ocultar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Validación de formularios
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    });

    // Formatear números con separadores
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function(input) {
        if (input.name === 'valor_adquisicion' || input.name === 'kilometraje' || input.name === 'horas_uso') {
            input.addEventListener('blur', function() {
                if (this.value) {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        // Opcional: formatear visualmente
                    }
                }
            });
        }
    });

    // Búsqueda Global Rápida
    initGlobalSearch();
    
    // Sistema de Notificaciones
    initNotifications();
    
    // Atajos de Teclado
    initKeyboardShortcuts();
    
    // Cerrar menú al hacer clic en un enlace (mejora UX)
    const menuLinks = navbarMenu ? navbarMenu.querySelectorAll('a') : [];
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            const parentDropdown = link.closest('.nav-item-dropdown');
            const hasSubmenu = parentDropdown && parentDropdown.querySelector('.dropdown-menu');
            const isMobile = window.innerWidth <= 768;
            const isParentLink = parentDropdown && parentDropdown.firstElementChild === link;

            if (isMobile && hasSubmenu && isParentLink) {
                // Solo despliega el submenú, no cerramos el menú principal todavía
                return;
            }

            if (navbarMenu) {
                navbarMenu.classList.remove('active');
            }
        });
    });

    initUserMenuToggle();
});

function initUserMenuToggle() {
    const navbarUser = document.querySelector('.navbar-user');
    const toggle = navbarUser ? navbarUser.querySelector('.user-menu-toggle') : null;

    if (!navbarUser || !toggle) {
        return;
    }

    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        navbarUser.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
        if (!navbarUser.contains(e.target)) {
            navbarUser.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            navbarUser.classList.remove('open');
        }
    });
}

// Búsqueda Global Rápida
function initGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    if (!searchInput) {
        console.warn('Campo de búsqueda global no encontrado');
        return;
    }
    
    if (!searchResults) {
        console.warn('Contenedor de resultados de búsqueda no encontrado');
        return;
    }

    console.log('Búsqueda global inicializada correctamente');

    // Atajo de teclado Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('active');
            return;
        }

        searchTimeout = setTimeout(function() {
            buscarGlobal(query);
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            buscarGlobal(this.value.trim());
        }
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        const target = e.target;
        const isClickInside = searchInput.contains(target) || 
                              searchResults.contains(target) ||
                              target.closest('.global-search-container');
        
        if (!isClickInside) {
            searchResults.classList.remove('active');
        }
    });
    
    // Prevenir que Enter envíe el formulario
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query.length >= 2) {
                buscarGlobal(query);
            }
        }
    });
}

function buscarGlobal(query) {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) {
        console.error('Elemento searchResults no encontrado');
        return;
    }
    
    // Obtener BASE_URL de diferentes formas
    let baseUrl = window.BASE_URL;
    if (!baseUrl) {
        // Intentar obtener de la ruta actual
        const path = window.location.pathname;
        const match = path.match(/^(.+\/)/);
        baseUrl = match ? match[1] : '/Desarrollos/Roma/';
    }
    
    // Asegurar que termine con /
    if (!baseUrl.endsWith('/')) {
        baseUrl += '/';
    }
    
    const url = baseUrl + 'index.php?action=buscar&q=' + encodeURIComponent(query);
    
    console.log('Buscando:', query);
    console.log('URL:', url);
    
    // Mostrar indicador de carga
    searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-spinner fa-spin"></i><p>Buscando...</p></div>';
    searchResults.classList.add('active');
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Respuesta recibida:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error('Error en la respuesta: ' + response.status);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', text);
                throw new Error('Respuesta no es JSON válido');
            }
        });
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        if (data && data.resultados) {
            mostrarResultadosBusqueda(data.resultados, baseUrl);
        } else {
            searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-search"></i><p>No se encontraron resultados</p></div>';
        }
        searchResults.classList.add('active');
    })
    .catch(error => {
        console.error('Error en búsqueda:', error);
        searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-exclamation-triangle"></i><p>Error al buscar: ' + error.message + '</p></div>';
        searchResults.classList.add('active');
    });
}

function mostrarResultadosBusqueda(resultados, baseUrl) {
    const searchResults = document.getElementById('searchResults');
    let html = '';

    if (resultados.activos && resultados.activos.length > 0) {
        html += '<div class="search-section"><h5><i class="fas fa-box"></i> Activos</h5><ul>';
        resultados.activos.forEach(function(activo) {
            html += `<li>
                <a href="${baseUrl}index.php?action=activos&subaction=ver&id=${activo.id_activo}">
                    <strong>${escapeHtml(activo.nombre_activo)}</strong>
                    ${activo.codigo_interno ? '<small>' + escapeHtml(activo.codigo_interno) + '</small>' : ''}
                </a>
            </li>`;
        });
        html += '</ul></div>';
    }

    if (resultados.ordenes && resultados.ordenes.length > 0) {
        html += '<div class="search-section"><h5><i class="fas fa-clipboard-list"></i> Órdenes</h5><ul>';
        resultados.ordenes.forEach(function(orden) {
            html += `<li>
                <a href="${baseUrl}index.php?action=ordenes&subaction=ver&id=${orden.id_orden}">
                    <strong>${escapeHtml(orden.numero_radicado)}</strong>
                    <small>${escapeHtml(orden.descripcion_corta || '')}</small>
                </a>
            </li>`;
        });
        html += '</ul></div>';
    }

    if (resultados.usuarios && resultados.usuarios.length > 0) {
        html += '<div class="search-section"><h5><i class="fas fa-users"></i> Usuarios</h5><ul>';
        resultados.usuarios.forEach(function(usuario) {
            html += `<li>
                <a href="${baseUrl}index.php?action=usuarios&subaction=ver&id=${usuario.id_usuario}">
                    <strong>${escapeHtml(usuario.nombre)}</strong>
                    <small>${escapeHtml(usuario.email || '')}</small>
                </a>
            </li>`;
        });
        html += '</ul></div>';
    }

    if (!html) {
        html = '<div class="search-empty"><i class="fas fa-search"></i><p>No se encontraron resultados</p></div>';
    }

    searchResults.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Sistema de Notificaciones
function initNotifications() {
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationBadge = document.getElementById('notificationBadge');

    if (!notificationsToggle) return;

    // Toggle dropdown
    notificationsToggle.addEventListener('click', function(e) {
        e.preventDefault();
        notificationsDropdown.classList.toggle('active');
        cargarNotificaciones();
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!notificationsToggle.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.remove('active');
        }
    });

    // Marcar todas como leídas
    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            const baseUrl = window.BASE_URL || '/Desarrollos/Roma/';
            fetch(baseUrl + 'index.php?action=notificaciones&subaction=marcar_todas_leidas', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cargarNotificaciones();
                    }
                });
        });
    }

    // Cargar notificaciones cada 30 segundos
    setInterval(cargarNotificaciones, 30000);
    cargarNotificaciones();
}

function cargarNotificaciones() {
    const notificationsList = document.getElementById('notificationsList');
    const notificationBadge = document.getElementById('notificationBadge');
    const baseUrl = window.BASE_URL || '/Desarrollos/Roma/';

    if (!notificationsList) return;

    fetch(baseUrl + 'index.php?action=notificaciones&subaction=obtener')
        .then(response => response.json())
        .then(data => {
            const notificaciones = data.notificaciones || [];
            
            // Actualizar badge
            if (notificationBadge) {
                if (notificaciones.length > 0) {
                    notificationBadge.textContent = notificaciones.length;
                    notificationBadge.style.display = 'inline-block';
                } else {
                    notificationBadge.style.display = 'none';
                }
            }

            // Mostrar notificaciones
            if (notificaciones.length === 0) {
                notificationsList.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No hay notificaciones</p>
                    </div>
                `;
            } else {
                let html = '';
                notificaciones.forEach(function(notif) {
                    html += `
                        <div class="notification-item" data-id="${notif.id_notificacion}">
                            <div class="notification-icon">
                                <i class="${notif.icono || 'fas fa-bell'}"></i>
                            </div>
                            <div class="notification-content">
                                <h6>${escapeHtml(notif.titulo || '')}</h6>
                                <p>${escapeHtml(notif.mensaje || '')}</p>
                                <small>${escapeHtml(notif.tiempo || '')}</small>
                            </div>
                            ${notif.url ? `<a href="${notif.url}" class="notification-link"></a>` : ''}
                        </div>
                    `;
                });
                notificationsList.innerHTML = html;

                // Marcar como leída al hacer clic
                notificationsList.querySelectorAll('.notification-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const baseUrl = window.BASE_URL || '/Desarrollos/Roma/';
                        fetch(baseUrl + 'index.php?action=notificaciones&subaction=marcar_leida', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'id_notificacion=' + id
                        });
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar notificaciones:', error);
        });
}

// Atajos de Teclado
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+N: Nueva orden (si tiene permiso)
        if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            const nuevaOrdenLink = document.querySelector('a[href*="ordenes&subaction=crear"]');
            if (nuevaOrdenLink) {
                window.location.href = nuevaOrdenLink.href;
            }
        }

        // Ctrl+A: Nuevo activo (si tiene permiso)
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            const nuevoActivoLink = document.querySelector('a[href*="activos&subaction=crear"]');
            if (nuevoActivoLink) {
                window.location.href = nuevoActivoLink.href;
            }
        }

        // Esc: Cerrar modales/dropdowns
        if (e.key === 'Escape') {
            document.querySelectorAll('.notifications-dropdown.active').forEach(function(dropdown) {
                dropdown.classList.remove('active');
            });
            document.getElementById('searchResults')?.classList.remove('active');
        }
    });
}

// Funciones de utilidad
function confirmarEliminacion(mensaje) {
    return confirm(mensaje || '¿Está seguro de realizar esta acción?');
}

function mostrarCargando() {
    const loader = document.createElement('div');
    loader.id = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    document.body.appendChild(loader);
}

function ocultarCargando() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.remove();
    }
}

