<?php
// admin/dashboard.php - Complete fixed version
session_start();
include "../conixion.php";
// include "../admin/includes/sidebar.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

// Récupérer les statistiques du tableau de bord
try {
    // Total clients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
    $total_clients = $stmt->fetch()['total'];
    
    // Total produits
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
    $total_produits = $stmt->fetch()['total'];
    
    // Rendez-vous en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prendre WHERE STATUS_RENDEZ_VOUS = 0");
    $rdv_attente = $stmt->fetch()['total'];
    
    // Produits en stock faible (moins de 10)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE quantite_stock < 10");
    $rupture_stock = $stmt->fetch()['total'];
    
    // Valeur totale du stock
    $stmt = $pdo->query("SELECT SUM(quantite_stock * prix) as valeur_stock FROM produits");
    $valeur_stock = $stmt->fetch()['valeur_stock'] ?? 0;
    
    // Rendez-vous confirmés
    $stmt = $pdo->query("SELECT COUNT(*) as rdv_confirmes FROM prendre WHERE STATUS_RENDEZ_VOUS = 1");
    $rdv_confirmes = $stmt->fetch()['rdv_confirmes'];
    
    // Derniers produits
    $stmt = $pdo->query("SELECT * FROM produits ORDER BY produit_id DESC LIMIT 5");
    $derniers_produits = $stmt->fetchAll();
    
    // Derniers clients
    $stmt = $pdo->query("SELECT nom_complet, email FROM clients ORDER BY client_id DESC LIMIT 5");
    $derniers_clients = $stmt->fetchAll();
    
    // Prochains rendez-vous
    $stmt = $pdo->query("
        SELECT p.DATE_RENDEZ_VOUS, c.nom_complet, pr.nom_produit 
        FROM prendre p 
        JOIN clients c ON p.client_id = c.client_id 
        JOIN produits pr ON p.produit_id = pr.produit_id 
        WHERE p.STATUS_RENDEZ_VOUS = 1 AND p.DATE_RENDEZ_VOUS > NOW() 
        ORDER BY p.DATE_RENDEZ_VOUS ASC 
        LIMIT 5
    ");
    $prochains_rdv = $stmt->fetchAll();
    
    // Alertes stock
    $stmt = $pdo->query("SELECT produit_id, nom_produit, quantite_stock FROM produits WHERE quantite_stock < 5 ORDER BY quantite_stock ASC");
    $alertes_stock = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
    $total_clients = $total_produits = $rdv_attente = $rupture_stock = $valeur_stock = $rdv_confirmes = 0;
    $derniers_produits = $derniers_clients = $prochains_rdv = $alertes_stock = [];
}

// Gestion des requêtes AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'update_stock':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $product_id = $input['product_id'];
                $new_stock = $input['new_stock'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE produit_id = ?");
                    $stmt->execute([$new_stock, $product_id]);
                    echo json_encode(['success' => true, 'message' => 'Stock mis à jour avec succès']);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
                }
            }
            exit;
            
        case 'get_stats':
            try {
                $stats = [
                    'total_clients' => $total_clients,
                    'total_produits' => $total_produits,
                    'rdv_attente' => $rdv_attente,
                    'rupture_stock' => $rupture_stock,
                    'valeur_stock' => $valeur_stock,
                    'rdv_confirmes' => $rdv_confirmes
                ];
                echo json_encode($stats);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}

$page_title = "Tableau de bord";
$current_page = "dashboard";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OpticLook Admin</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../Csstotal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            min-height: 100vh;
        }

        /* Admin Header */
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .logo-text h1 {
        color: #38b342;
        font-size: 24px;
        font-weight: 700;
       }

        .logo-text span {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .user-role {
            font-size: 12px;
            color: #666;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger {
    background: linear-gradient(135deg, #3cc747, #347d3a);
    color: white;
}
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(85, 236, 105, 0.4);
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;

            
    flex: 1;
    margin-left: 280px;
    padding: 20px;
    transition: margin-left 0.3s ease;

        }

        /* Stats Grid */
        .stats-section h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(180, 252, 180, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76));
        }

        .stat-card.primary::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.success::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.warning::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.danger::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.info::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.secondary::before { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }

        .stat-card-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-card.primary .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.success .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); color: #333; }
        .stat-card.danger .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.info .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }
        .stat-card.secondary .stat-icon { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); }

        .stat-info h3 {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-trend.positive { color:rgb(12, 119, 98); }
        .stat-trend.negative { color:rgb(255, 51, 0); }
        .stat-trend.neutral { color:rgb(253, 243, 110); }

        /* Quick Actions */
        .quick-actions-section {
            margin-bottom: 40px;
        }

        .quick-actions-section h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .quick-action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .quick-action-card i {
            font-size: 32px;
            margin-bottom: 15px;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            -webkit-background-clip: text ;
            -webkit-text-fill-color: transparent;
        }

        .quick-action-card h4 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .quick-action-card p {
            font-size: 12px;
            color: #666;
        }

        /* Widgets */
        .widgets-section h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .widgets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .widget {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .widget-header {
            padding: 20px 25px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-header h3 {
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-link {
            color:rgb(73, 180, 109);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .widget-content {
            padding: 20px 25px;
            max-height: 400px;
            overflow-y: auto;
        }

        .widget-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            gap: 15px;
        }

        .widget-item:last-child {
            border-bottom: none;
        }

        .widget-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .client-avatar, .rdv-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .widget-info {
            flex: 1;
        }

        .widget-info h4 {
            font-size: 14px;
            margin-bottom: 4px;
            color: #333;
        }

        .widget-info p {
            font-size: 13px;
            color: #666;
            margin-bottom: 2px;
        }

        .widget-info small {
            font-size: 11px;
            color: #999;
        }

        .widget-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background:rgb(42, 212, 107);
            color: white;
        }

        .btn-warning {
            background:rgb(129, 230, 89);
            color:rgb(42, 192, 80);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            color: #999;
            padding: 30px;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .alert-icon {
            color:rgb(64, 230, 49);
            font-size: 18px;
        }

        .widget-item.alert .widget-info h4 {
            color: #e17055;
        }

        /* Charts Section */
        .charts-section h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 10px;
            color: #666;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 1001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
        }

        .notification.error {
            background: linear-gradient(135deg, #fd79a8, #e84393);
        }

        .notification.info {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .widgets-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .admin-header-content {
                flex-direction: column;
                gap: 15px;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>


<!-- Include Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<!-- Header Admin -->
<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-logo">
            <div class="logo-text">
                <h1> OpticLook</h1>
                <span>Administration</span>
            </div>
        </div>

        <div class="admin-user-actions">
            <div class="user-info">
                <div class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="user-role">Administrateur</div>
            </div>
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>
</header>

<div class="dashboard-container">
    <?php if (isset($error_message)): ?>
        <div class="notification error show">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques principales -->
    <div class="stats-section">
        <h2><i class="fas fa-chart-pie"></i> Vue d'ensemble</h2>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_produits; ?></h3>
                        <p>Produits total</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> Catalogue complet
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_clients; ?></h3>
                        <p>Clients inscrits</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> Base clients
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $rdv_attente; ?></h3>
                        <p>RDV en attente</p>
                        <span class="stat-trend neutral">
                            <i class="fas fa-minus"></i> À traiter
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $rupture_stock; ?></h3>
                        <p>Stock faible</p>
                        <span class="stat-trend negative">
                            <i class="fas fa-arrow-down"></i> Action requise
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($valeur_stock, 0); ?> DH</h3>
                        <p>Valeur du stock</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> Inventaire
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card secondary">
                <div class="stat-card-content">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $rdv_confirmes; ?></h3>
                        <p>RDV confirmés</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> Ce mois
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions-section">
        <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
        <div class="quick-actions-grid">
            <a href="#" onclick="openProductModal()" class="quick-action-card">
                <i class="fas fa-plus"></i>
                <h4>Ajouter produit</h4>
                <p>Nouveau produit au catalogue</p>
            </a>
            <a href="#" onclick="showAppointments()" class="quick-action-card">
                <i class="fas fa-calendar-check"></i>
                <h4>Gérer RDV</h4>
                <p><?php echo $rdv_attente; ?> en attente</p>
            </a>
            <a href="#" onclick="showClients()" class="quick-action-card">
                <i class="fas fa-users"></i>
                <h4>Voir clients</h4>
                <p><?php echo $total_clients; ?> inscrits</p>
            </a>
            <!-- <a href="#" onclick="generateReport()" class="quick-action-card">
                <i class="fas fa-chart-bar"></i>
                <h4>Rapport</h4>
                <p>Générer rapport</p>
            </a> -->
        </div>
    </div>

    <!-- Widgets principaux -->
    <div class="widgets-section">
        <h2><i class="fas fa-th-large"></i> Aperçu détaillé</h2>
        <div class="widgets-grid">
            <!-- Widget Derniers produits -->
            <div class="widget">
                <div class="widget-header">
                    <h3><i class="fas fa-box-open"></i> Derniers produits</h3>
                    <a href="#" onclick="showProducts()" class="widget-link">Voir tout</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($derniers_produits)): ?>
                        <?php foreach ($derniers_produits as $produit): ?>
                            <div class="widget-item">
                                <img src="<?php echo htmlspecialchars($produit['url_image'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRkZGIiBzdHJva2U9IiNEREQiLz4KPHN2ZyB4PSIxNSIgeT0iMTUiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM5OTkiPgo8cGF0aCBkPSJtMTUgMTggNi02LTYtNi02IDYgNiA2eiIvPgo8L3N2Zz4KPC9zdmc+'); ?>" 
                                     alt="<?php echo htmlspecialchars($produit['nom_produit']); ?>" 
                                     class="widget-img">
                                <div class="widget-info">
                                    <h4><?php echo htmlspecialchars($produit['nom_produit']); ?></h4>
                                    <p><strong><?php echo htmlspecialchars($produit['prix']); ?> DH</strong></p>
                                    <small><i class="fas fa-cubes"></i> Stock: <?php echo $produit['quantite_stock']; ?></small>
                                </div>
                                <div class="widget-actions">
                                    <button class="btn-icon" onclick="editProduct(<?php echo $produit['produit_id']; ?>)" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box"></i>
                            <p>Aucun produit trouvé</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget Derniers clients -->
            <div class="widget">
                <div class="widget-header">
                    <h3><i class="fas fa-user-plus"></i> Derniers clients</h3>
                    <a href="#" onclick="showClients()" class="widget-link">Voir tout</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($derniers_clients)): ?>
                        <?php foreach ($derniers_clients as $client): ?>
                            <div class="widget-item">
                                <div class="client-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="widget-info">
                                    <h4><?php echo htmlspecialchars($client['nom_complet']); ?></h4>
                                    <p><?php echo htmlspecialchars($client['email']); ?></p>
                                    <small><i class="fas fa-clock"></i> Nouveau client</small>
                                </div>
                                <div class="widget-actions">
                                    <button class="btn-icon" title="Contacter">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>Aucun client trouvé</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget Prochains RDV -->
            <div class="widget">
                <div class="widget-header">
                    <h3><i class="fas fa-calendar-check"></i> Prochains rendez-vous</h3>
                    <a href="#" onclick="showAppointments()" class="widget-link">Voir tout</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($prochains_rdv)): ?>
                        <?php foreach ($prochains_rdv as $rdv): ?>
                            <div class="widget-item">
                                <div class="rdv-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="widget-info">
                                    <h4><?php echo htmlspecialchars($rdv['nom_complet']); ?></h4>
                                    <p><?php echo htmlspecialchars($rdv['nom_produit']); ?></p>
                                    <small><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($rdv['DATE_RENDEZ_VOUS'])); ?></small>
                                </div>
                                <div class="widget-actions">
                                    <span class="status-badge confirmed">Confirmé</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucun rendez-vous programmé</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget Alertes Stock -->
            <div class="widget">
                <div class="widget-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Alertes Stock</h3>
                    <a href="#" onclick="showStockManagement()" class="widget-link">Gérer stock</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($alertes_stock)): ?>
                        <?php foreach ($alertes_stock as $alerte): ?>
                            <div class="widget-item alert">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="widget-info">
                                    <h4><?php echo htmlspecialchars($alerte['nom_produit']); ?></h4>
                                    <p><strong>Stock: <?php echo $alerte['quantite_stock']; ?></strong></p>
                                    <small><i class="fas fa-arrow-down"></i> Stock critique</small>
                                </div>
                                <div class="widget-actions">
                                    <button class="btn-icon btn-warning" title="Réapprovisionner" onclick="updateStock(<?php echo $alerte['produit_id']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle" style="color: #00b894;"></i>
                            <p>Aucune alerte de stock</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et rapports -->
    <!-- <div class="charts-section">
        <h2><i class="fas fa-chart-bar"></i> Rapports visuels</h2>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Répartition des produits</h3>
                    <button class="btn-icon" onclick="exportChart('category')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="chart-container" id="categoryChart">
                    Graphique des catégories de produits
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Évolution des RDV</h3>
                    <button class="btn-icon" onclick="exportChart('appointments')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="chart-container" id="appointmentsChart">
                    Graphique de l'évolution des rendez-vous
                </div>
            </div>
        </div>
    </div> -->
</div>

<!-- Product Modal -->
<div id="productModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
            <h3 style="font-size: 24px; font-weight: 600; color: #333;">Ajouter un Produit</h3>
            <span onclick="closeModal('productModal')" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #999;">&times;</span>
        </div>
        <form id="productForm">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Nom du produit *</label>
                    <input type="text" id="productName" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Catégorie *</label>
                    <select id="productCategory" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                        <option value="">Sélectionner une catégorie</option>
                        <option value="1">Lunettes Médicales</option>
                        <option value="2">Lunettes de Soleil</option>
                        <option value="3">Accessoires</option>
                    </select>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Prix (DH) *</label>
                    <input type="number" id="productPrice" step="0.01" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Stock *</label>
                    <input type="number" id="productStock" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Genre</label>
                    <select id="productGender" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                        <option value="Unisexe">Unisexe</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">URL Image</label>
                    <input type="url" id="productImage" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;">
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Description</label>
                <textarea id="productDescription" rows="3" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px;"></textarea>
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal('productModal')" style="padding: 10px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #fd79a8, #e84393); color: white; cursor: pointer;">Annuler</button>
                <button type="submit" style="padding: 10px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; cursor: pointer;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Notification -->
<div id="notification" class="notification"></div>

<script>
// Auto-refresh des données toutes les 5 minutes
setInterval(function() {
    updateStats();
}, 300000);

// Mettre à jour les statistiques
function updateStats() {
    fetch('?action=get_stats')
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les statistiques si nécessaire
            console.log('Stats updated:', data);
        })
        .catch(error => {
            console.error('Erreur mise à jour stats:', error);
        });
}

// Générer un rapport
function generateReport() {
    if (confirm('Voulez-vous générer un rapport complet ?')) {
        showNotification('Génération du rapport en cours...', 'info');
        // Ici vous pouvez implémenter la génération de rapport
        setTimeout(() => {
            showNotification('Rapport généré avec succès !', 'success');
        }, 2000);
    }
}

// Mettre à jour le stock
function updateStock(productId) {
    const newStock = prompt('Entrez la nouvelle quantité en stock:');
    if (newStock !== null && !isNaN(newStock) && parseInt(newStock) >= 0) {
        fetch('?action=update_stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                new_stock: parseInt(newStock)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour du stock', 'error');
        });
    }
}

// Exporter les graphiques
function exportChart(type) {
    showNotification('Export ' + type + ' en cours...', 'info');
    // Implémenter l'export selon vos besoins
}

// Ouvrir le modal produit
function openProductModal() {
    document.getElementById('productModal').style.display = 'block';
}

// Fermer les modals
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Modifier un produit
function editProduct(productId) {
    showNotification('Redirection vers l\'édition du produit...', 'info');
    // Implémenter la redirection ou l'ouverture du modal d'édition
}

// Navigation rapide
function showProducts() {
    showNotification('Redirection vers la liste des produits...', 'info');
    // Implémenter la navigation
}

function showClients() {
    showNotification('Redirection vers la liste des clients...', 'info');
    // Implémenter la navigation
}

function showAppointments() {
    showNotification('Redirection vers la gestion des RDV...', 'info');
    // Implémenter la navigation
}

function showStockManagement() {
    showNotification('Redirection vers la gestion du stock...', 'info');
    // Implémenter la navigation
}

// Gestion du formulaire produit
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        nom_produit: document.getElementById('productName').value,
        categorie_id: document.getElementById('productCategory').value,
        prix: document.getElementById('productPrice').value,
        quantite_stock: document.getElementById('productStock').value,
        genre: document.getElementById('productGender').value,
        url_image: document.getElementById('productImage').value,
        description_produit: document.getElementById('productDescription').value
    };
    
    // Ici vous pouvez implémenter l'envoi des données
    showNotification('Produit ajouté avec succès !', 'success');
    closeModal('productModal');
    this.reset();
});

// Système de notifications
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 4000);
}

// Fermer les modals en cliquant à l'extérieur
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Auto-hide des alertes après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.notification.show');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
        }, 5000);
    });
});

// Initialisation des graphiques simulés
document.addEventListener('DOMContentLoaded', function() {
    // Simuler des graphiques simples
    const categoryChart = document.getElementById('categoryChart');
    const appointmentsChart = document.getElementById('appointmentsChart');
    
    // Ajouter des données simulées
    setTimeout(() => {
        if (categoryChart) {
            categoryChart.innerHTML = `
                <div style="text-align: center;">
                    <h4>Répartition par catégorie</h4>
                    <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                        <div style="text-align: center;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #4ecdc4, #44a08d); border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">45%</div>
                            <small>Médicales</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #ffeaa7, #fdcb6e); border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: #333; font-weight: bold;">35%</div>
                            <small>Soleil</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #74b9ff, #0984e3); border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">20%</div>
                            <small>Accessoires</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        if (appointmentsChart) {
            appointmentsChart.innerHTML = `
                <div style="text-align: center;">
                    <h4>Tendance des RDV</h4>
                    <div style="margin-top: 20px;">
                        <div style="display: flex; align-items: end; height: 120px; justify-content: space-around; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                            <div style="width: 30px; height: 60px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 4px;" title="Lun: 12 RDV"></div>
                            <div style="width: 30px; height: 80px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 4px;" title="Mar: 16 RDV"></div>
                            <div style="width: 30px; height: 45px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 4px;" title="Mer: 9 RDV"></div>
                            <div style="width: 30px; height: 90px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 4px;" title="Jeu: 18 RDV"></div>
                            <div style="width: 30px; height: 70px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 4px;" title="Ven: 14 RDV"></div>
                        </div>
                        <small style="color: #666; margin-top: 10px; display: block;">Évolution positive cette semaine</small>
                    </div>
                </div>
            `;
        }
    }, 1000);
});

console.log('OpticLook Admin Dashboard loaded successfully');
</script>

</body>
</html>