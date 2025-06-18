<?php
// admin/stock.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'stock';
$page_title = 'Gestion du Stock';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'update_stock':
            $product_id = $_POST['product_id'];
            $new_stock = $_POST['new_stock'];
            $movement_type = $_POST['movement_type']; // 'set', 'add', 'remove'
            $reason = trim($_POST['reason']);
            
            if (!is_numeric($new_stock) || $new_stock < 0) {
                $error_message = "La quantité doit être un nombre positif.";
            } else {
                try {
                    // Récupérer le stock actuel
                    $stmt = $pdo->prepare("SELECT quantite_stock, nom_produit FROM produits WHERE produit_id = ?");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $old_stock = $product['quantite_stock'];
                        $final_stock = $new_stock;
                        
                        if ($movement_type === 'add') {
                            $final_stock = $old_stock + $new_stock;
                        } elseif ($movement_type === 'remove') {
                            $final_stock = max(0, $old_stock - $new_stock);
                        }
                        
                        // Mettre à jour le stock
                        $stmt = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE produit_id = ?");
                        $stmt->execute([$final_stock, $product_id]);
                        
                        // Enregistrer le mouvement (optionnel - à implémenter selon votre structure)
                        $movement_description = "";
                        switch ($movement_type) {
                            case 'set':
                                $movement_description = "Stock défini à $final_stock (était $old_stock)";
                                break;
                            case 'add':
                                $movement_description = "Ajout de $new_stock unités ($old_stock → $final_stock)";
                                break;
                            case 'remove':
                                $movement_description = "Retrait de $new_stock unités ($old_stock → $final_stock)";
                                break;
                        }
                        
                        $success_message = "Stock mis à jour pour {$product['nom_produit']}: $movement_description. Raison: $reason";
                    } else {
                        $error_message = "Produit non trouvé.";
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                }
            }
            $action = 'list';
            break;
            
        case 'bulk_update':
            $updates = $_POST['stock_updates'] ?? [];
            $updated_count = 0;
            
            try {
                foreach ($updates as $product_id => $new_stock) {
                    if (is_numeric($new_stock) && $new_stock >= 0) {
                        $stmt = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE produit_id = ?");
                        $stmt->execute([$new_stock, $product_id]);
                        $updated_count++;
                    }
                }
                $success_message = "$updated_count produits mis à jour en lot.";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la mise à jour en lot: " . $e->getMessage();
            }
            $action = 'list';
            break;
    }
}

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$stock_filter = $_GET['stock'] ?? '';
$sort = $_GET['sort'] ?? 'stock_asc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "p.nom_produit LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.categorie_id = ?";
    $params[] = $category_filter;
}

if ($stock_filter === 'low') {
    $where_conditions[] = "p.quantite_stock < 10";
} elseif ($stock_filter === 'out') {
    $where_conditions[] = "p.quantite_stock = 0";
} elseif ($stock_filter === 'high') {
    $where_conditions[] = "p.quantite_stock >= 50";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Tri
$order_clause = "ORDER BY ";
switch ($sort) {
    case 'stock_asc':
        $order_clause .= "p.quantite_stock ASC";
        break;
    case 'stock_desc':
        $order_clause .= "p.quantite_stock DESC";
        break;
    case 'value_asc':
        $order_clause .= "(p.quantite_stock * p.prix) ASC";
        break;
    case 'value_desc':
        $order_clause .= "(p.quantite_stock * p.prix) DESC";
        break;
    case 'name_asc':
        $order_clause .= "p.nom_produit ASC";
        break;
    default:
        $order_clause .= "p.quantite_stock ASC";
}

try {
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total 
                  FROM produits p 
                  JOIN categories c ON p.categorie_id = c.categorie_id 
                  $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_products = $stmt->fetch()['total'];
    
    // Récupérer les produits
    $sql = "SELECT p.*, c.nom_categorie,
                   (p.quantite_stock * p.prix) as stock_value
            FROM produits p 
            JOIN categories c ON p.categorie_id = c.categorie_id 
            $where_clause 
            $order_clause 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $total_pages = ceil($total_products / $per_page);
    
    // Statistiques du stock
    $stats_sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(quantite_stock) as total_stock,
                    SUM(quantite_stock * prix) as total_value,
                    SUM(CASE WHEN quantite_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN quantite_stock < 10 THEN 1 ELSE 0 END) as low_stock,
                    AVG(quantite_stock) as avg_stock
                  FROM produits p";
    $stmt = $pdo->query($stats_sql);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
    $products = [];
    $total_products = 0;
    $total_pages = 1;
    $stats = ['total_products' => 0, 'total_stock' => 0, 'total_value' => 0, 'out_of_stock' => 0, 'low_stock' => 0, 'avg_stock' => 0];
}

// Récupérer les catégories pour les filtres
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
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
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76));
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

        .btn-primary { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); color: white; }
        .btn-success { background: white; color: rgb(48, 146, 86); }
        .btn-warning { background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)); color:white; }
        .btn-danger { background: linear-gradient(135deg,rgb(253, 121, 121),rgb(255, 0, 0)); color: white; }
        .btn-info { background: linear-gradient(135deg, #74b9ff,rgb(19, 169, 255)); color: white; }

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

        .stat-item.warning { background: rgba(255, 193, 7, 0.1); border-color: rgb(238, 255, 7); }
        .stat-item.danger { background: rgba(220, 53, 69, 0.1); border-color: rgba(255, 0, 25, 0.3); }
        .stat-item.success { background: rgba(40, 167, 69, 0.1); border-color: rgba(27, 201, 68, 0.94); }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-item.warning .stat-number { color: #ffc107; }
        .stat-item.danger .stat-number { color: #dc3545; }
        .stat-item.success .stat-number { color: #28a745; }

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

        .data-table tr.danger {
            background: rgba(220, 53, 69, 0.05);
            border-left: 4px solid #dc3545;
        }

        .data-table tr.warning {
            background: rgba(255, 193, 7, 0.05);
            border-left: 4px solid #ffc107;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .stock-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stock-out { background: #f8d7da; color:rgb(255, 0, 0); }
        .stock-low { background: #fff3cd; color:rgba(255, 231, 10, 0.9); }
        .stock-medium { background: #d1ecf1; color:rgb(6, 218, 255); }
        .stock-high { background: #d4edda; color:rgb(15, 216, 62); }

        .stock-input {
            width: 80px;
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
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
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76));
            color: white;
            border-color: transparent;
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

        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
            <i class="fas fa-warehouse"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <button class="btn btn-info" onclick="toggleBulkMode()">
                <i class="fas fa-edit"></i> Mode édition
            </button>
            <button class="btn btn-success" onclick="openStockModal()">
                <i class="fas fa-plus"></i> Ajuster stock
            </button>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-box"></i> Produits
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

    <!-- Gestion du stock -->
    <div class="content-card">
        <!-- Statistiques -->
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                <div class="stat-label">Produits</div>
            </div>
            <div class="stat-item success">
                <div class="stat-number"><?php echo $stats['total_stock']; ?></div>
                <div class="stat-label">Stock Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['total_value'], 0); ?> DH</div>
                <div class="stat-label">Valeur Stock</div>
            </div>
            <div class="stat-item danger">
                <div class="stat-number"><?php echo $stats['out_of_stock']; ?></div>
                <div class="stat-label">Rupture Stock</div>
            </div>
            <div class="stat-item warning">
                <div class="stat-number"><?php echo $stats['low_stock']; ?></div>
                <div class="stat-label">Stock Faible</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['avg_stock'], 1); ?></div>
                <div class="stat-label">Moyenne</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="filters-section">
            <input type="text" name="search" class="search-box" placeholder="Rechercher un produit..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="category" class="form-control">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['categorie_id']; ?>" 
                            <?php echo ($category_filter == $category['categorie_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nom_categorie']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="stock" class="form-control">
                <option value="">Tous les stocks</option>
                <option value="out" <?php echo ($stock_filter === 'out') ? 'selected' : ''; ?>>Rupture (0)</option>
                <option value="low" <?php echo ($stock_filter === 'low') ? 'selected' : ''; ?>>Faible (&lt; 10)</option>
                <option value="high" <?php echo ($stock_filter === 'high') ? 'selected' : ''; ?>>Élevé (≥ 50)</option>
            </select>
            
            <select name="sort" class="form-control">
                <option value="stock_asc" <?php echo ($sort === 'stock_asc') ? 'selected' : ''; ?>>Stock croissant</option>
                <option value="stock_desc" <?php echo ($sort === 'stock_desc') ? 'selected' : ''; ?>>Stock décroissant</option>
                <option value="value_desc" <?php echo ($sort === 'value_desc') ? 'selected' : ''; ?>>Valeur décroissante</option>
                <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Nom A-Z</option>
            </select>
            
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
                <h4><i class="fas fa-boxes"></i> Mise à jour du stock en lot</h4>
                <p>Modifiez les quantités dans le tableau ci-dessous et cliquez sur "Mettre à jour".</p>
                <div style="margin-top: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Mettre à jour le stock
                    </button>
                    <button type="button" class="btn btn-danger" onclick="toggleBulkMode()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>

        <!-- Tableau des produits -->
        <?php if (!empty($products)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix Unitaire</th>
                        <th>Stock Actuel</th>
                        <th>Valeur Stock</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php 
                        $row_class = '';
                        if ($product['quantite_stock'] == 0) {
                            $row_class = 'danger';
                        } elseif ($product['quantite_stock'] < 10) {
                            $row_class = 'warning';
                        }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($product['url_image'] ?: 'https://via.placeholder.com/50'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['nom_produit']); ?>" 
                                         class="product-img">
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong>
                                        <br><small>ID: <?php echo $product['produit_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($product['nom_categorie']); ?></td>
                            <td><strong><?php echo number_format($product['prix'], 2); ?> DH</strong></td>
                            <td>
                                <!-- Mode normal -->
                                <div class="normal-stock-display">
                                    <strong style="font-size: 16px;"><?php echo $product['quantite_stock']; ?></strong>
                                </div>
                                
                                <!-- Mode édition en lot -->
                                <div class="bulk-stock-edit" style="display: none;">
                                    <input type="number" name="stock_updates[<?php echo $product['produit_id']; ?>]" 
                                           value="<?php echo $product['quantite_stock']; ?>" 
                                           class="stock-input" min="0">
                                </div>
                            </td>
                            <td><strong><?php echo number_format($product['stock_value'], 2); ?> DH</strong></td>
                            <td>
                                <span class="stock-status <?php 
                                    if ($product['quantite_stock'] == 0) echo 'stock-out';
                                    elseif ($product['quantite_stock'] < 10) echo 'stock-low';
                                    elseif ($product['quantite_stock'] < 30) echo 'stock-medium';
                                    else echo 'stock-high';
                                ?>">
                                    <?php 
                                    if ($product['quantite_stock'] == 0) echo 'Rupture';
                                    elseif ($product['quantite_stock'] < 10) echo 'Faible';
                                    elseif ($product['quantite_stock'] < 30) echo 'Moyen';
                                    else echo 'Bon';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="normal-actions">
                                    <button onclick="openStockModal(<?php echo $product['produit_id']; ?>, '<?php echo addslashes($product['nom_produit']); ?>', <?php echo $product['quantite_stock']; ?>)" 
                                            class="btn btn-warning btn-sm" title="Ajuster stock">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($product['quantite_stock'] == 0): ?>
                                        <button onclick="quickStock(<?php echo $product['produit_id']; ?>, 'add', 10)" 
                                                class="btn btn-success btn-sm" title="Réapprovisionner">
                                            <i class="fas fa-plus"></i> 10
                                        </button>
                                    <?php elseif ($product['quantite_stock'] < 10): ?>
                                        <button onclick="quickStock(<?php echo $product['produit_id']; ?>, 'add', 20)" 
                                                class="btn btn-info btn-sm" title="Ajouter 20">
                                            <i class="fas fa-plus"></i> 20
                                        </button>
                                    <?php endif; ?>
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
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort; ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-boxes" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                <h3>Aucun produit trouvé</h3>
                <p>Aucun produit ne correspond à vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal d'ajustement de stock -->
<div id="stockModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Ajuster le stock</h3>
            <span class="close" onclick="closeModal('stockModal')">&times;</span>
        </div>
        <form method="POST" id="stockForm">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="product_id" id="modalProductId">
            
            <div style="margin-bottom: 20px;">
                <label><strong>Produit:</strong></label>
                
                <p id="modalProductName" style="color: #666;"></p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label><strong>Stock actuel:</strong></label>
                <p id="modalCurrentStock" style="font-size: 18px; font-weight: bold; color: #667eea;"></p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="movement_type">Type d'opération:</label>
                <select id="movement_type" name="movement_type" class="form-control" onchange="updateStockLabel()">
                    <option value="set">Définir le stock à</option>
                    <option value="add">Ajouter au stock</option>
                    <option value="remove">Retirer du stock</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="new_stock" id="stockLabel">Nouvelle quantité:</label>
                <input type="number" id="new_stock" name="new_stock" class="form-control" min="0" required>
                <small id="stockPreview" style="color: #666; margin-top: 5px; display: block;"></small>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="reason">Raison (optionnel):</label>
                <input type="text" id="reason" name="reason" class="form-control" 
                       placeholder="Ex: Réapprovisionnement, Vente, Casse, Inventaire...">
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('stockModal')">
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
let currentStock = 0;

// Toggle bulk edit mode
function toggleBulkMode() {
    const bulkActions = document.getElementById('bulkActions');
    const bulkEdits = document.querySelectorAll('.bulk-stock-edit');
    const normalDisplays = document.querySelectorAll('.normal-stock-display');
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

// Open stock adjustment modal
function openStockModal(productId = null, productName = '', stock = 0) {
    if (productId) {
        document.getElementById('modalProductId').value = productId;
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalCurrentStock').textContent = stock + ' unités';
        currentStock = parseInt(stock);
        
        // Reset form
        document.getElementById('movement_type').value = 'set';
        document.getElementById('new_stock').value = '';
        document.getElementById('reason').value = '';
        updateStockLabel();
    }
    
    document.getElementById('stockModal').style.display = 'block';
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Update stock label and preview
function updateStockLabel() {
    const movementType = document.getElementById('movement_type').value;
    const stockLabel = document.getElementById('stockLabel');
    const newStockInput = document.getElementById('new_stock');
    
    switch (movementType) {
        case 'set':
            stockLabel.textContent = 'Nouvelle quantité:';
            newStockInput.placeholder = 'Ex: 50';
            break;
        case 'add':
            stockLabel.textContent = 'Quantité à ajouter:';
            newStockInput.placeholder = 'Ex: 20';
            break;
        case 'remove':
            stockLabel.textContent = 'Quantité à retirer:';
            newStockInput.placeholder = 'Ex: 5';
            break;
    }
    
    updateStockPreview();
}

// Update stock preview
function updateStockPreview() {
    const movementType = document.getElementById('movement_type').value;
    const newStockValue = parseInt(document.getElementById('new_stock').value) || 0;
    const stockPreview = document.getElementById('stockPreview');
    
    let finalStock = 0;
    let previewText = '';
    
    switch (movementType) {
        case 'set':
            finalStock = newStockValue;
            previewText = `Stock final: ${finalStock} unités`;
            break;
        case 'add':
            finalStock = currentStock + newStockValue;
            previewText = `Stock final: ${currentStock} + ${newStockValue} = ${finalStock} unités`;
            break;
        case 'remove':
            finalStock = Math.max(0, currentStock - newStockValue);
            previewText = `Stock final: ${currentStock} - ${newStockValue} = ${finalStock} unités`;
            if (finalStock === 0 && currentStock - newStockValue < 0) {
                previewText += ' (limité à 0)';
            }
            break;
    }
    
    stockPreview.textContent = previewText;
    
    // Color code the preview
    if (finalStock === 0) {
        stockPreview.style.color = '#dc3545';
    } else if (finalStock < 10) {
        stockPreview.style.color = '#ffc107';
    } else {
        stockPreview.style.color = '#28a745';
    }
}

// Quick stock adjustment
function quickStock(productId, action, quantity) {
    if (confirm(`Êtes-vous sûr de vouloir ${action === 'add' ? 'ajouter' : 'retirer'} ${quantity} unités ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="product_id" value="${productId}">
            <input type="hidden" name="movement_type" value="${action}">
            <input type="hidden" name="new_stock" value="${quantity}">
            <input type="hidden" name="reason" value="Ajustement rapide">
        `;
        document.body.appendChild(form);
        form.submit();
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update stock preview on input change
    document.getElementById('new_stock').addEventListener('input', updateStockPreview);
    
    // Form validation
    document.getElementById('stockForm').addEventListener('submit', function(e) {
        const newStock = parseInt(document.getElementById('new_stock').value);
        const movementType = document.getElementById('movement_type').value;
        
        if (isNaN(newStock) || newStock < 0) {
            e.preventDefault();
            alert('Veuillez saisir une quantité valide (nombre positif).');
            return;
        }
        
        if (movementType === 'remove' && newStock > currentStock) {
            if (!confirm(`Attention: Vous retirez plus que le stock disponible (${currentStock}). Le stock sera mis à 0. Continuer ?`)) {
                e.preventDefault();
                return;
            }
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mise à jour...';
    });
    
    // Bulk update form validation
    document.getElementById('bulkUpdateForm')?.addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('.stock-input');
        let hasChanges = false;
        let hasErrors = false;
        
        inputs.forEach(input => {
            const value = parseInt(input.value);
            if (isNaN(value) || value < 0) {
                hasErrors = true;
                input.style.borderColor = '#dc3545';
            } else {
                input.style.borderColor = '#ddd';
                if (value !== parseInt(input.defaultValue)) {
                    hasChanges = true;
                }
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Veuillez corriger les valeurs en rouge (nombres positifs uniquement).');
            return;
        }
        
        if (!hasChanges) {
            e.preventDefault();
            alert('Aucune modification détectée.');
            return;
        }
        
        if (!confirm('Êtes-vous sûr de vouloir mettre à jour le stock de ces produits ?')) {
            e.preventDefault();
        }
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
    
    // Quick actions with keyboard
    if (e.ctrlKey && e.shiftKey) {
        switch(e.key.toLowerCase()) {
            case 's':
                e.preventDefault();
                openStockModal();
                break;
            case 'b':
                e.preventDefault();
                toggleBulkMode();
                break;
        }
    }
});

// Export stock data
function exportStock() {
    if (confirm('Voulez-vous exporter les données de stock en CSV ?')) {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Produit,Catégorie,Prix,Stock,Valeur,Statut\n";
        
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const produit = cells[0].querySelector('strong').textContent.trim();
                const categorie = cells[1].textContent.trim();
                const prix = cells[2].querySelector('strong').textContent.trim();
                const stock = cells[3].querySelector('strong').textContent.trim();
                const valeur = cells[4].querySelector('strong').textContent.trim();
                const statut = cells[5].querySelector('.stock-status').textContent.trim();
                
                csvContent += `"${produit}","${categorie}","${prix}","${stock}","${valeur}","${statut}"\n`;
            }
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "stock-" + new Date().toISOString().split('T')[0] + ".csv");
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

// Auto-refresh low stock alerts
setInterval(function() {
    const lowStockItems = document.querySelectorAll('.stock-low, .stock-out').length;
    if (lowStockItems > 0) {
        // Update page title with alert count
        document.title = `(${lowStockItems}) ${document.title.replace(/^\(\d+\)\s/, '')}`;
    }
}, 60000); // Check every minute

console.log('OpticLook Stock Management loaded successfully');
</script>

</body>
</html>