<?php
// admin/appointments.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'appointments';
$page_title = 'Gestion des Rendez-vous';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'update_status':
            $client_id = $_POST['client_id'];
            $admin_id = $_POST['admin_id'];
            $product_id = $_POST['product_id'];
            $date_rdv = $_POST['date_rdv'];
            $new_status = $_POST['new_status'];
            
            try {
                $sql = "UPDATE prendre SET STATUS_RENDEZ_VOUS = ? 
                        WHERE client_id = ? AND administrateur_id = ? AND produit_id = ? AND DATE_RENDEZ_VOUS = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_status, $client_id, $admin_id, $product_id, $date_rdv]);
                
                $status_text = [0 => 'En attente', 1 => 'Confirmé', 2 => 'Annulé'][$new_status];
                $success_message = "Statut du rendez-vous mis à jour : $status_text";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
            $action = 'list';
            break;
            
        case 'bulk_update':
            $updates = $_POST['status_updates'] ?? [];
            $updated_count = 0;
            
            try {
                foreach ($updates as $rdv_key => $new_status) {
                    if (is_numeric($new_status) && in_array($new_status, [0, 1, 2])) {
                        // Décoder la clé du RDV (format: client_id-admin_id-product_id-date)
                        $parts = explode('-', $rdv_key);
                        if (count($parts) >= 4) {
                            $client_id = $parts[0];
                            $admin_id = $parts[1];
                            $product_id = $parts[2];
                            $date_rdv = implode('-', array_slice($parts, 3)); // Reconstituer la date
                            
                            $sql = "UPDATE prendre SET STATUS_RENDEZ_VOUS = ? 
                                    WHERE client_id = ? AND administrateur_id = ? AND produit_id = ? AND DATE_RENDEZ_VOUS = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$new_status, $client_id, $admin_id, $product_id, $date_rdv]);
                            $updated_count++;
                        }
                    }
                }
                $success_message = "$updated_count rendez-vous mis à jour.";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la mise à jour en lot: " . $e->getMessage();
            }
            $action = 'list';
            break;
    }
}

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.nom_complet LIKE ? OR p.nom_produit LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    $where_conditions[] = "pr.STATUS_RENDEZ_VOUS = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(pr.DATE_RENDEZ_VOUS) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total 
                  FROM prendre pr 
                  JOIN clients c ON pr.client_id = c.client_id 
                  JOIN produits p ON pr.produit_id = p.produit_id 
                  JOIN administrateurs a ON pr.administrateur_id = a.administrateur_id 
                  $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_appointments = $stmt->fetch()['total'];
    
    // Récupérer les rendez-vous
    $sql = "SELECT pr.*, c.nom_complet as client_nom, c.email as client_email, c.numero_telephone,
                   p.nom_produit, p.prix, p.url_image,
                   a.nom_complet as admin_nom
            FROM prendre pr 
            JOIN clients c ON pr.client_id = c.client_id 
            JOIN produits p ON pr.produit_id = p.produit_id 
            JOIN administrateurs a ON pr.administrateur_id = a.administrateur_id 
            $where_clause 
            ORDER BY pr.DATE_RENDEZ_VOUS DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
    $total_pages = ceil($total_appointments / $per_page);
    
    // Statistiques
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN STATUS_RENDEZ_VOUS = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN STATUS_RENDEZ_VOUS = 1 THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN STATUS_RENDEZ_VOUS = 2 THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN STATUS_RENDEZ_VOUS = 1 THEN p.prix ELSE 0 END) as total_revenue
                  FROM prendre pr
                  JOIN produits p ON pr.produit_id = p.produit_id";
    $stmt = $pdo->query($stats_sql);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des rendez-vous: " . $e->getMessage();
    $appointments = [];
    $total_appointments = 0;
    $total_pages = 1;
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'total_revenue' => 0];
}
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-success { background: linear-gradient(135deg, #4ecdc4, #44a08d); color: white; }
        .btn-warning { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #333; }
        .btn-danger { background: linear-gradient(135deg, #fd79a8, #e84393); color: white; }
        .btn-info { background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 20px;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            min-width: 150px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .stat-item.pending { background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.3); }
        .stat-item.confirmed { background: rgba(40, 167, 69, 0.1); border-color: rgba(40, 167, 69, 0.3); }
        .stat-item.cancelled { background: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); }
        .stat-item.revenue { background: rgba(116, 185, 255, 0.1); border-color: rgba(116, 185, 255, 0.3); }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-item.pending .stat-number { color: #ffc107; }
        .stat-item.confirmed .stat-number { color: #28a745; }
        .stat-item.cancelled .stat-number { color: #dc3545; }
        .stat-item.revenue .stat-number { color: #74b9ff; }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .search-box, .form-control {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box:focus, .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-box {
            min-width: 250px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .data-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .appointment-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .appointment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .appointment-info h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 14px;
        }

        .appointment-info p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .status-select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
            min-width: 100px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination .current {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .bulk-actions {
            background: rgba(255, 206, 84, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .bulk-actions.show {
            display: block;
        }

        .date-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 11px;
            color: #495057;
            font-weight: 600;
        }

        .priority-high {
            border-left: 4px solid #dc3545;
        }

        .priority-medium {
            border-left: 4px solid #ffc107;
        }

        .priority-low {
            border-left: 4px solid #28a745;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-section {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
                width: 100%;
            }

            .stats-row {
                flex-direction: column;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>

<!-- Include Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-calendar-alt"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <button class="btn btn-info" onclick="toggleBulkMode()">
                <i class="fas fa-edit"></i> Mode édition
            </button>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Liste des rendez-vous -->
    <div class="content-card">
        <!-- Statistiques -->
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total RDV</div>
            </div>
            <div class="stat-item pending">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-item confirmed">
                <div class="stat-number"><?php echo $stats['confirmed']; ?></div>
                <div class="stat-label">Confirmés</div>
            </div>
            <div class="stat-item cancelled">
                <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Annulés</div>
            </div>
            <div class="stat-item revenue">
                <div class="stat-number"><?php echo number_format($stats['total_revenue'], 0); ?> DH</div>
                <div class="stat-label">Chiffre d'Affaires</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="filters-section">
            <input type="text" name="search" class="search-box" placeholder="Rechercher client ou produit..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status" class="form-control">
                <option value="">Tous les statuts</option>
                <option value="0" <?php echo ($status_filter === '0') ? 'selected' : ''; ?>>En attente</option>
                <option value="1" <?php echo ($status_filter === '1') ? 'selected' : ''; ?>>Confirmé</option>
                <option value="2" <?php echo ($status_filter === '2') ? 'selected' : ''; ?>>Annulé</option>
            </select>
            
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrer
            </button>
            
            <a href="?" class="btn btn-warning">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <!-- Actions en lot -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" id="bulkUpdateForm">
                <input type="hidden" name="action" value="bulk_update">
                <h4><i class="fas fa-edit"></i> Mise à jour en lot</h4>
                <p>Modifiez les statuts dans le tableau ci-dessous et cliquez sur "Mettre à jour".</p>
                <div style="margin-top: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    <button type="button" class="btn btn-danger" onclick="toggleBulkMode()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>

        <!-- Tableau des rendez-vous -->
        <?php if (!empty($appointments)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rendez-vous</th>
                        <th>Client</th>
                        <th>Produit</th>
                        <th>Date & Heure</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <?php 
                        $rdv_key = $appointment['client_id'] . '-' . $appointment['administrateur_id'] . '-' . $appointment['produit_id'] . '-' . $appointment['DATE_RENDEZ_VOUS'];
                        
                        // Déterminer la priorité basée sur la date
                        $date_rdv = strtotime($appointment['DATE_RENDEZ_VOUS']);
                        $now = time();
                        $diff_days = ($date_rdv - $now) / (24 * 3600);
                        
                        $priority_class = '';
                        if ($diff_days < 1 && $diff_days > 0) {
                            $priority_class = 'priority-high'; // Dans les 24h
                        } elseif ($diff_days <= 3 && $diff_days > 0) {
                            $priority_class = 'priority-medium'; // Dans les 3 jours
                        } else {
                            $priority_class = 'priority-low';
                        }
                        ?>
                        <tr class="<?php echo $priority_class; ?>">
                            <td>
                                <div class="appointment-item">
                                    <img src="<?php echo htmlspecialchars($appointment['url_image'] ?: 'https://via.placeholder.com/50'); ?>" 
                                         alt="<?php echo htmlspecialchars($appointment['nom_produit']); ?>" 
                                         class="appointment-avatar">
                                    <div class="appointment-info">
                                        <h4>RDV #<?php echo $appointment['client_id'] . $appointment['produit_id']; ?></h4>
                                        <p><i class="fas fa-user-shield"></i> Admin: <?php echo htmlspecialchars($appointment['admin_nom']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($appointment['client_nom']); ?></strong>
                                <br>
                                <small>
                                    <i class="fas fa-envelope"></i> 
                                    <a href="mailto:<?php echo htmlspecialchars($appointment['client_email']); ?>">
                                        <?php echo htmlspecialchars($appointment['client_email']); ?>
                                    </a>
                                </small>
                                <?php if (!empty($appointment['numero_telephone'])): ?>
                                    <br>
                                    <small>
                                        <i class="fas fa-phone"></i> 
                                        <a href="tel:<?php echo htmlspecialchars($appointment['numero_telephone']); ?>">
                                            <?php echo htmlspecialchars($appointment['numero_telephone']); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($appointment['nom_produit']); ?></strong>
                            </td>
                            <td>
                                <div class="date-badge">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($appointment['DATE_RENDEZ_VOUS'])); ?>
                                </div>
                                <br>
                                <small>
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('H:i', strtotime($appointment['DATE_RENDEZ_VOUS'])); ?>
                                </small>
                                <?php if ($diff_days < 1 && $diff_days > 0): ?>
                                    <br><small style="color: #dc3545; font-weight: bold;">
                                        <i class="fas fa-exclamation-triangle"></i> Aujourd'hui !
                                    </small>
                                <?php elseif ($diff_days <= 3 && $diff_days > 0): ?>
                                    <br><small style="color: #ffc107; font-weight: bold;">
                                        <i class="fas fa-clock"></i> Dans <?php echo ceil($diff_days); ?> jour(s)
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($appointment['prix'], 2); ?> DH</strong>
                            </td>
                            <td>
                                <!-- Affichage normal du statut -->
                                <div class="normal-status-display">
                                    <span class="status-badge <?php 
                                        switch($appointment['STATUS_RENDEZ_VOUS']) {
                                            case 0: echo 'status-pending'; break;
                                            case 1: echo 'status-confirmed'; break;
                                            case 2: echo 'status-cancelled'; break;
                                            default: echo 'status-pending';
                                        }
                                    ?>">
                                        <?php 
                                        switch($appointment['STATUS_RENDEZ_VOUS']) {
                                            case 0: echo 'En attente'; break;
                                            case 1: echo 'Confirmé'; break;
                                            case 2: echo 'Annulé'; break;
                                            default: echo 'Inconnu';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- Mode édition en lot -->
                                <div class="bulk-status-edit" style="display: none;">
                                    <select name="status_updates[<?php echo $rdv_key; ?>]" class="status-select">
                                        <option value="0" <?php echo ($appointment['STATUS_RENDEZ_VOUS'] == 0) ? 'selected' : ''; ?>>En attente</option>
                                        <option value="1" <?php echo ($appointment['STATUS_RENDEZ_VOUS'] == 1) ? 'selected' : ''; ?>>Confirmé</option>
                                        <option value="2" <?php echo ($appointment['STATUS_RENDEZ_VOUS'] == 2) ? 'selected' : ''; ?>>Annulé</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <div class="normal-actions">
                                    <?php if ($appointment['STATUS_RENDEZ_VOUS'] == 0): ?>
                                        <button onclick="updateStatus('<?php echo $rdv_key; ?>', 1)" 
                                                class="btn btn-success btn-sm" title="Confirmer">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="updateStatus('<?php echo $rdv_key; ?>', 2)" 
                                                class="btn btn-danger btn-sm" title="Annuler">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($appointment['STATUS_RENDEZ_VOUS'] == 1): ?>
                                        <span class="btn btn-success btn-sm" style="cursor: default;">
                                            <i class="fas fa-check-circle"></i> Confirmé
                                        </span>
                                        <button onclick="updateStatus('<?php echo $rdv_key; ?>', 2)" 
                                                class="btn btn-danger btn-sm" title="Annuler">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="updateStatus('<?php echo $rdv_key; ?>', 0)" 
                                                class="btn btn-warning btn-sm" title="Remettre en attente">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Contact rapide -->
                                    <div style="margin-top: 5px;">
                                        <a href="mailto:<?php echo htmlspecialchars($appointment['client_email']); ?>" 
                                           class="btn btn-info btn-sm" title="Envoyer email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                        <?php if (!empty($appointment['numero_telephone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($appointment['numero_telephone']); ?>" 
                                               class="btn btn-info btn-sm" title="Appeler">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                <h3>Aucun rendez-vous trouvé</h3>
                <p>Aucun rendez-vous ne correspond à vos critères de recherche.</p>
                <div style="margin-top: 20px;">
                    <a href="?" class="btn btn-primary">
                        <i class="fas fa-refresh"></i> Voir tous les rendez-vous
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions Panel -->
    <div class="content-card">
        <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
        <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
            <button onclick="filterByStatus(0)" class="btn btn-warning">
                <i class="fas fa-clock"></i> Voir en attente (<?php echo $stats['pending']; ?>)
            </button>
            <button onclick="filterByStatus(1)" class="btn btn-success">
                <i class="fas fa-check"></i> Voir confirmés (<?php echo $stats['confirmed']; ?>)
            </button>
            <button onclick="filterByToday()" class="btn btn-info">
                <i class="fas fa-calendar-day"></i> RDV d'aujourd'hui
            </button>
            <button onclick="exportAppointments()" class="btn btn-primary">
                <i class="fas fa-download"></i> Exporter
            </button>
            <button onclick="showCalendarView()" class="btn btn-primary">
                <i class="fas fa-calendar"></i> Vue calendrier
            </button>
        </div>
    </div>
</main>

<!-- Modal pour vue calendrier -->
<div id="calendarModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div style="background: white; margin: 2% auto; padding: 30px; border-radius: 20px; width: 95%; max-width: 1200px; height: 90vh; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
            <h3 style="font-size: 24px; font-weight: 600; color: #333;">
                <i class="fas fa-calendar-alt"></i> Vue Calendrier des Rendez-vous
            </h3>
            <span onclick="closeModal('calendarModal')" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #999;">&times;</span>
        </div>
        <div id="calendarContent">
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-calendar" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                <h4>Vue calendrier</h4>
                <p>Fonctionnalité en développement - Affichage des rendez-vous par mois</p>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle bulk edit mode
function toggleBulkMode() {
    const bulkActions = document.getElementById('bulkActions');
    const bulkEdits = document.querySelectorAll('.bulk-status-edit');
    const normalDisplays = document.querySelectorAll('.normal-status-display');
    const normalActions = document.querySelectorAll('.normal-actions');
    
    const isVisible = bulkActions.classList.contains('show');
    
    if (isVisible) {
        bulkActions.classList.remove('show');
        bulkEdits.forEach(edit => edit.style.display = 'none');
        normalDisplays.forEach(display => display.style.display = 'block');
        normalActions.forEach(action => action.style.display = 'block');
    } else {
        bulkActions.classList.add('show');
        bulkEdits.forEach(edit => edit.style.display = 'block');
        normalDisplays.forEach(display => display.style.display = 'none');
        normalActions.forEach(action => action.style.display = 'none');
    }
}

// Update single appointment status
function updateStatus(rdvKey, newStatus) {
    if (!confirm('Êtes-vous sûr de vouloir modifier le statut de ce rendez-vous ?')) {
        return;
    }
    
    // Parse the rdv key to get individual components
    const parts = rdvKey.split('-');
    if (parts.length < 4) {
        alert('Erreur: Clé de rendez-vous invalide');
        return;
    }
    
    const clientId = parts[0];
    const adminId = parts[1];
    const productId = parts[2];
    const dateRdv = parts.slice(3).join('-'); // Reconstitute the date
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="client_id" value="${clientId}">
        <input type="hidden" name="admin_id" value="${adminId}">
        <input type="hidden" name="product_id" value="${productId}">
        <input type="hidden" name="date_rdv" value="${dateRdv}">
        <input type="hidden" name="new_status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Quick filter functions
function filterByStatus(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function filterByToday() {
    const today = new Date().toISOString().split('T')[0];
    const url = new URL(window.location.href);
    url.searchParams.set('date', today);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Export appointments
function exportAppointments() {
    if (confirm('Voulez-vous exporter la liste des rendez-vous en CSV ?')) {
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Client,Email,Produit,Date,Heure,Prix,Statut\n";
        
        // Add data from current page
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const client = cells[1].querySelector('strong').textContent.trim();
                const email = cells[1].querySelector('a[href^="mailto:"]').textContent.trim();
                const produit = cells[2].querySelector('strong').textContent.trim();
                const dateTime = cells[3].textContent.trim().replace(/\s+/g, ' ');
                const prix = cells[4].querySelector('strong').textContent.trim();
                const statut = cells[5].querySelector('.status-badge').textContent.trim();
                
                csvContent += `"${client}","${email}","${produit}","${dateTime}","${prix}","${statut}"\n`;
            }
        });
        
        // Download file
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "rendez-vous-" + new Date().toISOString().split('T')[0] + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Export terminé avec succès !', 'success');
    }
}

// Show calendar view
function showCalendarView() {
    document.getElementById('calendarModal').style.display = 'block';
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle"></i>
        ${message}
    `;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1001';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Bulk update form validation
document.getElementById('bulkUpdateForm')?.addEventListener('submit', function(e) {
    const selects = this.querySelectorAll('.status-select');
    let hasChanges = false;
    
    selects.forEach(select => {
        if (select.value !== select.dataset.original) {
            hasChanges = true;
        }
    });
    
    if (!hasChanges) {
        e.preventDefault();
        alert('Aucune modification détectée.');
        return;
    }
    
    if (!confirm('Êtes-vous sûr de vouloir mettre à jour le statut de ces rendez-vous ?')) {
        e.preventDefault();
    }
});

// Store original values for comparison
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-select').forEach(select => {
        select.dataset.original = select.value;
    });
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Auto-refresh appointments every 5 minutes
setInterval(function() {
    // Only refresh if we're still on the appointments page
    if (window.location.pathname.includes('appointments')) {
        const pendingCount = document.querySelector('.stat-item.pending .stat-number');
        if (pendingCount) {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPendingCount = doc.querySelector('.stat-item.pending .stat-number');
                    
                    if (newPendingCount && newPendingCount.textContent !== pendingCount.textContent) {
                        showNotification('Nouveaux rendez-vous détectés !', 'info');
                        // Optionally reload the page
                        // window.location.reload();
                    }
                })
                .catch(error => console.log('Auto-refresh error:', error));
        }
    }
}, 300000); // 5 minutes

console.log('OpticLook Appointments Page loaded successfully');
</script>

</body>
</html>