<?php
// admin/locations.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'locations';
$page_title = 'Gestion des Emplacements';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
        case 'edit':
            $emplacement_id = $_POST['emplacement_id'] ?? null;
            $numero_emplacement = trim($_POST['numero_emplacement']);
            
            if (empty($numero_emplacement)) {
                $error_message = "Le numéro d'emplacement est obligatoire.";
            } else {
                try {
                    // Vérifier l'unicité du numéro
                    $check_sql = "SELECT emplacement_id FROM emplacements WHERE numero_emplacement = ?";
                    if ($action === 'edit') {
                        $check_sql .= " AND emplacement_id != ?";
                    }
                    $stmt = $pdo->prepare($check_sql);
                    $params = [$numero_emplacement];
                    if ($action === 'edit') {
                        $params[] = $emplacement_id;
                    }
                    $stmt->execute($params);
                    
                    if ($stmt->fetch()) {
                        $error_message = "Ce numéro d'emplacement existe déjà.";
                    } else {
                        if ($action === 'add') {
                            $sql = "INSERT INTO emplacements (numero_emplacement) VALUES (?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$numero_emplacement]);
                            $success_message = "Emplacement ajouté avec succès.";
                        } else {
                            $sql = "UPDATE emplacements SET numero_emplacement = ? WHERE emplacement_id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$numero_emplacement, $emplacement_id]);
                            $success_message = "Emplacement modifié avec succès.";
                        }
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la sauvegarde: " . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $emplacement_id = $_POST['emplacement_id'];
            try {
                // Vérifier s'il y a des mouvements de stock associés
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mouvementsstock WHERE emplacement_id = ?");
                $stmt->execute([$emplacement_id]);
                $movement_count = $stmt->fetch()['count'];
                
                if ($movement_count > 0) {
                    $error_message = "Impossible de supprimer cet emplacement car il contient des mouvements de stock.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM emplacements WHERE emplacement_id = ?");
                    $stmt->execute([$emplacement_id]);
                    $success_message = "Emplacement supprimé avec succès.";
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
        $emplacement_id = $_GET['id'] ?? null;
        if ($emplacement_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM emplacements WHERE emplacement_id = ?");
                $stmt->execute([$emplacement_id]);
                $location = $stmt->fetch();
                if (!$location) {
                    $error_message = "Emplacement non trouvé.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la récupération de l'emplacement.";
                $action = 'list';
            }
        }
        break;
        
    case 'list':
    default:
        try {
            // Récupérer les emplacements avec statistiques
            $sql = "SELECT e.*, 
                           COUNT(DISTINCT ms.produit_id) as products_count,
                           SUM(CASE WHEN ms.statut_flux = 1 THEN ms.quantite ELSE 0 END) as total_entrees,
                           SUM(CASE WHEN ms.statut_flux = 0 THEN ms.quantite ELSE 0 END) as total_sorties,
                           (SUM(CASE WHEN ms.statut_flux = 1 THEN ms.quantite ELSE 0 END) - 
                            SUM(CASE WHEN ms.statut_flux = 0 THEN ms.quantite ELSE 0 END)) as stock_actuel
                    FROM emplacements e 
                    LEFT JOIN mouvementsstock ms ON e.emplacement_id = ms.emplacement_id 
                    GROUP BY e.emplacement_id
                    ORDER BY e.numero_emplacement ASC";
            $stmt = $pdo->query($sql);
            $locations = $stmt->fetchAll();
            
            // Statistiques générales
            $stats = [
                'total_locations' => count($locations),
                'active_locations' => 0,
                'total_products' => 0,
                'total_movements' => 0
            ];
            
            foreach ($locations as $location) {
                if ($location['products_count'] > 0) $stats['active_locations']++;
                $stats['total_products'] += $location['products_count'];
            }
            
            // Total des mouvements
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM mouvementsstock");
            $stats['total_movements'] = $stmt->fetch()['total'];
            
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la récupération des emplacements: " . $e->getMessage();
            $locations = [];
            $stats = ['total_locations' => 0, 'active_locations' => 0, 'total_products' => 0, 'total_movements' => 0];
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
            box-shadow: 0 8px 32px rgb(72, 165, 79);
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
        .btn-success { background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79)); color: white; }
        .btn-warning { background: white; color: rgb(72, 165, 79); }
        .btn-danger { background: linear-gradient(135deg,rgb(247, 15, 15),rgb(255, 0, 0)); color: white; }
        .btn-info { background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79)); color: white; }

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
            color:rgb(72, 165, 79);
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .location-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .location-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
        }

        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .location-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .location-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .location-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .location-stat-number {
            font-size: 16px;
            font-weight: 600;
            color:rgb(68, 190, 52);
        }

        .location-stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .location-movements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .movement-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .movement-row:last-child {
            margin-bottom: 0;
        }

        .movement-label {
            font-size: 12px;
            color: #666;
        }

        .movement-value {
            font-size: 12px;
            font-weight: 600;
        }

        .movement-in { color: #28a745; }
        .movement-out { color: #dc3545; }
        .movement-stock { color: #667eea; }

        .location-actions {
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
            border-color:rgb(96, 247, 108);
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
            color:rgb(255, 0, 25);
            border: 1px solidrgb(250, 45, 66);
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

            .locations-grid {
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
            <i class="fas fa-map-marker-alt"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <?php if ($action !== 'add' && $action !== 'edit'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un emplacement
                </a>
            <?php endif; ?>
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <a href="?" class="btn btn-info">
                    <i class="fas fa-list"></i> Retour à la liste
                </a>
            <?php endif; ?>
            <a href="stock-movements.php" class="btn btn-info">
                <i class="fas fa-exchange-alt"></i> Mouvements de stock
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
        <!-- Liste des emplacements -->
        <div class="content-card">
            <!-- Statistiques -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_locations']; ?></div>
                    <div class="stat-label">Total Emplacements</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['active_locations']; ?></div>
                    <div class="stat-label">Emplacements Actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Produits Stockés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_movements']; ?></div>
                    <div class="stat-label">Mouvements Total</div>
                </div>
            </div>

            <!-- Grille des emplacements -->
            <?php if (!empty($locations)): ?>
                <div class="locations-grid">
                    <?php foreach ($locations as $location): ?>
                        <div class="location-card">
                            <div class="location-header">
                                <div class="location-name">
                                    <div class="location-icon">
                                        <i class="fas fa-warehouse"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($location['numero_emplacement']); ?></span>
                                </div>
                            </div>
                            
                            <div class="location-stats">
                                <div class="location-stat">
                                    <div class="location-stat-number"><?php echo $location['products_count']; ?></div>
                                    <div class="location-stat-label">Produits</div>
                                </div>
                                <div class="location-stat">
                                    <div class="location-stat-number"><?php echo number_format($location['stock_actuel'] ?: 0, 0); ?></div>
                                    <div class="location-stat-label">Stock Net</div>
                                </div>
                            </div>
                            
                            <div class="location-movements">
                                <div class="movement-row">
                                    <span class="movement-label">Entrées:</span>
                                    <span class="movement-value movement-in">
                                        +<?php echo number_format($location['total_entrees'] ?: 0, 0); ?>
                                    </span>
                                </div>
                                <div class="movement-row">
                                    <span class="movement-label">Sorties:</span>
                                    <span class="movement-value movement-out">
                                        -<?php echo number_format($location['total_sorties'] ?: 0, 0); ?>
                                    </span>
                                </div>
                                <div class="movement-row">
                                    <span class="movement-label">Solde:</span>
                                    <span class="movement-value movement-stock">
                                        <?php echo number_format($location['stock_actuel'] ?: 0, 0); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="location-actions">
                                <a href="stock-movements.php?location=<?php echo $location['emplacement_id']; ?>" 
                                   class="btn btn-info btn-sm" title="Voir mouvements">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=edit&id=<?php echo $location['emplacement_id']; ?>" 
                                   class="btn btn-warning btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($location['products_count'] == 0): ?>
                                    <button onclick="deleteLocation(<?php echo $location['emplacement_id']; ?>)" 
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
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Aucun emplacement trouvé</h3>
                    <p>Commencez par créer votre premier emplacement de stockage.</p>
                    <a href="?action=add" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Créer le premier emplacement
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire d'ajout/modification -->
        <div class="content-card">
            <h2>
                <i class="fas fa-<?php echo ($action === 'add') ? 'plus' : 'edit'; ?>"></i>
                <?php echo ($action === 'add') ? 'Ajouter un nouvel emplacement' : 'Modifier l\'emplacement'; ?>
            </h2>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="emplacement_id" value="<?php echo $location['emplacement_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="numero_emplacement">Numéro d'emplacement *</label>
                    <input type="text" id="numero_emplacement" name="numero_emplacement" class="form-control" required
                           value="<?php echo htmlspecialchars($location['numero_emplacement'] ?? ''); ?>"
                           placeholder="Ex: A1, B2, Entrepôt-1, Zone-Nord...">
                    <small style="color: #666; margin-top: 5px; display: block;">
                        Le numéro doit être unique et identifiable (lettres, chiffres, tirets autorisés)
                    </small>
                </div>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <h4 style="color: #333; margin-bottom: 10px;">
                        <i class="fas fa-lightbulb"></i> Conseils pour nommer vos emplacements
                    </h4>
                    <ul style="color: #666; font-size: 14px; margin-left: 20px;">
                        <li><strong>Zone + Numéro:</strong> A1, B2, C3 (pour un système en grille)</li>
                        <li><strong>Descriptif:</strong> Entrepôt-Principal, Reserve-Lunettes, Vitrine-1</li>
                        <li><strong>Étage + Section:</strong> RDC-A, Etage1-B, Sous-sol-1</li>
                        <li><strong>Par type:</strong> Soleil-Stock, Medical-Reserve, Accessoires-Vitrine</li>
                    </ul>
                </div>

                <div class="form-actions">
                    <a href="?" class="btn btn-danger">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> 
                        <?php echo ($action === 'add') ? 'Créer l\'emplacement' : 'Mettre à jour'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
// Delete location
function deleteLocation(locationId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet emplacement ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="emplacement_id" value="${locationId}">
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
            const locationNumber = document.getElementById('numero_emplacement').value.trim();
            
            if (locationNumber.length < 1) {
                e.preventDefault();
                alert('Le numéro d\'emplacement ne peut pas être vide.');
                return;
            }
            
            if (locationNumber.length > 50) {
                e.preventDefault();
                alert('Le numéro d\'emplacement ne peut pas dépasser 50 caractères.');
                return;
            }
            
            // Validate format (letters, numbers, hyphens, underscores)
            if (!/^[a-zA-Z0-9\-_]+$/.test(locationNumber)) {
                if (!confirm('Le numéro contient des caractères spéciaux. Seuls les lettres, chiffres, tirets et underscores sont recommandés. Continuer ?')) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
        });
    }
});

// Add smooth animations to location cards
document.querySelectorAll('.location-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

console.log('OpticLook Locations Management loaded successfully');
</script>

</body>
</html>