<?php
// admin/clients.php - View Only Version
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'clients';
$page_title = 'Liste des Clients';
$error_message = '';
$success_message = '';

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.nom_complet LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM clients c $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_clients = $stmt->fetch()['total'];
    
    // Récupérer les clients avec leurs statistiques
    $sql = "SELECT c.*, 
                   COUNT(p.client_id) as total_rdv,
                   SUM(CASE WHEN p.STATUS_RENDEZ_VOUS = 1 THEN pr.prix ELSE 0 END) as total_depense,
                   MAX(p.DATE_RENDEZ_VOUS) as dernier_rdv
            FROM clients c 
            LEFT JOIN prendre p ON c.client_id = p.client_id 
            LEFT JOIN produits pr ON p.produit_id = pr.produit_id
            $where_clause 
            GROUP BY c.client_id
            ORDER BY c.client_id DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
    $total_pages = ceil($total_clients / $per_page);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des clients: " . $e->getMessage();
    $clients = [];
    $total_clients = 0;
    $total_pages = 1;
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

        .btn-primary {
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-info { background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79)); color: white; }
        .btn-warning { background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79)); color: #333; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgb(72, 165, 79);
            border: 1px solid rgb(64, 121, 69);
            margin-bottom: 20px;
        }

        .filters-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .search-box {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            min-width: 300px;
        }

        .search-box:focus {
            outline: none;
            border-color:rgb(65, 189, 113);
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

        .client-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

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
            background: linear-gradient(135deg,rgb(90, 185, 95),rgb(72, 165, 79));
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

        .alert-error {
            background: #f8d7da;
            color:rgb(255, 0, 0);
            border: 1px solid rgb(255, 62, 62);
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
            color:rgb(0, 0, 0);
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .client-details-link {
            color:rgb(0, 0, 0);
            text-decoration: none;
            font-weight: 600;
        }

        .client-details-link:hover {
            text-decoration: underline;
        }

        .text-muted {
            color: #666;
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
            <i class="fas fa-users"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
            <a href="dashboard.php" class="btn btn-info">
                <i class="fas fa-tachometer-alt"></i> Retour au Dashboard
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

    <!-- Liste des clients -->
    <div class="content-card">
        <!-- Statistiques -->
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_clients; ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $active_clients = 0;
                    foreach ($clients as $client) {
                        if ($client['total_rdv'] > 0) $active_clients++;
                    }
                    echo $active_clients;
                    ?>
                </div>
                <div class="stat-label">Clients Actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $total_revenue = 0;
                    foreach ($clients as $client) {
                        $total_revenue += $client['total_depense'] ?? 0;
                    }
                    echo number_format($total_revenue, 0);
                    ?> DH
                </div>
                <div class="stat-label">Chiffre d'Affaires</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $new_clients = count(array_filter($clients, function($client) {
                        return empty($client['dernier_rdv']);
                    }));
                    echo $new_clients;
                    ?>
                </div>
                <div class="stat-label">Nouveaux Clients</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="filters-section">
            <input type="text" name="search" class="search-box" placeholder="Rechercher par nom ou email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Rechercher
            </button>
            
            <a href="?" class="btn btn-warning">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <!-- Tableau des clients -->
        <?php if (!empty($clients)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>RDV</th>
                        <th>Dépenses</th>
                        <th>Dernier RDV</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="client-avatar">
                                        <?php echo strtoupper(substr($client['nom_complet'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($client['nom_complet']); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $client['client_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="client-details-link">
                                    <?php echo htmlspecialchars($client['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($client['numero_telephone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($client['numero_telephone']); ?>" class="client-details-link">
                                        <?php echo htmlspecialchars($client['numero_telephone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Non renseigné</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $client['total_rdv'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $client['total_rdv']; ?> RDV
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format($client['total_depense'] ?: 0, 2); ?> DH</strong>
                            </td>
                            <td>
                                <?php if ($client['dernier_rdv']): ?>
                                    <?php echo date('d/m/Y', strtotime($client['dernier_rdv'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['total_rdv'] > 0): ?>
                                    <span class="status-badge status-active">Actif</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Nouveau</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                <h3>Aucun client trouvé</h3>
                <p>Aucun client ne correspond à vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Auto-hide alerts after 5 seconds
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

// Add smooth transitions to table rows
document.querySelectorAll('.data-table tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.01)';
        this.style.boxShadow = '0 4px 15px rgba(102, 126, 234, 0.1)';
    });
    
    row.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = 'none';
    });
});

// Enhanced search functionality
const searchBox = document.querySelector('.search-box');
if (searchBox) {
    searchBox.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.closest('form').submit();
        }
    });
}

// Add loading state to search button
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recherche...';
});

console.log('OpticLook Clients Page - View Only Mode loaded successfully');
</script>

</body>
</html>