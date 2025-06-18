<?php
// admin_extended.php - Additional admin features
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

// Gestion des requêtes AJAX pour les nouvelles fonctionnalités
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_sales_report':
            try {
                $period = $_GET['period'] ?? 'month';
                $sql = "";
                
                switch ($period) {
                    case 'week':
                        $sql = "SELECT 
                                    DATE(p.DATE_RENDEZ_VOUS) as date,
                                    COUNT(*) as appointments,
                                    SUM(pr.prix) as revenue
                                FROM prendre p 
                                JOIN produits pr ON p.produit_id = pr.produit_id 
                                WHERE p.STATUS_RENDEZ_VOUS = 1 
                                AND p.DATE_RENDEZ_VOUS >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                GROUP BY DATE(p.DATE_RENDEZ_VOUS)
                                ORDER BY date DESC";
                        break;
                    case 'month':
                        $sql = "SELECT 
                                    DATE_FORMAT(p.DATE_RENDEZ_VOUS, '%Y-%m-%d') as date,
                                    COUNT(*) as appointments,
                                    SUM(pr.prix) as revenue
                                FROM prendre p 
                                JOIN produits pr ON p.produit_id = pr.produit_id 
                                WHERE p.STATUS_RENDEZ_VOUS = 1 
                                AND p.DATE_RENDEZ_VOUS >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                GROUP BY DATE(p.DATE_RENDEZ_VOUS)
                                ORDER BY date DESC";
                        break;
                    case 'year':
                        $sql = "SELECT 
                                    DATE_FORMAT(p.DATE_RENDEZ_VOUS, '%Y-%m') as date,
                                    COUNT(*) as appointments,
                                    SUM(pr.prix) as revenue
                                FROM prendre p 
                                JOIN produits pr ON p.produit_id = pr.produit_id 
                                WHERE p.STATUS_RENDEZ_VOUS = 1 
                                AND p.DATE_RENDEZ_VOUS >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                                GROUP BY DATE_FORMAT(p.DATE_RENDEZ_VOUS, '%Y-%m')
                                ORDER BY date DESC";
                        break;
                }
                
                $stmt = $pdo->query($sql);
                $sales = $stmt->fetchAll();
                echo json_encode($sales);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_top_products':
            try {
                $sql = "SELECT 
                            p.nom_produit,
                            p.prix,
                            p.url_image,
                            c.nom_categorie,
                            COUNT(pr.produit_id) as total_appointments,
                            SUM(p.prix) as total_revenue
                        FROM produits p 
                        LEFT JOIN prendre pr ON p.produit_id = pr.produit_id AND pr.STATUS_RENDEZ_VOUS = 1
                        JOIN categories c ON p.categorie_id = c.categorie_id
                        GROUP BY p.produit_id 
                        ORDER BY total_appointments DESC 
                        LIMIT 10";
                $stmt = $pdo->query($sql);
                $products = $stmt->fetchAll();
                echo json_encode($products);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_inventory_alerts':
            try {
                $sql = "SELECT 
                            p.produit_id,
                            p.nom_produit,
                            p.quantite_stock,
                            p.prix,
                            c.nom_categorie,
                            p.url_image
                        FROM produits p 
                        JOIN categories c ON p.categorie_id = c.categorie_id
                        WHERE p.quantite_stock < 10 
                        ORDER BY p.quantite_stock ASC";
                $stmt = $pdo->query($sql);
                $alerts = $stmt->fetchAll();
                echo json_encode($alerts);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_recent_activities':
            try {
                $sql = "SELECT 
                            'appointment' as type,
                            c.nom_complet as client_name,
                            p.nom_produit as product_name,
                            pr.DATE_RENDEZ_VOUS as date,
                            pr.STATUS_RENDEZ_VOUS as status
                        FROM prendre pr 
                        JOIN clients c ON pr.client_id = c.client_id 
                        JOIN produits p ON pr.produit_id = p.produit_id
                        WHERE pr.DATE_RENDEZ_VOUS >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ORDER BY pr.DATE_RENDEZ_VOUS DESC 
                        LIMIT 20";
                $stmt = $pdo->query($sql);
                $activities = $stmt->fetchAll();
                echo json_encode($activities);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_client_analytics':
            try {
                $sql = "SELECT 
                            c.client_id,
                            c.nom_complet,
                            c.email,
                            COUNT(p.client_id) as total_appointments,
                            SUM(CASE WHEN p.STATUS_RENDEZ_VOUS = 1 THEN pr.prix ELSE 0 END) as total_spent,
                            MAX(p.DATE_RENDEZ_VOUS) as last_appointment
                        FROM clients c 
                        LEFT JOIN prendre p ON c.client_id = p.client_id 
                        LEFT JOIN produits pr ON p.produit_id = pr.produit_id
                        GROUP BY c.client_id 
                        HAVING total_appointments > 0
                        ORDER BY total_spent DESC 
                        LIMIT 20";
                $stmt = $pdo->query($sql);
                $analytics = $stmt->fetchAll();
                echo json_encode($analytics);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'export_data':
            try {
                $type = $_GET['type'] ?? 'products';
                $filename = '';
                $data = [];
                
                switch ($type) {
                    case 'products':
                        $sql = "SELECT p.*, c.nom_categorie FROM produits p JOIN categories c ON p.categorie_id = c.categorie_id";
                        $stmt = $pdo->query($sql);
                        $data = $stmt->fetchAll();
                        $filename = 'produits_' . date('Y-m-d') . '.csv';
                        break;
                    case 'clients':
                        $sql = "SELECT * FROM clients";
                        $stmt = $pdo->query($sql);
                        $data = $stmt->fetchAll();
                        $filename = 'clients_' . date('Y-m-d') . '.csv';
                        break;
                    case 'appointments':
                        $sql = "SELECT p.*, c.nom_complet, pr.nom_produit 
                                FROM prendre p 
                                JOIN clients c ON p.client_id = c.client_id 
                                JOIN produits pr ON p.produit_id = pr.produit_id";
                        $stmt = $pdo->query($sql);
                        $data = $stmt->fetchAll();
                        $filename = 'rendez_vous_' . date('Y-m-d') . '.csv';
                        break;
                }
                
                // Generate CSV
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');
                
                $output = fopen('php://output', 'w');
                
                if (!empty($data)) {
                    // Header row
                    fputcsv($output, array_keys($data[0]));
                    
                    // Data rows
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                }
                
                fclose($output);
                exit;
                
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'backup_database':
            try {
                // Simple backup functionality
                $backup_file = 'backup_opti_db_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = '../backups/' . $backup_file;
                
                // Create backups directory if it doesn't exist
                if (!file_exists('../backups')) {
                    mkdir('../backups', 0755, true);
                }
                
                // Get all tables
                $tables = [];
                $result = $pdo->query("SHOW TABLES");
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                
                $backup_content = "-- OpticLook Database Backup\n";
                $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
                
                foreach ($tables as $table) {
                    // Get table structure
                    $result = $pdo->query("SHOW CREATE TABLE `$table`");
                    $row = $result->fetch(PDO::FETCH_NUM);
                    $backup_content .= "\n\n-- Table structure for `$table`\n";
                    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                    $backup_content .= $row[1] . ";\n\n";
                    
                    // Get table data
                    $result = $pdo->query("SELECT * FROM `$table`");
                    $num_fields = $result->columnCount();
                    
                    if ($result->rowCount() > 0) {
                        $backup_content .= "-- Data for table `$table`\n";
                        while ($row = $result->fetch(PDO::FETCH_NUM)) {
                            $backup_content .= "INSERT INTO `$table` VALUES(";
                            for ($j = 0; $j < $num_fields; $j++) {
                                $row[$j] = addslashes($row[$j]);
                                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                                if (isset($row[$j])) {
                                    $backup_content .= '"' . $row[$j] . '"';
                                } else {
                                    $backup_content .= '""';
                                }
                                if ($j < ($num_fields - 1)) {
                                    $backup_content .= ',';
                                }
                            }
                            $backup_content .= ");\n";
                        }
                    }
                }
                
                file_put_contents($backup_path, $backup_content);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Sauvegarde créée avec succès',
                    'filename' => $backup_file
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Gestion des requêtes POST pour les nouvelles fonctionnalités
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'bulk_update_stock':
            try {
                $updates = json_decode($_POST['updates'], true);
                $updated_count = 0;
                
                foreach ($updates as $update) {
                    $sql = "UPDATE produits SET quantite_stock = ? WHERE produit_id = ?";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$update['stock'], $update['id']])) {
                        $updated_count++;
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => "$updated_count produits mis à jour avec succès"
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_notification':
            try {
                $client_id = $_POST['client_id'];
                $subject = $_POST['subject'];
                $message = $_POST['message'];
                
                // Get client email
                $stmt = $pdo->prepare("SELECT email, nom_complet FROM clients WHERE client_id = ?");
                $stmt->execute([$client_id]);
                $client = $stmt->fetch();
                
                if ($client) {
                    // Here you would integrate with your email system
                    // For now, we'll just simulate sending
                    
                    // Log the notification
                    $log_sql = "INSERT INTO notifications (client_id, subject, message, sent_date) VALUES (?, ?, ?, NOW())";
                    // Note: You would need to create a notifications table for this
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "Notification envoyée à {$client['nom_complet']}"
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
                }
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            exit;
            
        case 'generate_report':
            try {
                $report_type = $_POST['report_type'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $sql = "";
                $params = [$start_date, $end_date];
                
                switch ($report_type) {
                    case 'sales':
                        $sql = "SELECT 
                                    DATE(p.DATE_RENDEZ_VOUS) as date,
                                    COUNT(*) as total_appointments,
                                    SUM(pr.prix) as total_revenue,
                                    AVG(pr.prix) as avg_order_value
                                FROM prendre p 
                                JOIN produits pr ON p.produit_id = pr.produit_id 
                                WHERE p.STATUS_RENDEZ_VOUS = 1 
                                AND DATE(p.DATE_RENDEZ_VOUS) BETWEEN ? AND ?
                                GROUP BY DATE(p.DATE_RENDEZ_VOUS)
                                ORDER BY date";
                        break;
                    case 'products':
                        $sql = "SELECT 
                                    pr.nom_produit,
                                    c.nom_categorie,
                                    COUNT(p.produit_id) as total_sold,
                                    SUM(pr.prix) as revenue,
                                    pr.quantite_stock as current_stock
                                FROM produits pr 
                                LEFT JOIN prendre p ON pr.produit_id = p.produit_id 
                                    AND p.STATUS_RENDEZ_VOUS = 1 
                                    AND DATE(p.DATE_RENDEZ_VOUS) BETWEEN ? AND ?
                                JOIN categories c ON pr.categorie_id = c.categorie_id
                                GROUP BY pr.produit_id 
                                ORDER BY total_sold DESC";
                        break;
                    case 'clients':
                        $sql = "SELECT 
                                    c.nom_complet,
                                    c.email,
                                    COUNT(p.client_id) as total_appointments,
                                    SUM(CASE WHEN p.STATUS_RENDEZ_VOUS = 1 THEN pr.prix ELSE 0 END) as total_spent,
                                    MAX(p.DATE_RENDEZ_VOUS) as last_visit
                                FROM clients c 
                                LEFT JOIN prendre p ON c.client_id = p.client_id 
                                    AND DATE(p.DATE_RENDEZ_VOUS) BETWEEN ? AND ?
                                LEFT JOIN produits pr ON p.produit_id = pr.produit_id
                                GROUP BY c.client_id 
                                HAVING total_appointments > 0
                                ORDER BY total_spent DESC";
                        break;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $report_data = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true, 
                    'data' => $report_data,
                    'report_type' => $report_type,
                    'period' => "$start_date to $end_date"
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpticLook - Dashboard Avancé</title>
    <style>
        /* Previous styles would be included here */
        /* Adding new styles for extended features */
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .analytics-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .chart-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-style: italic;
            margin: 15px 0;
        }
        
        .top-products-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s ease;
        }
        
        .product-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .product-item img {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .product-category {
            font-size: 12px;
            color: #666;
        }
        
        .product-stats {
            text-align: right;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-left: 4px solid #667eea;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 0 8px 8px 0;
            margin-bottom: 10px;
        }
        
        .activity-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 14px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .activity-desc {
            font-size: 12px;
            color: #666;
        }
        
        .alert-item {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .alert-icon {
            color: #856404;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 2px;
        }
        
        .alert-desc {
            font-size: 12px;
            color: #856404;
        }
        
        .report-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 20px;
        }
        
        .report-results {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .bulk-actions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 20px;
        }
        
        .client-analytics-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s ease;
        }
        
        .client-analytics-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .client-name {
            font-weight: 600;
        }
        
        .client-email {
            font-size: 12px;
            color: #666;
        }
        
        .client-stats {
            text-align: right;
        }
        
        .stat-value {
            font-weight: 600;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Extended Analytics Section -->
    <section class="content-section" id="analytics">
        <div class="analytics-grid">
            <!-- Sales Chart -->
            <div class="analytics-card">
                <h3>📈 Évolution des Ventes</h3>
                <select id="salesPeriod" class="form-control" style="width: auto; margin-bottom: 15px;">
                    <option value="week">Cette semaine</option>
                    <option value="month" selected>Ce mois</option>
                    <option value="year">Cette année</option>
                </select>
                <div class="chart-placeholder" id="salesChart">
                    Graphique des ventes
                </div>
            </div>
            
            <!-- Top Products -->
            <div class="analytics-card">
                <h3>🏆 Produits Populaires</h3>
                <div class="top-products-list" id="topProductsList">
                    <!-- Top products will be loaded here -->
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="analytics-card">
                <h3>🔄 Activités Récentes</h3>
                <div id="recentActivities">
                    <!-- Recent activities will be loaded here -->
                </div>
            </div>
            
            <!-- Inventory Alerts -->
            <div class="analytics-card">
                <h3>⚠️ Alertes Stock</h3>
                <div id="inventoryAlerts">
                    <!-- Inventory alerts will be loaded here -->
                </div>
            </div>
        </div>
        
        <!-- Client Analytics -->
        <div class="analytics-card">
            <h3>👥 Analyse des Clients</h3>
            <div id="clientAnalytics">
                <!-- Client analytics will be loaded here -->
            </div>
        </div>
    </section>
    
    <!-- Reports Section -->
    <section class="content-section" id="reports">
        <div class="report-form">
            <h2 class="table-title">📊 Génération de Rapports</h2>
            <form id="reportForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="reportType">Type de rapport</label>
                        <select class="form-control" id="reportType" required>
                            <option value="">Sélectionner le type</option>
                            <option value="sales">Rapport des Ventes</option>
                            <option value="products">Rapport des Produits</option>
                            <option value="clients">Rapport des Clients</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Date de début</label>
                        <input type="date" class="form-control" id="startDate" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">Date de fin</label>
                        <input type="date" class="form-control" id="endDate" required>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Générer le Rapport</button>
                    </div>
                </div>
            </form>
            
            <div class="report-results" id="reportResults" style="display: none;">
                <!-- Report results will be displayed here -->
            </div>
        </div>
    </section>
    
    <!-- System Management Section -->
    <section class="content-section" id="system">
        <div class="export-buttons">
            <button class="btn btn-info" onclick="exportData('products')">
                📥 Exporter Produits
            </button>
            <button class="btn btn-info" onclick="exportData('clients')">
                📥 Exporter Clients
            </button>
            <button class="btn btn-info" onclick="exportData('appointments')">
                📥 Exporter RDV
            </button>
            <button class="btn btn-success" onclick="backupDatabase()">
                💾 Sauvegarder BDD
            </button>
        </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <h3>⚡ Actions en Lot</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="bulkAction">Action</label>
                    <select class="form-control" id="bulkAction">
                        <option value="">Sélectionner une action</option>
                        <option value="update_stock">Mettre à jour le stock</option>
                        <option value="apply_discount">Appliquer une remise</option>
                        <option value="change_category">Changer la catégorie</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bulkValue">Valeur</label>
                    <input type="text" class="form-control" id="bulkValue" placeholder="Nouvelle valeur">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-warning" onclick="executeBulkAction()">
                        Exécuter l'Action
                    </button>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="table-container">
            <h3>ℹ️ Informations Système</h3>
            <table class="data-table">
                <tr>
                    <td><strong>Version PHP:</strong></td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Version MySQL:</strong></td>
                    <td><?php echo $pdo->query('SELECT VERSION()')->fetchColumn(); ?></td>
                </tr>
                <tr>
                    <td><strong>Espace disque utilisé:</strong></td>
                    <td><?php echo round(disk_total_space('.') / 1024 / 1024 / 1024, 2); ?> GB</td>
                </tr>
                <tr>
                    <td><strong>Mémoire PHP:</strong></td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                </tr>
                <tr>
                    <td><strong>Dernière sauvegarde:</strong></td>
                    <td id="lastBackup">Aucune sauvegarde trouvée</td>
                </tr>
            </table>
        </div>
    </section>

    <!-- Notifications Section -->
    <section class="content-section" id="notifications">
        <div class="form-container">
            <h2 class="table-title">📧 Envoyer une Notification</h2>
            <form id="notificationForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="notificationClient">Client</label>
                        <select class="form-control" id="notificationClient" required>
                            <option value="">Sélectionner un client</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notificationSubject">Sujet</label>
                        <input type="text" class="form-control" id="notificationSubject" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notificationMessage">Message</label>
                    <textarea class="form-control" id="notificationMessage" rows="5" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Envoyer la Notification</button>
                </div>
            </form>
        </div>

        <!-- Quick Templates -->
        <div class="table-container">
            <h3>📋 Modèles Rapides</h3>
            <div class="template-buttons">
                <button class="btn btn-info btn-sm" onclick="useTemplate('appointment_reminder')">
                    Rappel RDV
                </button>
                <button class="btn btn-info btn-sm" onclick="useTemplate('promotion')">
                    Promotion
                </button>
                <button class="btn btn-info btn-sm" onclick="useTemplate('welcome')">
                    Bienvenue
                </button>
                <button class="btn btn-info btn-sm" onclick="useTemplate('follow_up')">
                    Suivi
                </button>
            </div>
        </div>
    </section>

    <script>
        // Extended JavaScript functionality
        
        // Load analytics data
        function loadAnalytics() {
            loadSalesChart();
            loadTopProducts();
            loadRecentActivities();
            loadInventoryAlerts();
            loadClientAnalytics();
        }

        function loadSalesChart() {
            const period = document.getElementById('salesPeriod').value;
            
            fetch(`?action=get_sales_report&period=${period}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Erreur: ' + data.error, 'error');
                        return;
                    }
                    
                    // Simple chart representation
                    const chartContainer = document.getElementById('salesChart');
                    if (data.length === 0) {
                        chartContainer.innerHTML = 'Aucune donnée disponible';
                        return;
                    }
                    
                    let chartHTML = '<div style="display: flex; align-items: end; height: 150px; gap: 5px;">';
                    const maxRevenue = Math.max(...data.map(d => parseFloat(d.revenue || 0)));
                    
                    data.slice(0, 7).forEach(item => {
                        const height = maxRevenue > 0 ? (item.revenue / maxRevenue * 120) : 0;
                        chartHTML += `
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="width: 20px; background: linear-gradient(135deg, #667eea, #764ba2); height: ${height}px; border-radius: 2px; margin-bottom: 5px;" title="${item.revenue} DH"></div>
                                <small style="font-size: 10px; transform: rotate(-45deg);">${new Date(item.date).toLocaleDateString('fr-FR', {month: 'short', day: 'numeric'})}</small>
                            </div>
                        `;
                    });
                    chartHTML += '</div>';
                    
                    chartContainer.innerHTML = chartHTML;
                })
                .catch(error => {
                    console.error('Error loading sales chart:', error);
                });
        }

        function loadTopProducts() {
            fetch('?action=get_top_products')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Erreur: ' + data.error, 'error');
                        return;
                    }
                    
                    const container = document.getElementById('topProductsList');
                    container.innerHTML = '';
                    
                    data.slice(0, 5).forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'product-item';
                        item.innerHTML = `
                            <img src="${product.url_image || 'placeholder.jpg'}" alt="${product.nom_produit}" 
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRkZGIiBzdHJva2U9IiNEREQiLz4KPHN2ZyB4PSIxMiIgeT0iMTIiIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM5OTkiPgo8cGF0aCBkPSJtMTUgMTggNi02LTYtNi02IDYgNiA2eiIvPgo8L3N2Zz4KPC9zdmc+'">
                            <div class="product-info">
                                <div class="product-name">${product.nom_produit}</div>
                                <div class="product-category">${product.nom_categorie}</div>
                            </div>
                            <div class="product-stats">
                                <div class="stat-value">${product.total_appointments || 0}</div>
                                <div class="stat-label">RDV</div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error loading top products:', error);
                });
        }

        function loadRecentActivities() {
            fetch('?action=get_recent_activities')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Erreur: ' + data.error, 'error');
                        return;
                    }
                    
                    const container = document.getElementById('recentActivities');
                    container.innerHTML = '';
                    
                    data.slice(0, 5).forEach(activity => {
                        const item = document.createElement('div');
                        item.className = 'activity-item';
                        
                        const statusText = getStatusText(activity.status);
                        const icon = activity.type === 'appointment' ? '📅' : '📦';
                        
                        item.innerHTML = `
                            <div class="activity-icon">${icon}</div>
                            <div class="activity-content">
                                <div class="activity-title">RDV - ${activity.client_name}</div>
                                <div class="activity-desc">${activity.product_name} - ${statusText}</div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error loading recent activities:', error);
                });
        }

        function loadInventoryAlerts() {
            fetch('?action=get_inventory_alerts')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Erreur: ' + data.error, 'error');
                        return;
                    }
                    
                    const container = document.getElementById('inventoryAlerts');
                    container.innerHTML = '';
                    
                    if (data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: #28a745;">✅ Aucune alerte de stock</p>';
                        return;
                    }
                    
                    data.slice(0, 5).forEach(alert => {
                        const item = document.createElement('div');
                        item.className = 'alert-item';
                        item.innerHTML = `
                            <div class="alert-icon">⚠️</div>
                            <div class="alert-content">
                                <div class="alert-title">${alert.nom_produit}</div>
                                <div class="alert-desc">Stock: ${alert.quantite_stock} unités</div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error loading inventory alerts:', error);
                });
        }

        function loadClientAnalytics() {
            fetch('?action=get_client_analytics')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Erreur: ' + data.error, 'error');
                        return;
                    }
                    
                    const container = document.getElementById('clientAnalytics');
                    container.innerHTML = '';
                    
                    data.slice(0, 10).forEach(client => {
                        const item = document.createElement('div');
                        item.className = 'client-analytics-item';
                        item.innerHTML = `
                            <div>
                                <div class="client-name">${client.nom_complet}</div>
                                <div class="client-email">${client.email}</div>
                            </div>
                            <div class="client-stats">
                                <div class="stat-value">${client.total_spent || 0} DH</div>
                                <div class="stat-label">${client.total_appointments} RDV</div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error loading client analytics:', error);
                });
        }

        // Report generation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'generate_report');
            formData.append('report_type', document.getElementById('reportType').value);
            formData.append('start_date', document.getElementById('startDate').value);
            formData.append('end_date', document.getElementById('endDate').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReport(data);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Erreur lors de la génération du rapport', 'error');
                console.error('Error:', error);
            });
        });

        function displayReport(reportData) {
            const container = document.getElementById('reportResults');
            container.style.display = 'block';
            
            let html = `<h4>📊 Rapport ${reportData.report_type} - ${reportData.period}</h4>`;
            
            if (reportData.data.length === 0) {
                html += '<p>Aucune donnée trouvée pour cette période.</p>';
            } else {
                html += '<table class="data-table"><thead><tr>';
                
                // Generate table headers
                Object.keys(reportData.data[0]).forEach(key => {
                    html += `<th>${key}</th>`;
                });
                html += '</tr></thead><tbody>';
                
                // Generate table rows
                reportData.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += `<td>${value || 'N/A'}</td>`;
                    });
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }
            
            container.innerHTML = html;
        }

        // Export functions
        function exportData(type) {
            window.open(`?action=export_data&type=${type}`, '_blank');
            showNotification(`Export ${type} lancé`, 'info');
        }

        function backupDatabase() {
            if (confirm('Êtes-vous sûr de vouloir créer une sauvegarde de la base de données ?')) {
                fetch('?action=backup_database')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            document.getElementById('lastBackup').textContent = new Date().toLocaleString('fr-FR');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Erreur lors de la sauvegarde', 'error');
                        console.error('Error:', error);
                    });
            }
        }

        // Bulk actions
        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const value = document.getElementById('bulkValue').value;
            
            if (!action || !value) {
                showNotification('Veuillez sélectionner une action et saisir une valeur', 'error');
                return;
            }
            
            if (action === 'update_stock') {
                // Example bulk stock update
                const updates = [
                    {id: 1, stock: value},
                    {id: 2, stock: value}
                    // Add more products as needed
                ];
                
                const formData = new FormData();
                formData.append('action', 'bulk_update_stock');
                formData.append('updates', JSON.stringify(updates));
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur lors de l\'action en lot', 'error');
                    console.error('Error:', error);
                });
            } else {
                showNotification('Action non implémentée', 'info');
            }
        }

        // Notification system
        function loadNotificationClients() {
            fetch('?action=get_clients')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('notificationClient');
                    select.innerHTML = '<option value="">Sélectionner un client</option>';
                    
                    data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.client_id;
                        option.textContent = `${client.nom_complet} (${client.email})`;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading clients:', error);
                });
        }

        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'send_notification');
            formData.append('client_id', document.getElementById('notificationClient').value);
            formData.append('subject', document.getElementById('notificationSubject').value);
            formData.append('message', document.getElementById('notificationMessage').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    this.reset();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Erreur lors de l\'envoi', 'error');
                console.error('Error:', error);
            });
        });

        // Message templates
        function useTemplate(templateType) {
            const templates = {
                appointment_reminder: {
                    subject: 'Rappel de votre rendez-vous - OpticLook',
                    message: 'Bonjour,\n\nNous vous rappelons votre rendez-vous prévu demain à OpticLook.\n\nÀ bientôt,\nL\'équipe OpticLook'
                },
                promotion: {
                    subject: 'Offre spéciale - OpticLook',
                    message: 'Bonjour,\n\nProfitez de notre offre spéciale : -20% sur toutes nos lunettes de soleil !\n\nÀ bientôt,\nL\'équipe OpticLook'
                },
                welcome: {
                    subject: 'Bienvenue chez OpticLook',
                    message: 'Bonjour,\n\nBienvenue chez OpticLook ! Nous sommes ravis de vous compter parmi nos clients.\n\nÀ bientôt,\nL\'équipe OpticLook'
                },
                follow_up: {
                    subject: 'Suivi de votre visite - OpticLook',
                    message: 'Bonjour,\n\nNous espérons que vous êtes satisfait(e) de votre récent achat. N\'hésitez pas à nous contacter si vous avez des questions.\n\nÀ bientôt,\nL\'équipe OpticLook'
                }
            };
            
            const template = templates[templateType];
            if (template) {
                document.getElementById('notificationSubject').value = template.subject;
                document.getElementById('notificationMessage').value = template.message;
            }
        }

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            const activeSection = document.querySelector('.content-section.active');
            if (activeSection && activeSection.id === 'analytics') {
                loadAnalytics();
            }
        }, 30000);

        // Sales period change handler
        document.getElementById('salesPeriod').addEventListener('change', loadSalesChart);

        // Initialize extended features when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add new navigation items to the existing sidebar
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu) {
                const analyticsNav = document.createElement('li');
                analyticsNav.className = 'nav-item';
                analyticsNav.innerHTML = `
                    <a href="#" class="nav-link" data-section="analytics">
                        <i>📊</i> Analytics
                    </a>
                `;
                
                const reportsNav = document.createElement('li');
                reportsNav.className = 'nav-item';
                reportsNav.innerHTML = `
                    <a href="#" class="nav-link" data-section="reports">
                        <i>📈</i> Rapports
                    </a>
                `;
                
                const systemNav = document.createElement('li');
                systemNav.className = 'nav-item';
                systemNav.innerHTML = `
                    <a href="#" class="nav-link" data-section="system">
                        <i>🔧</i> Système
                    </a>
                `;
                
                const notificationsNav = document.createElement('li');
                notificationsNav.className = 'nav-item';
                notificationsNav.innerHTML = `
                    <a href="#" class="nav-link" data-section="notifications">
                        <i>📧</i> Notifications
                    </a>
                `;
                
                // Insert before profile section
                const profileNav = navMenu.querySelector('[data-section="profile"]').parentElement;
                navMenu.insertBefore(analyticsNav, profileNav);
                navMenu.insertBefore(reportsNav, profileNav);
                navMenu.insertBefore(systemNav, profileNav);
                navMenu.insertBefore(notificationsNav, profileNav);
            }
            
            // Load notification clients
            loadNotificationClients();
            
            // Set default dates for reports
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            document.getElementById('startDate').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
        });
    </script>
</body>
</html>