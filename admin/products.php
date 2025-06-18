<?php
// admin/products.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'products';
$page_title = 'Gestion des Produits';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
        case 'edit':
            $product_id = $_POST['product_id'] ?? null;
            $nom_produit = trim($_POST['nom_produit']);
            $categorie_id = $_POST['categorie_id'];
            $prix = $_POST['prix'];
            $quantite_stock = $_POST['quantite_stock'];
            $genre = $_POST['genre'];
            $url_image = trim($_POST['url_image']);
            $description_produit = trim($_POST['description_produit']);
            
            if (empty($nom_produit) || empty($categorie_id) || empty($prix) || empty($quantite_stock)) {
                $error_message = "Veuillez remplir tous les champs obligatoires.";
            } else {
                try {
                    if ($action === 'add') {
                        $sql = "INSERT INTO produits (nom_produit, categorie_id, prix, quantite_stock, genre, url_image, description_produit) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nom_produit, $categorie_id, $prix, $quantite_stock, $genre, $url_image, $description_produit]);
                        $success_message = "Produit ajouté avec succès.";
                    } else {
                        $sql = "UPDATE produits SET nom_produit = ?, categorie_id = ?, prix = ?, quantite_stock = ?, genre = ?, url_image = ?, description_produit = ? WHERE produit_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nom_produit, $categorie_id, $prix, $quantite_stock, $genre, $url_image, $description_produit, $product_id]);
                        $success_message = "Produit modifié avec succès.";
                    }
                    $action = 'list'; // Retour à la liste
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la sauvegarde: " . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $product_id = $_POST['product_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM produits WHERE produit_id = ?");
                $stmt->execute([$product_id]);
                $success_message = "Produit supprimé avec succès.";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            $action = 'list';
            break;
            
        case 'bulk_update_stock':
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
                $success_message = "$updated_count produits mis à jour.";
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
            $action = 'list';
            break;
    }
}

// Récupération des données selon l'action
switch ($action) {
    case 'edit':
        $product_id = $_GET['id'] ?? null;
        if ($product_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM produits WHERE produit_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                if (!$product) {
                    $error_message = "Produit non trouvé.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la récupération du produit.";
                $action = 'list';
            }
        }
        break;
        
    case 'list':
    default:
        // Paramètres de recherche et filtrage
        $search = $_GET['search'] ?? '';
        $category_filter = $_GET['category'] ?? '';
        $stock_filter = $_GET['stock'] ?? '';
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
        }

if ($stock_filter === 'low') {
            $where_conditions[] = "p.quantite_stock < 10";
        } elseif ($stock_filter === 'out') {
            $where_conditions[] = "p.quantite_stock = 0";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        try {
            // Compter le total
            $count_sql = "SELECT COUNT(*) as total FROM produits p JOIN categories c ON p.categorie_id = c.categorie_id $where_clause";
            $stmt = $pdo->prepare($count_sql);
            $stmt->execute($params);
            $total_products = $stmt->fetch()['total'];
            
            // Récupérer les produits
            $sql = "SELECT p.*, c.nom_categorie 
                    FROM produits p 
                    JOIN categories c ON p.categorie_id = c.categorie_id 
                    $where_clause 
                    ORDER BY p.produit_id DESC 
                    LIMIT $per_page OFFSET $offset";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            $total_pages = ceil($total_products / $per_page);
            
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la récupération des produits: " . $e->getMessage();
            $products = [];
            $total_products = 0;
            $total_pages = 1;
        }
        break;
}

// Récupérer les catégories pour les formulaires
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

        .btn-primary {
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgb(59, 158, 76);
        }

        .btn-success { background: linear-gradient(135deg,rgb(124, 252, 188),rgb(15, 207, 111)); color: white; }
        .btn-warning { background: linear-gradient(135deg,rgb(224, 255, 85),rgb(238, 255, 0)); color: #333; }
        .btn-danger  { background:rgb(255, 0, 21); color:rgb(255, 0, 0); ; color: white; }
        .btn-info { background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; }
         

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

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .stock-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-high { background:rgb(129, 236, 154); color:rgb(99, 184, 43); }
        .stock-medium { background: #fff3cd; color:rgb(205, 214, 82); }
        .stock-low { background:rgb(245, 198, 201); color:rgb(233, 17, 38); }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
            flex-wrap: wrap;
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
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        .stock-input {
            width: 80px;
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
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
            <i class="fas fa-box"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <?php if ($action !== 'add' && $action !== 'edit'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un produit
                </a>
            <?php endif; ?>
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <a href="?" class="btn btn-info">
                    <i class="fas fa-list"></i> Retour à la liste
                </a>
            <?php endif; ?>
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

    <?php if ($action === 'list'): ?>
        <!-- Liste des produits -->
        <div class="content-card">
            <!-- Statistiques -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Produits</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $low_stock_count = 0;
                        foreach ($products as $product) {
                            if ($product['quantite_stock'] < 10) $low_stock_count++;
                        }
                        echo $low_stock_count;
                        ?>
                    </div>
                    <div class="stat-label">Stock Faible</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php 
                        $total_value = 0;
                        foreach ($products as $product) {
                            $total_value += $product['prix'] * $product['quantite_stock'];
                        }
                        echo number_format($total_value, 0);
                        ?> DH
                    </div>
                    <div class="stat-label">Valeur Stock</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Catégories</div>
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
                    <option value="low" <?php echo ($stock_filter === 'low') ? 'selected' : ''; ?>>Stock faible (&lt; 10)</option>
                    <option value="out" <?php echo ($stock_filter === 'out') ? 'selected' : ''; ?>>Rupture de stock</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                
                <a href="?" class="btn btn-warning">
                    <i class="fas fa-undo"></i> Reset
                </a>
                
                <button type="button" class="btn btn-info" onclick="toggleBulkMode()">
                    <i class="fas fa-edit"></i> Mise à jour en lot
                </button>
            </form>

            <!-- Actions en lot -->
            <div class="bulk-actions" id="bulkActions">
                <form method="POST" id="bulkUpdateForm">
                    <input type="hidden" name="action" value="bulk_update_stock">
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
                            <th>Image</th>
                            <th>Nom du produit</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Genre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['url_image'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRkZGIiBzdHJva2U9IiNEREQiLz4KPHN2ZyB4PSIyMCIgeT0iMjAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM5OTkiPgo8cGF0aCBkPSJtMTUgMTggNi02LTYtNi02IDYgNiA2eiIvPgo8L3N2Zz4KPC9zdmc+'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['nom_produit']); ?>" 
                                         class="product-img">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong>
                                    <?php if (!empty($product['description_produit'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description_produit'], 0, 50)) . (strlen($product['description_produit']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['nom_categorie']); ?></td>
                                <td><strong><?php echo number_format($product['prix'], 2); ?> DH</strong></td>
                                <td>
                                    <div class="bulk-stock-edit" style="display: none;">
                                        <input type="number" name="stock_updates[<?php echo $product['produit_id']; ?>]" 
                                               value="<?php echo $product['quantite_stock']; ?>" 
                                               class="stock-input" min="0">
                                    </div>
                                    <div class="normal-stock-display">
                                        <span class="stock-status <?php 
                                            if ($product['quantite_stock'] == 0) echo 'stock-low';
                                            elseif ($product['quantite_stock'] < 10) echo 'stock-medium';
                                            else echo 'stock-high';
                                        ?>">
                                            <?php echo $product['quantite_stock']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['genre'] ?: 'N/A'); ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $product['produit_id']; ?>" 
                                       class="btn btn-warning btn-sm" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteProduct(<?php echo $product['produit_id']; ?>)" 
                                            class="btn btn-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="../detaill.php?id=<?php echo $product['produit_id']; ?>" 
                                       target="_blank" class="btn btn-info btn-sm" title="Voir sur le site">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock=<?php echo $stock_filter; ?>">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                    <h3>Aucun produit trouvé</h3>
                    <p>Aucun produit ne correspond à vos critères de recherche.</p>
                    <a href="?action=add" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Ajouter le premier produit
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire d'ajout/modification -->
        <div class="content-card">
            <h2>
                <i class="fas fa-<?php echo ($action === 'add') ? 'plus' : 'edit'; ?>"></i>
                <?php echo ($action === 'add') ? 'Ajouter un nouveau produit' : 'Modifier le produit'; ?>
            </h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['produit_id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_produit">Nom du produit *</label>
                        <input type="text" id="nom_produit" name="nom_produit" class="form-control" required
                               value="<?php echo htmlspecialchars($product['nom_produit'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="categorie_id">Catégorie *</label>
                        <select id="categorie_id" name="categorie_id" class="form-control" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['categorie_id']; ?>"
                                        <?php echo (isset($product) && $product['categorie_id'] == $category['categorie_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom_categorie']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="prix">Prix (DH) *</label>
                        <input type="number" id="prix" name="prix" class="form-control" step="0.01" required
                               value="<?php echo htmlspecialchars($product['prix'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="quantite_stock">Quantité en stock *</label>
                        <input type="number" id="quantite_stock" name="quantite_stock" class="form-control" required
                               value="<?php echo htmlspecialchars($product['quantite_stock'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre" class="form-control">
                            <option value="Unisexe" <?php echo (isset($product) && $product['genre'] === 'Unisexe') ? 'selected' : ''; ?>>Unisexe</option>
                            <option value="Homme" <?php echo (isset($product) && $product['genre'] === 'Homme') ? 'selected' : ''; ?>>Homme</option>
                            <option value="Femme" <?php echo (isset($product) && $product['genre'] === 'Femme') ? 'selected' : ''; ?>>Femme</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="url_image">URL de l'image</label>
                        <input type="url" id="url_image" name="url_image" class="form-control"
                               value="<?php echo htmlspecialchars($product['url_image'] ?? ''); ?>">
                        <small style="color: #666;">Laissez vide pour utiliser l'image par défaut</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description_produit">Description</label>
                    <textarea id="description_produit" name="description_produit" class="form-control" rows="4"
                              placeholder="Description détaillée du produit..."><?php echo htmlspecialchars($product['description_produit'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="?" class="btn btn-danger">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> 
                        <?php echo ($action === 'add') ? 'Ajouter le produit' : 'Mettre à jour'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
// Toggle bulk edit mode
function toggleBulkMode() {
    const bulkActions = document.getElementById('bulkActions');
    const bulkEdits = document.querySelectorAll('.bulk-stock-edit');
    const normalDisplays = document.querySelectorAll('.normal-stock-display');
    
    const isVisible = bulkActions.style.display !== 'none' && bulkActions.classList.contains('show');
    
    if (isVisible) {
        bulkActions.classList.remove('show');
        bulkEdits.forEach(edit => edit.style.display = 'none');
        normalDisplays.forEach(display => display.style.display = 'block');
    } else {
        bulkActions.classList.add('show');
        bulkEdits.forEach(edit => edit.style.display = 'block');
        normalDisplays.forEach(display => display.style.display = 'none');
    }
}

// Delete product
function deleteProduct(productId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" value="${productId}">
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

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    if (form && (form.querySelector('input[name="action"][value="add"]') || form.querySelector('input[name="action"][value="edit"]'))) {
        form.addEventListener('submit', function(e) {
            const prix = parseFloat(document.getElementById('prix').value);
            const stock = parseInt(document.getElementById('quantite_stock').value);
            
            if (prix < 0) {
                e.preventDefault();
                alert('Le prix ne peut pas être négatif.');
                return;
            }
            
            if (stock < 0) {
                e.preventDefault();
                alert('La quantité en stock ne peut pas être négative.');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
        });
    }
});

// Preview image URL
document.getElementById('url_image')?.addEventListener('blur', function() {
    const url = this.value;
    if (url) {
        // Create a temporary image to test if URL is valid
        const img = new Image();
        img.onload = function() {
            console.log('Image URL is valid');
        };
        img.onerror = function() {
            alert('L\'URL de l\'image semble invalide. Veuillez vérifier.');
        };
        img.src = url;
    }
});

// Bulk update form validation
document.getElementById('bulkUpdateForm')?.addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('.stock-input');
    let hasChanges = false;
    
    inputs.forEach(input => {
        if (input.value !== input.defaultValue) {
            hasChanges = true;
        }
        
        if (parseInt(input.value) < 0) {
            e.preventDefault();
            alert('Les quantités en stock ne peuvent pas être négatives.');
            return;
        }
    });
    
    if (!hasChanges) {
        e.preventDefault();
        alert('Aucune modification détectée.');
        return;
    }
    
    if (!confirm('Êtes-vous sûr de vouloir mettre à jour le stock de ces produits ?')) {
        e.preventDefault();
    }
});
</script>

</body>
</html>