<?php
// admin/categories.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'categories';
$page_title = 'Gestion des Catégories';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
        case 'edit':
            $category_id = $_POST['category_id'] ?? null;
            $nom_categorie = trim($_POST['nom_categorie']);
            
            if (empty($nom_categorie)) {
                $error_message = "Le nom de la catégorie est obligatoire.";
            } else {
                try {
                    // Vérifier l'unicité du nom
                    $name_check_sql = "SELECT categorie_id FROM categories WHERE nom_categorie = ?";
                    if ($action === 'edit') {
                        $name_check_sql .= " AND categorie_id != ?";
                    }
                    $stmt = $pdo->prepare($name_check_sql);
                    $params = [$nom_categorie];
                    if ($action === 'edit') {
                        $params[] = $category_id;
                    }
                    $stmt->execute($params);
                    
                    if ($stmt->fetch()) {
                        $error_message = "Cette catégorie existe déjà.";
                    } else {
                        if ($action === 'add') {
                            $sql = "INSERT INTO categories (nom_categorie) VALUES (?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$nom_categorie]);
                            $success_message = "Catégorie ajoutée avec succès.";
                        } else {
                            $sql = "UPDATE categories SET nom_categorie = ? WHERE categorie_id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$nom_categorie, $category_id]);
                            $success_message = "Catégorie modifiée avec succès.";
                        }
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la sauvegarde: " . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $category_id = $_POST['category_id'];
            try {
                // Vérifier s'il y a des produits associés
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM produits WHERE categorie_id = ?");
                $stmt->execute([$category_id]);
                $product_count = $stmt->fetch()['count'];
                
                if ($product_count > 0) {
                    $error_message = "Impossible de supprimer cette catégorie car elle contient $product_count produit(s).";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE categorie_id = ?");
                    $stmt->execute([$category_id]);
                    $success_message = "Catégorie supprimée avec succès.";
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            $action = 'list';
            break;
    }
}

// Récupération des données selon l'action
switch ($action) {
    case 'edit':
        $category_id = $_GET['id'] ?? null;
        if ($category_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM categories WHERE categorie_id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch();
                if (!$category) {
                    $error_message = "Catégorie non trouvée.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la récupération de la catégorie.";
                $action = 'list';
            }
        }
        break;
        
    case 'list':
    default:
        try {
            // Récupérer les catégories avec le nombre de produits
            $sql = "SELECT c.*, COUNT(p.produit_id) as product_count,
                           SUM(p.quantite_stock) as total_stock,
                           SUM(p.quantite_stock * p.prix) as total_value
                    FROM categories c 
                    LEFT JOIN produits p ON c.categorie_id = p.categorie_id 
                    GROUP BY c.categorie_id
                    ORDER BY c.nom_categorie ASC";
            $stmt = $pdo->query($sql);
            $categories = $stmt->fetchAll();
            
            // Statistiques générales
            $stats_sql = "SELECT 
                            COUNT(DISTINCT c.categorie_id) as total_categories,
                            COUNT(p.produit_id) as total_products,
                            SUM(p.quantite_stock) as total_stock,
                            SUM(p.quantite_stock * p.prix) as total_value
                          FROM categories c 
                          LEFT JOIN produits p ON c.categorie_id = p.categorie_id";
            $stmt = $pdo->query($stats_sql);
            $stats = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la récupération des catégories: " . $e->getMessage();
            $categories = [];
            $stats = ['total_categories' => 0, 'total_products' => 0, 'total_stock' => 0, 'total_value' => 0];
        }
        break;
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
        .btn-success { background: linear-gradient(135deg, #4ecdc4, #44a08d); color: white; }
        .btn-warning { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #333; }
        .btn-danger { background: linear-gradient(135deg,rgb(243, 10, 10),rgb(243, 5, 5)); color: white; }
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

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76)));
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .category-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg,rgb(48, 146, 86),rgb(59, 158, 76));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .category-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .category-stat-number {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
        }

        .category-stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .category-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

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

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
            flex-wrap: wrap;
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

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                flex-direction: column;
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
            <i class="fas fa-tags"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <?php if ($action !== 'add' && $action !== 'edit'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter une catégorie
                </a>
            <?php endif; ?>
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <a href="?" class="btn btn-info">
                    <i class="fas fa-list"></i> Retour à la liste
                </a>
            <?php endif; ?>
            <a href="products.php" class="btn btn-info">
                <i class="fas fa-box"></i> Voir les produits
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

    <?php if ($action === 'list'): ?>
        <!-- Liste des catégories -->
        <div class="content-card">
            <!-- Statistiques -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                    <div class="stat-label">Catégories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Produits</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_stock']; ?></div>
                    <div class="stat-label">Stock Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_value'], 0); ?> DH</div>
                    <div class="stat-label">Valeur Totale</div>
                </div>
            </div>

            <!-- Grille des catégories -->
            <?php if (!empty($categories)): ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-name">
                                    <div class="category-icon">
                                        <i class="fas fa-<?php 
                                            switch(strtolower($category['nom_categorie'])) {
                                                case 'lunettes médicales': echo 'glasses';
                                                case 'lunettes de soleil': echo 'sun';
                                                case 'accessoires': echo 'gem';
                                                default: echo 'tag';
                                            }
                                        ?>"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($category['nom_categorie']); ?></span>
                                </div>
                            </div>
                            
                            <div class="category-stats">
                                <div class="category-stat">
                                    <div class="category-stat-number"><?php echo $category['product_count']; ?></div>
                                    <div class="category-stat-label">Produits</div>
                                </div>
                                <div class="category-stat">
                                    <div class="category-stat-number"><?php echo $category['total_stock'] ?: 0; ?></div>
                                    <div class="category-stat-label">Stock</div>
                                </div>
                                <div class="category-stat">
                                    <div class="category-stat-number"><?php echo number_format($category['total_value'] ?: 0, 0); ?> DH</div>
                                    <div class="category-stat-label">Valeur</div>
                                </div>
                            </div>
                            
                            <div class="category-actions">
                                <a href="products.php?category=<?php echo $category['categorie_id']; ?>" 
                                   class="btn btn-info btn-sm" title="Voir les produits">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=edit&id=<?php echo $category['categorie_id']; ?>" 
                                   class="btn btn-warning btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($category['product_count'] == 0): ?>
                                    <button onclick="deleteCategory(<?php echo $category['categorie_id']; ?>)" 
                                            class="btn btn-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled title="Contient des produits">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>Aucune catégorie trouvée</h3>
                    <p>Commencez par créer votre première catégorie de produits.</p>
                    <a href="?action=add" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Créer la première catégorie
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire d'ajout/modification -->
        <div class="content-card">
            <h2>
                <i class="fas fa-<?php echo ($action === 'add') ? 'plus' : 'edit'; ?>"></i>
                <?php echo ($action === 'add') ? 'Ajouter une nouvelle catégorie' : 'Modifier la catégorie'; ?>
            </h2>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="category_id" value="<?php echo $category['categorie_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom_categorie">Nom de la catégorie *</label>
                    <input type="text" id="nom_categorie" name="nom_categorie" class="form-control" required
                           value="<?php echo htmlspecialchars($category['nom_categorie'] ?? ''); ?>"
                           placeholder="Ex: Lunettes de soleil, Lunettes médicales, Accessoires...">
                    <small style="color: #666; margin-top: 5px; display: block;">
                        Le nom doit être unique et descriptif
                    </small>
                </div>

                <div class="form-actions">
                    <a href="?" class="btn btn-danger">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> 
                        <?php echo ($action === 'add') ? 'Créer la catégorie' : 'Mettre à jour'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
// Delete category
function deleteCategory(categoryId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" value="${categoryId}">
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
    if (form) {
        form.addEventListener('submit', function(e) {
            const categoryName = document.getElementById('nom_categorie').value.trim();
            
            if (categoryName.length < 2) {
                e.preventDefault();
                alert('Le nom de la catégorie doit contenir au moins 2 caractères.');
                return;
            }
            
            if (categoryName.length > 50) {
                e.preventDefault();
                alert('Le nom de la catégorie ne peut pas dépasser 50 caractères.');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
        });
    }
});

// Add smooth animations to category cards
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

console.log('OpticLook Categories Management loaded successfully');
</script>

</body>
</html>