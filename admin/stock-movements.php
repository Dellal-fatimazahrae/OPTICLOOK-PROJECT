<?php
// admin/stock-movements.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'stock_movements';
$page_title = 'Mouvements de Stock';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_movement':
            $produit_id = $_POST['produit_id'];
            $emplacement_id = $_POST['emplacement_id'];
            $quantite = (float)$_POST['quantite'];
            $statut_flux = (int)$_POST['statut_flux']; // 1 = Entrée, 0 = Sortie
            $raison = trim($_POST['raison'] ?? '');
            
            if ($quantite <= 0) {
                $error_message = "La quantité doit être supérieure à 0.";
            } else {
                try {
                    // Vérifier si un mouvement existe déjà pour ce produit/emplacement
                    $stmt = $pdo->prepare("SELECT * FROM mouvementsstock WHERE produit_id = ? AND emplacement_id = ?");
                    $stmt->execute([$produit_id, $emplacement_id]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Mettre à jour le mouvement existant
                        $nouvelle_quantite = $existing['quantite'];
                        if ($statut_flux == 1) { // Entrée
                            $nouvelle_quantite += $quantite;
                        } else { // Sortie
                            $nouvelle_quantite = max(0, $nouvelle_quantite - $quantite);
                        }
                        
                        $sql = "UPDATE mouvementsstock 
                                SET quantite = ?, date_mouvement = NOW(), statut_flux = ? 
                                WHERE produit_id = ? AND emplacement_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nouvelle_quantite, $statut_flux, $produit_id, $emplacement_id]);
                    } else {
                        // Créer un nouveau mouvement
                        $sql = "INSERT INTO mouvementsstock (produit_id, emplacement_id, date_mouvement, quantite, statut_flux) 
                                VALUES (?, ?, NOW(), ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$produit_id, $emplacement_id, $quantite, $statut_flux]);
                    }
                    
                    $type_mouvement = $statut_flux == 1 ? 'Entrée' : 'Sortie';
                    $success_message = "$type_mouvement de $quantite unités enregistrée avec succès. Raison: $raison";
                    
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de l'enregistrement: " . $e->getMessage();
                }
            }
            $action = 'list';
            break;
            
        case 'delete_movement':
            $produit_id = $_POST['produit_id'];
            $emplacement_id = $_POST['emplacement_id'];
            
            try {
                $stmt = $pdo->prepare("DELETE FROM mouvementsstock WHERE produit_id = ? AND emplacement_id = ?");
                $stmt->execute([$produit_id, $emplacement_id]);
                $success_message = "Mouvement supprimé avec succès.";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            $action = 'list';
            break;
    }
}

// Paramètres de filtrage
$location_filter = $_GET['location'] ?? '';
$product_filter = $_GET['product'] ?? '';
$movement_filter = $_GET['movement'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($location_filter)) {
    $where_conditions[] = "ms.emplacement_id = ?";
    $params[] = $location_filter;
}

if (!empty($product_filter)) {
    $where_conditions[] = "ms.produit_id = ?";
    $params[] = $product_filter;
}

if ($movement_filter === '1' || $movement_filter === '0') {
    $where_conditions[] = "ms.statut_flux = ?";
    $params[] = $movement_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(ms.date_mouvement) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.nom_produit LIKE ? OR e.numero_emplacement LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Récupérer les mouvements avec détails
    $sql = "SELECT ms.*, p.nom_produit, p.prix, p.url_image, e.numero_emplacement,
                   (ms.quantite * p.prix) as valeur_stock
            FROM mouvementsstock ms
            JOIN produits p ON ms.produit_id = p.produit_id
            JOIN emplacements e ON ms.emplacement_id = e.emplacement_id
            $where_clause
            ORDER BY ms.date_mouvement DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
    
    // Statistiques
    $stats_sql = "SELECT 
                COUNT(*) as total_movements,
                SUM(CASE WHEN ms.statut_flux = 1 THEN ms.quantite ELSE 0 END) as total_entrees,
                SUM(CASE WHEN ms.statut_flux = 0 THEN ms.quantite ELSE 0 END) as total_sorties,
                COUNT(DISTINCT ms.produit_id) as products_count,
                COUNT(DISTINCT ms.emplacement_id) as locations_count
              FROM mouvementsstock ms
              JOIN produits p ON ms.produit_id = p.produit_id
              JOIN emplacements e ON ms.emplacement_id = e.emplacement_id
              $where_clause";
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
    // Valeur totale
    $value_sql = "SELECT SUM(ms.quantite * p.prix) as total_value
                  FROM mouvementsstock ms
                  JOIN produits p ON ms.produit_id = p.produit_id
                  JOIN emplacements e ON ms.emplacement_id = e.emplacement_id
                  $where_clause";
    $stmt = $pdo->prepare($value_sql);
    $stmt->execute($params);
    $stats['total_value'] = $stmt->fetch()['total_value'] ?? 0;
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des mouvements: " . $e->getMessage();
    $movements = [];
    $stats = ['total_movements' => 0, 'total_entrees' => 0, 'total_sorties' => 0, 'products_count' => 0, 'locations_count' => 0, 'total_value' => 0];
}

// Récupérer les listes pour les filtres
try {
    $locations = $pdo->query("SELECT * FROM emplacements ORDER BY numero_emplacement")->fetchAll();
    $products = $pdo->query("SELECT produit_id, nom_produit FROM produits ORDER BY nom_produit")->fetchAll();
} catch (PDOException $e) {
    $locations = [];
    $products = [];
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
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
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

        .btn-primary { background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79)); color: white; }
        .btn-success { background: linear-gradient(135deg, #4ecdc4, #44a08d); color: white; }
        .btn-warning { background: linear-gradient(135deg,rgb(253, 237, 6),rgb(252, 255, 85)); color: #333; }
        .btn-danger { background: linear-gradient(135deg,rgb(255, 53, 53),rgb(255, 3, 3)); color: white; }
        .btn-info { background: linear-gradient(135deg,rgb(53, 154, 255),rgb(119, 196, 255)); color: white; }

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

        .stat-item.in { background: rgba(40, 167, 69, 0.1); border-color: rgba(40, 167, 69, 0.3); }
        .stat-item.out { background: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); }
        .stat-item.value { background: rgba(116, 185, 255, 0.1); border-color: rgba(116, 185, 255, 0.3); }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-item.in .stat-number { color: #28a745; }
        .stat-item.out .stat-number { color: #dc3545; }
        .stat-item.value .stat-number { color: #74b9ff; }

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

        .data-table tr.movement-in {
            border-left: 4px solid #28a745;
        }

        .data-table tr.movement-out {
            border-left: 4px solid #dc3545;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .movement-type {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .movement-in { background: #d4edda; color: #155724; }
        .movement-out { background: #f8d7da; color: #721c24; }

        .location-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        /* .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        } */
        .modal-content {
          background: rgba(255, 255, 255, 0.98);
          backdrop-filter: blur(20px);
         margin: 1% auto;
         padding: 30px;
         border-radius: 20px;
         width: 90%;
         max-width: 600px;
         height: 100%;
         box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
         overflow-y: auto;
}

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
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
            <i class="fas fa-exchange-alt"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <button class="btn btn-success" onclick="openMovementModal()">
                <i class="fas fa-plus"></i> Nouveau mouvement
            </button>
            <a href="locations.php" class="btn btn-info">
                <i class="fas fa-map-marker-alt"></i> Emplacements
            </a>
            <a href="stock.php" class="btn btn-primary">
                <i class="fas fa-warehouse"></i> Gestion stock
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

    <!-- Mouvements de stock -->
    <div class="content-card">
        <!-- Statistiques -->
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_movements']; ?></div>
                <div class="stat-label">Total Mouvements</div>
            </div>
            <div class="stat-item in">
                <div class="stat-number"><?php echo number_format($stats['total_entrees'], 0); ?></div>
                <div class="stat-label">Entrées</div>
            </div>
            <div class="stat-item out">
                <div class="stat-number"><?php echo number_format($stats['total_sorties'], 0); ?></div>
                <div class="stat-label">Sorties</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['products_count']; ?></div>
                <div class="stat-label">Produits</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['locations_count']; ?></div>
                <div class="stat-label">Emplacements</div>
            </div>
            <div class="stat-item value">
                <div class="stat-number"><?php echo number_format($stats['total_value'], 0); ?> DH</div>
                <div class="stat-label">Valeur Totale</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="filters-section">
            <input type="text" name="search" class="search-box" placeholder="Rechercher produit ou emplacement..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="location" class="form-control">
                <option value="">Tous les emplacements</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo $loc['emplacement_id']; ?>" 
                            <?php echo ($location_filter == $loc['emplacement_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loc['numero_emplacement']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="product" class="form-control">
                <option value="">Tous les produits</option>
                <?php foreach ($products as $prod): ?>
                    <option value="<?php echo $prod['produit_id']; ?>" 
                            <?php echo ($product_filter == $prod['produit_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($prod['nom_produit']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="movement" class="form-control">
                <option value="">Tous les mouvements</option>
                <option value="1" <?php echo ($movement_filter === '1') ? 'selected' : ''; ?>>Entrées</option>
                <option value="0" <?php echo ($movement_filter === '0') ? 'selected' : ''; ?>>Sorties</option>
            </select>
            
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrer
            </button>
            
            <a href="?" class="btn btn-warning">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <!-- Tableau des mouvements -->
        <?php if (!empty($movements)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Emplacement</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Date</th>
                        <th>Valeur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr class="<?php echo $movement['statut_flux'] == 1 ? 'movement-in' : 'movement-out'; ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($movement['url_image'] ?: 'https://via.placeholder.com/50'); ?>" 
                                         alt="<?php echo htmlspecialchars($movement['nom_produit']); ?>" 
                                         class="product-img">
                                    <div>
                                        <strong><?php echo htmlspecialchars($movement['nom_produit']); ?></strong>
                                        <br><small>ID: <?php echo $movement['produit_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="location-badge">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($movement['numero_emplacement']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="movement-type <?php echo $movement['statut_flux'] == 1 ? 'movement-in' : 'movement-out'; ?>">
                                    <i class="fas fa-<?php echo $movement['statut_flux'] == 1 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo $movement['statut_flux'] == 1 ? 'Entrée' : 'Sortie'; ?>
                                </span>
                            </td>
                            <td>
                                <strong style="font-size: 16px;">
                                    <?php echo number_format($movement['quantite'], 0); ?>
                                </strong>
                            </td>
                            <td>
                                <div>
                                    <?php echo date('d/m/Y', strtotime($movement['date_mouvement'])); ?>
                                    <br>
                                    <small><?php echo date('H:i', strtotime($movement['date_mouvement'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo number_format($movement['valeur_stock'], 2); ?> DH</strong>
                            </td>
                            <td>
                                <button onclick="editMovement(<?php echo $movement['produit_id']; ?>, <?php echo $movement['emplacement_id']; ?>)" 
                                        class="btn btn-warning btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteMovement(<?php echo $movement['produit_id']; ?>, <?php echo $movement['emplacement_id']; ?>)" 
                                        class="btn btn-danger btn-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-exchange-alt" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                <h3>Aucun mouvement trouvé</h3>
                <p>Aucun mouvement de stock ne correspond à vos critères de recherche.</p>
                <button onclick="openMovementModal()" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Enregistrer le premier mouvement
                </button>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal d'ajout de mouvement -->
<div id="movementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Nouveau Mouvement de Stock</h3>
            <span class="close" onclick="closeModal('movementModal')">&times;</span>
        </div>
        <form method="POST" id="movementForm">
            <input type="hidden" name="action" value="add_movement">
            
            <div class="form-group">
                <label for="produit_id">Produit *</label>
                <select id="produit_id" name="produit_id" class="form-control" required>
                    <option value="">Sélectionner un produit</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['produit_id']; ?>">
                            <?php echo htmlspecialchars($product['nom_produit']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="emplacement_id">Emplacement *</label>
                <select id="emplacement_id" name="emplacement_id" class="form-control" required>
                    <option value="">Sélectionner un emplacement</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['emplacement_id']; ?>">
                            <?php echo htmlspecialchars($location['numero_emplacement']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="statut_flux">Type de mouvement *</label>
                <select id="statut_flux" name="statut_flux" class="form-control" required>
                    <option value="1">Entrée (ajout de stock)</option>
                    <option value="0">Sortie (retrait de stock)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantite">Quantité *</label>
                <input type="number" id="quantite" name="quantite" class="form-control" 
                       min="0.01" step="0.01" required placeholder="Ex: 10">
            </div>
            
            <div class="form-group">
                <label for="raison">Raison du mouvement</label>
                <input type="text" id="raison" name="raison" class="form-control" 
                       placeholder="Ex: Réapprovisionnement, Vente, Transfert, Inventaire...">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-danger" onclick="closeModal('movementModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal d'édition de mouvement -->
<div id="editMovementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Modifier le Mouvement</h3>
            <span class="close" onclick="closeModal('editMovementModal')">&times;</span>
        </div>
        <form method="POST" id="editMovementForm">
            <input type="hidden" name="action" value="add_movement">
            <input type="hidden" name="produit_id" id="edit_produit_id">
            <input type="hidden" name="emplacement_id" id="edit_emplacement_id">
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong>Produit:</strong>
                    <span id="edit_product_name"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong>Emplacement:</strong>
                    <span id="edit_location_name"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <strong>Stock actuel:</strong>
                    <span id="edit_current_stock"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_statut_flux">Type de mouvement *</label>
                <select id="edit_statut_flux" name="statut_flux" class="form-control" required>
                    <option value="1">Entrée (ajout de stock)</option>
                    <option value="0">Sortie (retrait de stock)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_quantite">Quantité *</label>
                <input type="number" id="edit_quantite" name="quantite" class="form-control" 
                       min="0.01" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="edit_raison">Raison du mouvement</label>
                <input type="text" id="edit_raison" name="raison" class="form-control" 
                       placeholder="Ex: Correction, Ajustement, Transfert...">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-danger" onclick="closeModal('editMovementModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Open movement modal
function openMovementModal() {
    document.getElementById('movementModal').style.display = 'block';
}

// Edit movement
function editMovement(productId, locationId) {
    // Cette fonction nécessiterait un appel AJAX pour récupérer les détails
    // Pour la démo, on simule
    document.getElementById('edit_produit_id').value = productId;
    document.getElementById('edit_emplacement_id').value = locationId;
    document.getElementById('edit_product_name').textContent = 'Produit ID: ' + productId;
    document.getElementById('edit_location_name').textContent = 'Emplacement ID: ' + locationId;
    document.getElementById('edit_current_stock').textContent = 'Chargement...';
    
    document.getElementById('editMovementModal').style.display = 'block';
}

// Delete movement
function deleteMovement(productId, locationId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce mouvement ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_movement">
            <input type="hidden" name="produit_id" value="${productId}">
            <input type="hidden" name="emplacement_id" value="${locationId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if (modalId === 'movementModal') {
        document.getElementById('movementForm').reset();
    }
}

// Auto-hide alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.id === 'movementForm' || this.id === 'editMovementForm') {
                const quantity = parseFloat(this.querySelector('[name="quantite"]').value);
                
                if (isNaN(quantity) || quantity <= 0) {
                    e.preventDefault();
                    alert('Veuillez saisir une quantité valide (supérieure à 0).');
                    return;
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            }
        });
    });
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
    
    // Quick add movement with Ctrl+M
    if (e.ctrlKey && e.key.toLowerCase() === 'm') {
        e.preventDefault();
        openMovementModal();
    }
});

// Export movements data
function exportMovements() {
    if (confirm('Voulez-vous exporter les mouvements de stock en CSV ?')) {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Produit,Emplacement,Type,Quantité,Date,Heure,Valeur\n";
        
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const produit = cells[0].querySelector('strong').textContent.trim();
                const emplacement = cells[1].querySelector('.location-badge').textContent.trim();
                const type = cells[2].querySelector('.movement-type').textContent.trim();
                const quantite = cells[3].querySelector('strong').textContent.trim();
                const dateTime = cells[4].textContent.trim().replace(/\s+/g, ' ');
                const valeur = cells[5].querySelector('strong').textContent.trim();
                
                csvContent += `"${produit}","${emplacement}","${type}","${quantite}","${dateTime}","${valeur}"\n`;
            }
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "mouvements-stock-" + new Date().toISOString().split('T')[0] + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Export terminé avec succès !', 'success');
    }
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

// Quick movement templates
function useMovementTemplate(type) {
    const templates = {
        restock: {
            statut_flux: '1',
            raison: 'Réapprovisionnement'
        },
        sale: {
            statut_flux: '0',
            raison: 'Vente'
        },
        transfer: {
            statut_flux: '0',
            raison: 'Transfert'
        },
        inventory: {
            statut_flux: '1',
            raison: 'Ajustement inventaire'
        },
        damaged: {
            statut_flux: '0',
            raison: 'Produit endommagé'
        }
    };
    
    const template = templates[type];
    if (template) {
        document.getElementById('statut_flux').value = template.statut_flux;
        document.getElementById('raison').value = template.raison;
    }
}

// Add quick template buttons to the modal
document.addEventListener('DOMContentLoaded', function() {
    const reasonField = document.getElementById('raison');
    if (reasonField) {
        const templatesDiv = document.createElement('div');
        templatesDiv.style.marginTop = '10px';
        templatesDiv.innerHTML = `
            <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Modèles rapides:</label>
            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                <button type="button" class="btn btn-info btn-sm" onclick="useMovementTemplate('restock')">Réappro</button>
                <button type="button" class="btn btn-info btn-sm" onclick="useMovementTemplate('sale')">Vente</button>
                <button type="button" class="btn btn-info btn-sm" onclick="useMovementTemplate('transfer')">Transfert</button>
                <button type="button" class="btn btn-info btn-sm" onclick="useMovementTemplate('inventory')">Inventaire</button>
                <button type="button" class="btn btn-info btn-sm" onclick="useMovementTemplate('damaged')">Endommagé</button>
            </div>
        `;
        reasonField.parentNode.appendChild(templatesDiv);
    }
});

// Real-time stock calculation preview
document.getElementById('statut_flux')?.addEventListener('change', updateStockPreview);
document.getElementById('quantite')?.addEventListener('input', updateStockPreview);

function updateStockPreview() {
    // Cette fonction pourrait afficher un aperçu du stock final après le mouvement
    // Pour l'implémenter complètement, il faudrait récupérer le stock actuel via AJAX
}

console.log('OpticLook Stock Movements loaded successfully');
</script>

</body>
</html>