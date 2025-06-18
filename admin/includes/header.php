<?php
// admin/includes/header.php
if (!defined('ADMIN_ACCESS')) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>OpticLook Admin</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../Csstotal.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assest/images/favicon.ico">
    
    <!-- Meta -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Interface d'administration OpticLook">
</head>
<body class="admin-body">

<!-- Header Admin -->
<header class="admin-header">
    <div class="admin-header-content">
        <!-- Logo et titre -->
        <div class="admin-logo">
            <img src="../assest/images/logo.png" alt="OpticLook" class="logo-img">
            <div class="logo-text">
                <h1>OpticLook</h1>
                <span>Administration</span>
            </div>
        </div>

        <!-- Navigation du header -->
        <nav class="admin-header-nav">
            <ul class="header-nav-list">
                <li><a href="../index.php" target="_blank" class="header-nav-link">
                    <i class="fas fa-external-link-alt"></i> Voir le site
                </a></li>
                <li><a href="dashboard.php" class="header-nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
            </ul>
        </nav>

        <!-- Actions utilisateur -->
        <div class="admin-user-actions">
            <!-- Notifications -->
            <div class="notifications-dropdown">
                <button class="notification-btn" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationCount">3</span>
                </button>
                <div class="notifications-panel" id="notificationsPanel">
                    <div class="notifications-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read" onclick="markAllRead()">
                            <i class="fas fa-check-double"></i>
                        </button>
                    </div>
                    <div class="notifications-list">
                        <div class="notification-item unread">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <div class="notification-content">
                                <p>Stock faible: 3 produits</p>
                                <small>Il y a 2 minutes</small>
                            </div>
                        </div>
                        <div class="notification-item unread">
                            <i class="fas fa-calendar text-info"></i>
                            <div class="notification-content">
                                <p>Nouveau rendez-vous en attente</p>
                                <small>Il y a 15 minutes</small>
                            </div>
                        </div>
                        <div class="notification-item">
                            <i class="fas fa-user-plus text-success"></i>
                            <div class="notification-content">
                                <p>Nouveau client inscrit</p>
                                <small>Il y a 1 heure</small>
                            </div>
                        </div>
                    </div>
                    <div class="notifications-footer">
                        <a href="notifications.php">Voir toutes les notifications</a>
                    </div>
                </div>
            </div>

            <!-- Menu utilisateur -->
            <div class="user-menu-dropdown">
                <button class="user-menu-btn" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <small class="user-role">Administrateur</small>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-panel" id="userMenuPanel">
                    <div class="user-menu-header">
                        <div class="user-avatar-large">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h4>
                            <p><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@opticlook.com'); ?></p>
                        </div>
                    </div>
                    <div class="user-menu-list">
                        <a href="profil.php" class="user-menu-item">
                            <i class="fas fa-user"></i> Mon profil
                        </a>
                        <a href="parametres.php" class="user-menu-item">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <a href="aide.php" class="user-menu-item">
                            <i class="fas fa-question-circle"></i> Aide
                        </a>
                        <hr class="user-menu-divider">
                        <a href="../logout.php" class="user-menu-item logout">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu mobile toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<!-- Navigation principale -->
<?php include 'nav.php'; ?>

<!-- Container principal -->
<main class="admin-main">
    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message) && !empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="dashboard.php"><i class="fas fa-home"></i> Accueil</a>
            </li>
            <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                <?php foreach ($breadcrumb as $index => $item): ?>
                    <li class="breadcrumb-item <?php echo ($index === count($breadcrumb) - 1) ? 'active' : ''; ?>">
                        <?php if ($index === count($breadcrumb) - 1): ?>
                            <span><?php echo htmlspecialchars($item['title']); ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php elseif (isset($page_title)): ?>
                <li class="breadcrumb-item active">
                    <span><?php echo htmlspecialchars($page_title); ?></span>
                </li>
            <?php endif; ?>
        </ol>
    </nav>

<script>
// Toggle notifications
function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    const userPanel = document.getElementById('userMenuPanel');
    
    panel.classList.toggle('show');
    userPanel.classList.remove('show');
}

// Toggle user menu
function toggleUserMenu() {
    const panel = document.getElementById('userMenuPanel');
    const notificationPanel = document.getElementById('notificationsPanel');
    
    panel.classList.toggle('show');
    notificationPanel.classList.remove('show');
}

// Toggle mobile menu
function toggleMobileMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    sidebar.classList.toggle('mobile-open');
    
    if (!overlay) {
        const newOverlay = document.createElement('div');
        newOverlay.className = 'mobile-overlay';
        newOverlay.onclick = toggleMobileMenu;
        document.body.appendChild(newOverlay);
    } else {
        overlay.remove();
    }
}

// Mark all notifications as read
function markAllRead() {
    const notifications = document.querySelectorAll('.notification-item.unread');
    notifications.forEach(item => item.classList.remove('unread'));
    
    const badge = document.getElementById('notificationCount');
    badge.style.display = 'none';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notifications-dropdown')) {
        document.getElementById('notificationsPanel').classList.remove('show');
    }
    if (!e.target.closest('.user-menu-dropdown')) {
        document.getElementById('userMenuPanel').classList.remove('show');
    }
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});
</script>