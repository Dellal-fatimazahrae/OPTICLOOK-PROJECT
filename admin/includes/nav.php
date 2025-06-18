<?php
// admin/includes/nav.php
$current_page = $current_page ?? '';
?>

<aside class="admin-sidebar">
    <div class="sidebar-content">
        <!-- Menu principal -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>

                <!-- Gestion Produits -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'produit') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-box"></i>
                        <span>Produits</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'produit') !== false) ? 'open' : ''; ?>">
                        <li><a href="produits/" class="submenu-link">
                            <i class="fas fa-list"></i> Liste des produits
                        </a></li>
                        <li><a href="produits/ajouter.php" class="submenu-link">
                            <i class="fas fa-plus"></i> Ajouter produit
                        </a></li>
                        <li><a href="produits/categories.php" class="submenu-link">
                            <i class="fas fa-tags"></i> Catégories
                        </a></li>
                        <li><a href="produits/stock.php" class="submenu-link">
                            <i class="fas fa-warehouse"></i> Gestion stock
                        </a></li>
                    </ul>
                </li>

                <!-- Gestion Clients -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'client') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-users"></i>
                        <span>Clients</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'client') !== false) ? 'open' : ''; ?>">
                        <li><a href="clients/" class="submenu-link">
                            <i class="fas fa-list"></i> Liste des clients
                        </a></li>
                        <li><a href="clients/ajouter.php" class="submenu-link">
                            <i class="fas fa-user-plus"></i> Ajouter client
                        </a></li>
                        <li><a href="clients/groupes.php" class="submenu-link">
                            <i class="fas fa-layer-group"></i> Groupes
                        </a></li>
                    </ul>
                </li>

                <!-- Gestion Rendez-vous -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'rendezvous') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Rendez-vous</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'rendezvous') !== false) ? 'open' : ''; ?>">
                        <li><a href="rendezvous/" class="submenu-link">
                            <i class="fas fa-calendar"></i> Planning
                        </a></li>
                        <li><a href="rendezvous/en-attente.php" class="submenu-link">
                            <i class="fas fa-clock"></i> En attente
                            <span class="badge badge-warning" id="rdvEnAttenteBadge">
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM prendre WHERE STATUS_RENDEZ_VOUS = 0");
                                    $count = $stmt->fetch()['count'];
                                    echo $count > 0 ? $count : '';
                                } catch (Exception $e) {
                                    echo '';
                                }
                                ?>
                            </span>
                        </a></li>
                        <li><a href="rendezvous/confirmes.php" class="submenu-link">
                            <i class="fas fa-check-circle"></i> Confirmés
                        </a></li>
                        <li><a href="rendezvous/historique.php" class="submenu-link">
                            <i class="fas fa-history"></i> Historique
                        </a></li>
                    </ul>
                </li>

                <!-- Statistiques et Rapports -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'rapport') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-chart-bar"></i>
                        <span>Rapports</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'rapport') !== false) ? 'open' : ''; ?>">
                        <li><a href="rapports/" class="submenu-link">
                            <i class="fas fa-chart-pie"></i> Vue d'ensemble
                        </a></li>
                        <li><a href="rapports/ventes.php" class="submenu-link">
                            <i class="fas fa-euro-sign"></i> Ventes
                        </a></li>
                        <li><a href="rapports/clients.php" class="submenu-link">
                            <i class="fas fa-users"></i> Clients
                        </a></li>
                        <li><a href="rapports/stock.php" class="submenu-link">
                            <i class="fas fa-boxes"></i> Stock
                        </a></li>
                    </ul>
                </li>

                <!-- Paramètres -->
                <li class="nav-item">
                    <a href="#" class="nav-link has-submenu <?php echo (strpos($current_page, 'parametre') !== false) ? 'active' : ''; ?>" 
                       onclick="toggleSubmenu(this)">
                        <i class="fas fa-cog"></i>
                        <span>Paramètres</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo (strpos($current_page, 'parametre') !== false) ? 'open' : ''; ?>">
                        <li><a href="parametres/" class="submenu-link">
                            <i class="fas fa-sliders-h"></i> Général
                        </a></li>
                        <li><a href="parametres/utilisateurs.php" class="submenu-link">
                            <i class="fas fa-user-cog"></i> Utilisateurs
                        </a></li>
                        <li><a href="parametres/sauvegarde.php" class="submenu-link">
                            <i class="fas fa-database"></i> Sauvegarde
                        </a></li>
                        <li><a href="parametres/logs.php" class="submenu-link">
                            <i class="fas fa-file-alt"></i> Journaux
                        </a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Actions rapides -->
        <div class="sidebar-quick-actions">
            <h4><i class="fas fa-bolt"></i> Actions rapides</h4>
            <div class="quick-actions-list">
                <button class="quick-action-btn" onclick="openQuickAddProduct()">
                    <i class="fas fa-plus"></i> Produit
                </button>
                <button class="quick-action-btn" onclick="openQuickAddClient()">
                    <i class="fas fa-user-plus"></i> Client
                </button>
                <button class="quick-action-btn" onclick="openQuickReport()">
                    <i class="fas fa-chart-line"></i> Rapport
                </button>
                <button class="quick-action-btn" onclick="openQuickBackup()">
                    <i class="fas fa-shield-alt"></i> Backup
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
                <span class="info-label">Dernière sauvegarde:</span>
                <span class="info-value"><?php echo date('d/m H:i'); ?></span>
            </div>
        </div>
    </div>
</aside>

<script>
// Toggle submenu
function toggleSubmenu(element) {
    event.preventDefault();
    
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.submenu-arrow');
    const navItem = element.closest('.nav-item');
    
    // Close other open submenus
    document.querySelectorAll('.submenu.open').forEach(menu => {
        if (menu !== submenu) {
            menu.classList.remove('open');
            menu.previousElementSibling.classList.remove('active');
            menu.previousElementSibling.querySelector('.submenu-arrow').style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle current submenu
    if (submenu.classList.contains('open')) {
        submenu.classList.remove('open');
        element.classList.remove('active');
        arrow.style.transform = 'rotate(0deg)';
    } else {
        submenu.classList.add('open');
        element.classList.add('active');
        arrow.style.transform = 'rotate(90deg)';
    }
}

// Quick actions
function openQuickAddProduct() {
    window.location.href = 'produits/ajouter.php';
}

function openQuickAddClient() {
    window.location.href = 'clients/ajouter.php';
}

function openQuickReport() {
    window.open('rapports/generer.php?quick=1', '_blank');
}

function openQuickBackup() {
    if (confirm('Voulez-vous effectuer une sauvegarde maintenant ?')) {
        fetch('api/backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sauvegarde effectuée avec succès');
                updateSystemInfo();
            } else {
                alert('Erreur lors de la sauvegarde: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la sauvegarde');
        });
    }
}

// Update system info
function updateSystemInfo() {
    const lastBackupElement = document.querySelector('.sidebar-system-info .info-value:last-child');
    if (lastBackupElement) {
        lastBackupElement.textContent = new Date().toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Auto-close submenus on mobile
if (window.innerWidth <= 768) {
    document.querySelectorAll('.submenu-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMobileMenu();
            }
        });
    });
}

// Highlight current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .submenu-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href) && href !== '#') {
            link.classList.add('current');
            
            // Open parent submenu if this is a submenu link
            if (link.classList.contains('submenu-link')) {
                const parentSubmenu = link.closest('.submenu');
                const parentNavLink = parentSubmenu.previousElementSibling;
                
                parentSubmenu.classList.add('open');
                parentNavLink.classList.add('active');
                parentNavLink.querySelector('.submenu-arrow').style.transform = 'rotate(90deg)';
            }
        }
    });
});

// Update RDV badge periodically
setInterval(function() {
    fetch('api/get_pending_rdv_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('rdvEnAttenteBadge');
            if (badge) {
                badge.textContent = data.count > 0 ? data.count : '';
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        })
        .catch(error => console.error('Erreur mise à jour badge RDV:', error));
}, 60000); // Mise à jour toutes les minutes
</script>