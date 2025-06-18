<?php

$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <h2>👓 OpticLook</h2>
            <span>Administration</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <!-- Menu principal -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                </li>

                <!-- Gestion Produits -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'produit') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-box"></i>
                        <span class="nav-text">Produits</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'produit') !== false) ? 'open' : ''; ?>">
                        <li><a href="products.php" class="submenu-link <?php echo ($current_page == 'products') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> 
                            <span class="nav-text">Liste des produits</span>
                        </a></li>
                        <li><a href="products.php?action=add" class="submenu-link">
                            <i class="fas fa-plus"></i> 
                            <span class="nav-text">Ajouter produit</span>
                        </a></li>
                        <li><a href="categories.php" class="submenu-link">
                            <i class="fas fa-tags"></i> 
                            <span class="nav-text">Catégories</span>
                        </a></li>
                        <li><a href="stock.php" class="submenu-link">
                            <i class="fas fa-warehouse"></i> 
                            <span class="nav-text">Gestion stock</span>
                        </a></li>
                    </ul>
                </li>

                <!-- Gestion Clients -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'client') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Clients</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'client') !== false) ? 'open' : ''; ?>">
                        <li><a href="clients.php" class="submenu-link <?php echo ($current_page == 'clients') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> 
                            <span class="nav-text">Liste des clients</span>
                        </a></li>
                        <li><a href="clients.php?action=add" class="submenu-link">
                            <i class="fas fa-user-plus"></i> 
                            <span class="nav-text">Ajouter client</span>
                        </a></li>
                        <li><a href="clients-stats.php" class="submenu-link">
                            <i class="fas fa-chart-user"></i> 
                            <span class="nav-text">Statistiques</span>
                        </a></li>
                    </ul>
                </li>

                <!-- Gestion Rendez-vous -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'rdv') !== false || strpos($current_page, 'appointments') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Rendez-vous</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM prendre WHERE STATUS_RENDEZ_VOUS = 0");
                            $count = $stmt->fetch()['count'];
                            if ($count > 0): ?>
                                <span class="badge badge-warning"><?php echo $count; ?></span>
                            <?php endif;
                        } catch (Exception $e) {} ?>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'rdv') !== false || strpos($current_page, 'appointments') !== false) ? 'open' : ''; ?>">
                        <li><a href="appointments.php" class="submenu-link <?php echo ($current_page == 'appointments') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i> 
                            <span class="nav-text">Planning</span>
                        </a></li>
                        <li><a href="appointments.php?status=0" class="submenu-link">
                            <i class="fas fa-clock"></i> 
                            <span class="nav-text">En attente</span>
                            <?php if (isset($count) && $count > 0): ?>
                                <span class="badge badge-warning"><?php echo $count; ?></span>
                            <?php endif; ?>
                        </a></li>
                        <li><a href="appointments.php?status=1" class="submenu-link">
                            <i class="fas fa-check-circle"></i> 
                            <span class="nav-text">Confirmés</span>
                        </a></li>
                        <li><a href="appointments.php?status=2" class="submenu-link">
                            <i class="fas fa-times-circle"></i> 
                            <span class="nav-text">Annulés</span>
                        </a></li>
                    </ul>
                </li>

                <!-- Paramètres -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'setting') !== false || strpos($current_page, 'config') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-cog"></i>
                        <span class="nav-text">Paramètres</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'setting') !== false || strpos($current_page, 'config') !== false) ? 'open' : ''; ?>">
                        <li><a href="settings.php" class="submenu-link">
                            <i class="fas fa-sliders-h"></i> 
                            <span class="nav-text">Général</span>
                        </a></li>
                        <li><a href="users.php" class="submenu-link">
                            <i class="fas fa-user-cog"></i> 
                            <span class="nav-text">Utilisateurs</span>
                        </a></li>
                        <li><a href="backup.php" class="submenu-link">
                            <i class="fas fa-database"></i> 
                            <span class="nav-text">Sauvegarde</span>
                        </a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Actions rapides -->
        <div class="sidebar-quick-actions">
            <h4><i class="fas fa-bolt"></i> <span class="nav-text">Actions rapides</span></h4>
            <div class="quick-actions-list">
                <button class="quick-action-btn" onclick="quickAddProduct()" title="Ajouter un produit">
                    <i class="fas fa-plus"></i>
                    <span class="nav-text">Produit</span>
                </button>
                <button class="quick-action-btn" onclick="quickAddClient()" title="Ajouter un client">
                    <i class="fas fa-user-plus"></i>
                    <span class="nav-text">Client</span>
                </button>
                <button class="quick-action-btn" onclick="quickViewAppointments()" title="Voir les RDV">
                    <i class="fas fa-calendar"></i>
                    <span class="nav-text">RDV</span>
                </button>
                <button class="quick-action-btn" onclick="quickBackup()" title="Sauvegarde rapide">
                    <i class="fas fa-download"></i>
                    <span class="nav-text">Backup</span>
                </button>
            </div>
        </div>

        <!-- Informations système -->
        <div class="sidebar-system-info">
            <div class="system-info-item">
                <span class="info-label">Version:</span>
                <span class="info-value">v2.1.0</span>
            </div>
            <div class="system-info-item">
                <span class="info-label">Statut:</span>
                <span class="info-value status-online">
                    <i class="fas fa-circle"></i> En ligne
                </span>
            </div>
            <div class="system-info-item">
                <span class="info-label">Utilisateurs:</span>
                <span class="info-value"><?php echo $total_clients ?? 0; ?></span>
            </div>
        </div>
    </div>
</aside>

<style>
/* Sidebar Styles */
.admin-sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1000;
}

.admin-sidebar.collapsed {
    width:300px;
}

.admin-sidebar.collapsed .nav-text,
.admin-sidebar.collapsed .submenu,
.admin-sidebar.collapsed .sidebar-quick-actions h4 span,
.admin-sidebar.collapsed .quick-action-btn span,
.admin-sidebar.collapsed .sidebar-system-info {
    display: none;
}

.admin-sidebar.collapsed .submenu-arrow {
    display: none;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-logo h2 {
    color:rgb(79, 212, 130);
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.sidebar-logo span {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #666;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: #f0f0f0;
    color: #667eea;
}

.sidebar-content {
    padding: 20px 0;
}

.nav-list {
    list-style: none;
    padding: 0 15px;
    margin: 0;
}
.nav-list{
        display: grid;
    grid-template-columns: auto;
}


.nav-item {
    margin-bottom: 5px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #333;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.nav-link:hover,
.nav-link.active {
    background: linear-gradient(135deg,rgb(24, 122, 29), #53b259);
    color: white;
    transform: translateX(5px);
}

.nav-link i {
    margin-right: 12px;
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.nav-link .nav-text {
    flex: 1;
}

.submenu-arrow {
    margin-left: auto;
    font-size: 12px;
    transition: transform 0.3s ease;
}

.nav-link.active .submenu-arrow {
    transform: rotate(90deg);
}

.submenu {
    list-style: none;
    padding: 0;
    margin: 5px 0 0 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.submenu.open {
    max-height: 500px;

}

.submenu-link {
    display: flex;
    align-items: center;
    padding: 8px 15px 8px 50px;
    color: #666;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 14px;
    
}

.submenu-link:hover,
.submenu-link.active {
    background: rgba(102, 126, 234, 0.1);
    color:#347d3a;
    transform: translateX(3px);
}

.submenu-link i {
    margin-right: 10px;
    font-size: 14px;
    width: 16px;
    text-align: center;
}

.badge {
    background:#347d3a;
    color:rgb(0, 0, 0);
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
    margin-left: 8px;
}

.badge.badge-warning {
    background:#4fac55;
    color:rgb(7, 7, 6);
}

.sidebar-quick-actions {
    margin: 30px 15px 20px;
    padding: 20px;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.sidebar-quick-actions h4 {
    color: #333;
    font-size: 14px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-actions-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.admin-sidebar.collapsed .quick-actions-list {
    grid-template-columns: 1fr;
}

.quick-action-btn {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.quick-action-btn:hover {
    background: linear-gradient(135deg,rgb(99, 201, 116),rgb(74, 209, 119));
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 16px;
}

.quick-action-btn span {
    font-size: 11px;
    font-weight: 600;
}

.sidebar-system-info {
    margin: 20px 15px;
    padding: 15px;
    background: rgba(0, 0, 0, 0.02);
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.system-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
}

.system-info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    color: #666;
    font-weight: 500;
}

.info-value {
    color: #333;
    font-weight: 600;
}

.status-online {
    color: #00b894;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-online i {
    font-size: 8px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .admin-sidebar.collapsed {
        width: 280px;
    }
    
    .admin-sidebar.collapsed .nav-text,
    .admin-sidebar.collapsed .submenu,
    .admin-sidebar.collapsed .sidebar-quick-actions h4 span,
    .admin-sidebar.collapsed .quick-action-btn span,
    .admin-sidebar.collapsed .sidebar-system-info {
        display: block;
    }
}

/* Scrollbar Styling */
.admin-sidebar::-webkit-scrollbar {
    width: 4px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: #347d3a;
    border-radius: 2px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: #347d3a;
}
</style>

<script>
// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('collapsed');
    
    // Save state in localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebar-collapsed', isCollapsed);
}

// Toggle submenu
function toggleSubmenu(element) {
    event.preventDefault();
    
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.submenu-arrow');
    
    // Close other open submenus
    document.querySelectorAll('.submenu.open').forEach(menu => {
        if (menu !== submenu) {
            menu.classList.remove('open');
            menu.previousElementSibling.classList.remove('active');
            menu.previousElementSibling.querySelector('.submenu-arrow').style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle current submenu
    if (submenu && submenu.classList.contains('submenu')) {
        if (submenu.classList.contains('open')) {
            submenu.classList.remove('open');
            arrow.style.transform = 'rotate(0deg)';
        } else {
            submenu.classList.add('open');
            arrow.style.transform = 'rotate(90deg)';
        }
    }
}

// Quick actions
function quickAddProduct() {
    window.location.href = 'products.php?action=add';
}

function quickAddClient() {
    window.location.href = 'clients.php?action=add';
}

function quickViewAppointments() {
    window.location.href = 'appointments.php';
}

function quickBackup() {
    if (confirm('Voulez-vous effectuer une sauvegarde maintenant ?')) {
        // Implement backup functionality
        alert('Sauvegarde en cours...');
    }
}

// Load sidebar state
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
    }
    
    // Highlight current page
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .submenu-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.split('?')[0]) && href !== '#') {
            link.classList.add('active');
            
            // Open parent submenu if this is a submenu link
            if (link.classList.contains('submenu-link')) {
                const parentSubmenu = link.closest('.submenu');
                const parentNavLink = parentSubmenu.previousElementSibling;
                
                parentSubmenu.classList.add('open');
                parentNavLink.classList.add('active');
                const arrow = parentNavLink.querySelector('.submenu-arrow');
                if (arrow) arrow.style.transform = 'rotate(90deg)';
            }
        }
    });
});

// Mobile menu toggle
function toggleMobileMenu() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('mobile-open');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(e.target) && 
        !mobileToggle?.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
    }
});
</script>