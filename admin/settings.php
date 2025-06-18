<?php
// admin/settings.php
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../connexion.php");
    exit();
}

$current_page = 'settings';
$page_title = 'Paramètres du Système';
$error_message = '';
$success_message = '';

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'general';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'general':
            $shop_name = trim($_POST['shop_name']);
            $shop_address = trim($_POST['shop_address']);
            $shop_phone = trim($_POST['shop_phone']);
            $shop_email = trim($_POST['shop_email']);
            $shop_description = trim($_POST['shop_description']);
            $timezone = $_POST['timezone'];
            $currency = $_POST['currency'];
            $language = $_POST['language'];
            
            // Validation
            if (empty($shop_name) || empty($shop_email)) {
                $error_message = "Le nom du magasin et l'email sont obligatoires.";
            } elseif (!filter_var($shop_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Format d'email invalide.";
            } else {
                // Ici vous pourriez sauvegarder dans une table de configuration
                // Pour cette démo, on simule la sauvegarde
                $success_message = "Paramètres généraux mis à jour avec succès.";
            }
            break;
            
        case 'stock':
            $low_stock_threshold = (int)$_POST['low_stock_threshold'];
            $auto_reorder = isset($_POST['auto_reorder']) ? 1 : 0;
            $reorder_quantity = (int)$_POST['reorder_quantity'];
            $stock_notifications = isset($_POST['stock_notifications']) ? 1 : 0;
            $track_movements = isset($_POST['track_movements']) ? 1 : 0;
            
            if ($low_stock_threshold < 1 || $low_stock_threshold > 100) {
                $error_message = "Le seuil de stock faible doit être entre 1 et 100.";
            } else {
                $success_message = "Paramètres de stock mis à jour avec succès.";
            }
            break;
            
        case 'appointments':
            $appointment_duration = (int)$_POST['appointment_duration'];
            $advance_booking_days = (int)$_POST['advance_booking_days'];
            $max_daily_appointments = (int)$_POST['max_daily_appointments'];
            $auto_confirm = isset($_POST['auto_confirm']) ? 1 : 0;
            $send_reminders = isset($_POST['send_reminders']) ? 1 : 0;
            $reminder_hours = (int)$_POST['reminder_hours'];
            
            if ($appointment_duration < 15 || $appointment_duration > 180) {
                $error_message = "La durée des rendez-vous doit être entre 15 et 180 minutes.";
            } else {
                $success_message = "Paramètres des rendez-vous mis à jour avec succès.";
            }
            break;
            
        case 'notifications':
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
            $new_appointment_alert = isset($_POST['new_appointment_alert']) ? 1 : 0;
            $low_stock_alert = isset($_POST['low_stock_alert']) ? 1 : 0;
            $daily_report = isset($_POST['daily_report']) ? 1 : 0;
            $admin_email = trim($_POST['admin_email']);
            
            if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Format d'email administrateur invalide.";
            } else {
                $success_message = "Paramètres de notifications mis à jour avec succès.";
            }
            break;
            
        case 'security':
            $session_timeout = (int)$_POST['session_timeout'];
            $max_login_attempts = (int)$_POST['max_login_attempts'];
            $lockout_duration = (int)$_POST['lockout_duration'];
            $password_expiry = (int)$_POST['password_expiry'];
            $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
            $login_notifications = isset($_POST['login_notifications']) ? 1 : 0;
            
            if ($session_timeout < 5 || $session_timeout > 1440) {
                $error_message = "Le timeout de session doit être entre 5 et 1440 minutes.";
            } else {
                $success_message = "Paramètres de sécurité mis à jour avec succès.";
            }
            break;
            
        case 'backup':
            $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
            $backup_frequency = $_POST['backup_frequency'];
            $backup_retention = (int)$_POST['backup_retention'];
            $backup_email = trim($_POST['backup_email']);
            
            if ($auto_backup && empty($backup_email)) {
                $error_message = "L'email de sauvegarde est requis si la sauvegarde automatique est activée.";
            } else {
                $success_message = "Paramètres de sauvegarde mis à jour avec succès.";
            }
            break;
    }
}

// Récupérer les paramètres actuels (simulation)
$settings = [
    'general' => [
        'shop_name' => 'OpticLook',
        'shop_address' => '123 Rue de la Vision, Casablanca, Maroc',
        'shop_phone' => '+212 5XX XX XX XX',
        'shop_email' => 'contact@opticlook.ma',
        'shop_description' => 'Votre spécialiste en optique et lunetterie',
        'timezone' => 'Africa/Casablanca',
        'currency' => 'MAD',
        'language' => 'fr'
    ],
    'stock' => [
        'low_stock_threshold' => 10,
        'auto_reorder' => 0,
        'reorder_quantity' => 50,
        'stock_notifications' => 1,
        'track_movements' => 1
    ],
    'appointments' => [
        'appointment_duration' => 30,
        'advance_booking_days' => 30,
        'max_daily_appointments' => 20,
        'auto_confirm' => 0,
        'send_reminders' => 1,
        'reminder_hours' => 24
    ],
    'notifications' => [
        'email_notifications' => 1,
        'sms_notifications' => 0,
        'new_appointment_alert' => 1,
        'low_stock_alert' => 1,
        'daily_report' => 1,
        'admin_email' => 'admin@opticlook.ma'
    ],
    'security' => [
        'session_timeout' => 120,
        'max_login_attempts' => 5,
        'lockout_duration' => 15,
        'password_expiry' => 90,
        'two_factor_auth' => 0,
        'login_notifications' => 1
    ],
    'backup' => [
        'auto_backup' => 1,
        'backup_frequency' => 'daily',
        'backup_retention' => 30,
        'backup_email' => 'backup@opticlook.ma'
    ]
];

// Statistiques système
try {
    $system_stats = [
        'total_products' => $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn(),
        'total_clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
        'total_appointments' => $pdo->query("SELECT COUNT(*) FROM prendre")->fetchColumn(),
        'database_size' => '2.5 MB', // Simulation
        'last_backup' => '2024-01-15 10:30:00' // Simulation
    ];
} catch (Exception $e) {
    $system_stats = [
        'total_products' => 0,
        'total_clients' => 0,
        'total_appointments' => 0,
        'database_size' => 'N/A',
        'last_backup' => 'N/A'
    ];
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

        .settings-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
        }

        .settings-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .settings-nav {
            list-style: none;
        }

        .settings-nav-item {
            margin-bottom: 5px;
        }

        .settings-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            gap: 12px;
        }

        .settings-nav-link:hover,
        .settings-nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .settings-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
        }

        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-description {
            color: #666;
            margin-top: 8px;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .form-check label {
            margin-bottom: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .btn {
            padding: 12px 24px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
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

        .system-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .status-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            text-align: center;
        }

        .status-number {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }

        .status-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .settings-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }

        .settings-group h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .settings-container {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                position: static;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
            <i class="fas fa-cog"></i>
            <?php echo $page_title; ?>
        </h1>
        <div>
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

    <div class="settings-container">
        <!-- Settings Navigation -->
        <div class="settings-sidebar">
            <ul class="settings-nav">
                <li class="settings-nav-item">
                    <a href="#general" class="settings-nav-link active" onclick="showSection('general')">
                        <i class="fas fa-store"></i>
                        <span>Général</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#stock" class="settings-nav-link" onclick="showSection('stock')">
                        <i class="fas fa-boxes"></i>
                        <span>Stock</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#appointments" class="settings-nav-link" onclick="showSection('appointments')">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Rendez-vous</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#notifications" class="settings-nav-link" onclick="showSection('notifications')">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#security" class="settings-nav-link" onclick="showSection('security')">
                        <i class="fas fa-shield-alt"></i>
                        <span>Sécurité</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#backup" class="settings-nav-link" onclick="showSection('backup')">
                        <i class="fas fa-database"></i>
                        <span>Sauvegarde</span>
                    </a>
                </li>
                <li class="settings-nav-item">
                    <a href="#system" class="settings-nav-link" onclick="showSection('system')">
                        <i class="fas fa-info-circle"></i>
                        <span>Système</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <!-- Section Général -->
            <div id="general" class="settings-section active">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-store"></i>
                        Paramètres Généraux
                    </h2>
                    <p class="section-description">
                        Configuration de base de votre magasin et préférences générales
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="general">
                    
                    <div class="settings-group">
                        <h4>Informations du Magasin</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="shop_name">Nom du magasin *</label>
                                <input type="text" id="shop_name" name="shop_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['general']['shop_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shop_email">Email principal *</label>
                                <input type="email" id="shop_email" name="shop_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['general']['shop_email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shop_phone">Téléphone</label>
                                <input type="tel" id="shop_phone" name="shop_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['general']['shop_phone']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shop_address">Adresse complète</label>
                            <textarea id="shop_address" name="shop_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['general']['shop_address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="shop_description">Description</label>
                            <textarea id="shop_description" name="shop_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['general']['shop_description']); ?></textarea>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4>Préférences Système</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="timezone">Fuseau horaire</label>
                                <select id="timezone" name="timezone" class="form-control">
                                    <option value="Africa/Casablanca" <?php echo ($settings['general']['timezone'] === 'Africa/Casablanca') ? 'selected' : ''; ?>>Maroc (GMT+1)</option>
                                    <option value="Europe/Paris" <?php echo ($settings['general']['timezone'] === 'Europe/Paris') ? 'selected' : ''; ?>>France (GMT+1)</option>
                                    <option value="UTC" <?php echo ($settings['general']['timezone'] === 'UTC') ? 'selected' : ''; ?>>UTC (GMT+0)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="currency">Devise</label>
                                <select id="currency" name="currency" class="form-control">
                                    <option value="MAD" <?php echo ($settings['general']['currency'] === 'MAD') ? 'selected' : ''; ?>>Dirham marocain (MAD)</option>
                                    <option value="EUR" <?php echo ($settings['general']['currency'] === 'EUR') ? 'selected' : ''; ?>>Euro (EUR)</option>
                                    <option value="USD" <?php echo ($settings['general']['currency'] === 'USD') ? 'selected' : ''; ?>>Dollar US (USD)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="language">Langue</label>
                                <select id="language" name="language" class="form-control">
                                    <option value="fr" <?php echo ($settings['general']['language'] === 'fr') ? 'selected' : ''; ?>>Français</option>
                                    <option value="ar" <?php echo ($settings['general']['language'] === 'ar') ? 'selected' : ''; ?>>العربية</option>
                                    <option value="en" <?php echo ($settings['general']['language'] === 'en') ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Stock -->
            <div id="stock" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-boxes"></i>
                        Paramètres de Stock
                    </h2>
                    <p class="section-description">
                        Configuration des alertes et gestion automatique du stock
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="stock">
                    
                    <div class="settings-group">
                        <h4>Alertes de Stock</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="low_stock_threshold">Seuil de stock faible</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" 
                                       class="form-control" min="1" max="100"
                                       value="<?php echo $settings['stock']['low_stock_threshold']; ?>">
                                <div class="help-text">Quantité en dessous de laquelle une alerte est générée</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reorder_quantity">Quantité de réapprovisionnement</label>
                                <input type="number" id="reorder_quantity" name="reorder_quantity" 
                                       class="form-control" min="1"
                                       value="<?php echo $settings['stock']['reorder_quantity']; ?>">
                                <div class="help-text">Quantité suggérée pour le réapprovisionnement</div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="stock_notifications" name="stock_notifications" 
                                   <?php echo $settings['stock']['stock_notifications'] ? 'checked' : ''; ?>>
                            <label for="stock_notifications">Recevoir les notifications de stock faible</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="track_movements" name="track_movements" 
                                   <?php echo $settings['stock']['track_movements'] ? 'checked' : ''; ?>>
                            <label for="track_movements">Suivre les mouvements de stock</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="auto_reorder" name="auto_reorder" 
                                   <?php echo $settings['stock']['auto_reorder'] ? 'checked' : ''; ?>>
                            <label for="auto_reorder">Réapprovisionnement automatique (en développement)</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Rendez-vous -->
            <div id="appointments" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        Paramètres des Rendez-vous
                    </h2>
                    <p class="section-description">
                        Configuration du système de prise de rendez-vous
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="appointments">
                    
                    <div class="settings-group">
                        <h4>Planning</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="appointment_duration">Durée des rendez-vous (minutes)</label>
                                <select id="appointment_duration" name="appointment_duration" class="form-control">
                                    <option value="15" <?php echo ($settings['appointments']['appointment_duration'] == 15) ? 'selected' : ''; ?>>15 minutes</option>
                                    <option value="30" <?php echo ($settings['appointments']['appointment_duration'] == 30) ? 'selected' : ''; ?>>30 minutes</option>
                                    <option value="45" <?php echo ($settings['appointments']['appointment_duration'] == 45) ? 'selected' : ''; ?>>45 minutes</option>
                                    <option value="60" <?php echo ($settings['appointments']['appointment_duration'] == 60) ? 'selected' : ''; ?>>1 heure</option>
                                    <option value="90" <?php echo ($settings['appointments']['appointment_duration'] == 90) ? 'selected' : ''; ?>>1h30</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="advance_booking_days">Réservation à l'avance (jours)</label>
                                <input type="number" id="advance_booking_days" name="advance_booking_days" 
                                       class="form-control" min="1" max="365"
                                       value="<?php echo $settings['appointments']['advance_booking_days']; ?>">
                                <div class="help-text">Nombre maximum de jours à l'avance pour réserver</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_daily_appointments">RDV maximum par jour</label>
                                <input type="number" id="max_daily_appointments" name="max_daily_appointments" 
                                       class="form-control" min="1" max="50"
                                       value="<?php echo $settings['appointments']['max_daily_appointments']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="auto_confirm" name="auto_confirm" 
                                   <?php echo $settings['appointments']['auto_confirm'] ? 'checked' : ''; ?>>
                            <label for="auto_confirm">Confirmation automatique des rendez-vous</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="send_reminders" name="send_reminders" 
                                   <?php echo $settings['appointments']['send_reminders'] ? 'checked' : ''; ?>>
                            <label for="send_reminders">Envoyer des rappels automatiques</label>
                        </div>
                        
                        <div class="form-group">
                            <label for="reminder_hours">Rappel avant (heures)</label>
                            <select id="reminder_hours" name="reminder_hours" class="form-control">
                                <option value="1" <?php echo ($settings['appointments']['reminder_hours'] == 1) ? 'selected' : ''; ?>>1 heure</option>
                                <option value="2" <?php echo ($settings['appointments']['reminder_hours'] == 2) ? 'selected' : ''; ?>>2 heures</option>
                                <option value="24" <?php echo ($settings['appointments']['reminder_hours'] == 24) ? 'selected' : ''; ?>>24 heures</option>
                                <option value="48" <?php echo ($settings['appointments']['reminder_hours'] == 48) ? 'selected' : ''; ?>>48 heures</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Notifications -->
            <div id="notifications" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-bell"></i>
                        Paramètres de Notifications
                    </h2>
                    <p class="section-description">
                        Configuration des alertes et notifications automatiques
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="notifications">
                    
                    <div class="settings-group">
                        <h4>Types de Notifications</h4>
                        <div class="form-check">
                            <input type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?php echo $settings['notifications']['email_notifications'] ? 'checked' : ''; ?>>
                            <label for="email_notifications">Notifications par email</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" 
                                   <?php echo $settings['notifications']['sms_notifications'] ? 'checked' : ''; ?>>
                            <label for="sms_notifications">Notifications par SMS (en développement)</label>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4>Alertes Spécifiques</h4>
                        <div class="form-check">
                            <input type="checkbox" id="new_appointment_alert" name="new_appointment_alert" 
                                   <?php echo $settings['notifications']['new_appointment_alert'] ? 'checked' : ''; ?>>
                            <label for="new_appointment_alert">Nouveau rendez-vous</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="low_stock_alert" name="low_stock_alert" 
                                   <?php echo $settings['notifications']['low_stock_alert'] ? 'checked' : ''; ?>>
                            <label for="low_stock_alert">Stock faible</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="daily_report" name="daily_report" 
                                   <?php echo $settings['notifications']['daily_report'] ? 'checked' : ''; ?>>
                            <label for="daily_report">Rapport quotidien</label>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Email administrateur</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['notifications']['admin_email']); ?>">
                            <div class="help-text">Email pour recevoir les notifications importantes</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Sécurité -->
            <div id="security" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Paramètres de Sécurité
                    </h2>
                    <p class="section-description">
                        Configuration de la sécurité et des accès
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="security">
                    
                    <div class="settings-group">
                        <h4>Sessions</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="session_timeout">Timeout de session (minutes)</label>
                                <input type="number" id="session_timeout" name="session_timeout" 
                                       class="form-control" min="5" max="1440"
                                       value="<?php echo $settings['security']['session_timeout']; ?>">
                                <div class="help-text">Déconnexion automatique après inactivité</div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4>Authentification</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="max_login_attempts">Tentatives de connexion max</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                       class="form-control" min="3" max="10"
                                       value="<?php echo $settings['security']['max_login_attempts']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="lockout_duration">Durée de verrouillage (minutes)</label>
                                <input type="number" id="lockout_duration" name="lockout_duration" 
                                       class="form-control" min="5" max="60"
                                       value="<?php echo $settings['security']['lockout_duration']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password_expiry">Expiration mot de passe (jours)</label>
                                <input type="number" id="password_expiry" name="password_expiry" 
                                       class="form-control" min="30" max="365"
                                       value="<?php echo $settings['security']['password_expiry']; ?>">
                                <div class="help-text">0 = jamais d'expiration</div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="two_factor_auth" name="two_factor_auth" 
                                   <?php echo $settings['security']['two_factor_auth'] ? 'checked' : ''; ?>>
                            <label for="two_factor_auth">Authentification à deux facteurs (en développement)</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="login_notifications" name="login_notifications" 
                                   <?php echo $settings['security']['login_notifications'] ? 'checked' : ''; ?>>
                            <label for="login_notifications">Notifications de connexion</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Sauvegarde -->
            <div id="backup" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-database"></i>
                        Paramètres de Sauvegarde
                    </h2>
                    <p class="section-description">
                        Configuration des sauvegardes automatiques et manuelles
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="backup">
                    
                    <div class="settings-group">
                        <h4>Sauvegarde Automatique</h4>
                        <div class="form-check">
                            <input type="checkbox" id="auto_backup" name="auto_backup" 
                                   <?php echo $settings['backup']['auto_backup'] ? 'checked' : ''; ?>>
                            <label for="auto_backup">Activer la sauvegarde automatique</label>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="backup_frequency">Fréquence</label>
                                <select id="backup_frequency" name="backup_frequency" class="form-control">
                                    <option value="daily" <?php echo ($settings['backup']['backup_frequency'] === 'daily') ? 'selected' : ''; ?>>Quotidienne</option>
                                    <option value="weekly" <?php echo ($settings['backup']['backup_frequency'] === 'weekly') ? 'selected' : ''; ?>>Hebdomadaire</option>
                                    <option value="monthly" <?php echo ($settings['backup']['backup_frequency'] === 'monthly') ? 'selected' : ''; ?>>Mensuelle</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="backup_retention">Rétention (jours)</label>
                                <input type="number" id="backup_retention" name="backup_retention" 
                                       class="form-control" min="7" max="365"
                                       value="<?php echo $settings['backup']['backup_retention']; ?>">
                                <div class="help-text">Durée de conservation des sauvegardes</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="backup_email">Email de notification</label>
                                <input type="email" id="backup_email" name="backup_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['backup']['backup_email']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4>Actions Manuelles</h4>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-info" onclick="createBackup()">
                                <i class="fas fa-download"></i> Créer une sauvegarde
                            </button>
                            <button type="button" class="btn btn-warning" onclick="showRestoreModal()">
                                <i class="fas fa-upload"></i> Restaurer une sauvegarde
                            </button>
                            <button type="button" class="btn btn-primary" onclick="downloadBackup()">
                                <i class="fas fa-file-archive"></i> Télécharger la dernière sauvegarde
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Système -->
            <div id="system" class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Informations Système
                    </h2>
                    <p class="section-description">
                        État du système et informations techniques
                    </p>
                </div>

                <div class="system-status">
                    <div class="status-card">
                        <div class="status-number"><?php echo $system_stats['total_products']; ?></div>
                        <div class="status-label">Produits</div>
                    </div>
                    <div class="status-card">
                        <div class="status-number"><?php echo $system_stats['total_clients']; ?></div>
                        <div class="status-label">Clients</div>
                    </div>
                    <div class="status-card">
                        <div class="status-number"><?php echo $system_stats['total_appointments']; ?></div>
                        <div class="status-label">Rendez-vous</div>
                    </div>
                    <div class="status-card">
                        <div class="status-number"><?php echo $system_stats['database_size']; ?></div>
                        <div class="status-label">Taille BD</div>
                    </div>
                </div>

                <div class="settings-group">
                    <h4>Informations Techniques</h4>
                    <div style="display: grid; gap: 10px;">
                        <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Version OpticLook:</strong>
                            <span>v2.1.0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Version PHP:</strong>
                            <span><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Serveur Web:</strong>
                            <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non détecté'; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Dernière sauvegarde:</strong>
                            <span><?php echo $system_stats['last_backup']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Espace disque utilisé:</strong>
                            <span>2.1 GB / 10 GB</span>
                        </div>
                    </div>
                </div>

                <div class="settings-group">
                    <h4>Actions Système</h4>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-info" onclick="checkUpdates()">
                            <i class="fas fa-sync"></i> Vérifier les mises à jour
                        </button>
                        <button type="button" class="btn btn-warning" onclick="clearCache()">
                            <i class="fas fa-broom"></i> Vider le cache
                        </button>
                        <button type="button" class="btn btn-primary" onclick="optimizeDatabase()">
                            <i class="fas fa-database"></i> Optimiser la BD
                        </button>
                        <button type="button" class="btn btn-danger" onclick="showMaintenanceModal()">
                            <i class="fas fa-tools"></i> Mode maintenance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de restauration -->
<div id="restoreModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
            <h3 style="font-size: 24px; font-weight: 600; color: #333;">
                <i class="fas fa-upload"></i> Restaurer une Sauvegarde
            </h3>
            <span onclick="closeModal('restoreModal')" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #999;">&times;</span>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Attention:</strong> Cette opération remplacera toutes les données actuelles.
            </div>
            <input type="file" id="backupFile" accept=".sql,.zip" style="margin-bottom: 20px;">
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button class="btn btn-danger" onclick="closeModal('restoreModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-warning" onclick="restoreBackup()">
                    <i class="fas fa-upload"></i> Restaurer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Navigation entre sections
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all nav links
    document.querySelectorAll('.settings-nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Add active class to corresponding nav link
    document.querySelector(`[onclick="showSection('${sectionId}')"]`).classList.add('active');
    
    // Update URL hash
    window.location.hash = sectionId;
}

// Load section from URL hash on page load
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        showSection(hash);
    }
});

// Auto-hide alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);

// System actions
function createBackup() {
    if (confirm('Créer une nouvelle sauvegarde maintenant ?')) {
        showNotification('Création de la sauvegarde en cours...', 'info');
        
        // Simulate backup creation
        setTimeout(() => {
            showNotification('Sauvegarde créée avec succès !', 'success');
        }, 3000);
    }
}

function downloadBackup() {
    showNotification('Téléchargement de la sauvegarde...', 'info');
    // Simulate download
    setTimeout(() => {
        showNotification('Sauvegarde téléchargée !', 'success');
    }, 2000);
}

function showRestoreModal() {
    document.getElementById('restoreModal').style.display = 'block';
}

function restoreBackup() {
    const file = document.getElementById('backupFile').files[0];
    if (!file) {
        alert('Veuillez sélectionner un fichier de sauvegarde.');
        return;
    }
    
    if (confirm('Êtes-vous absolument sûr de vouloir restaurer cette sauvegarde ? Toutes les données actuelles seront perdues !')) {
        closeModal('restoreModal');
        showNotification('Restauration en cours... Veuillez patienter.', 'info');
        
        // Simulate restore
        setTimeout(() => {
            showNotification('Restauration terminée avec succès !', 'success');
        }, 5000);
    }
}

function checkUpdates() {
    showNotification('Vérification des mises à jour...', 'info');
    setTimeout(() => {
        showNotification('Système à jour - Version 2.1.0', 'success');
    }, 2000);
}

function clearCache() {
    if (confirm('Vider le cache du système ?')) {
        showNotification('Cache vidé avec succès !', 'success');
    }
}

function optimizeDatabase() {
    if (confirm('Optimiser la base de données ? Cette opération peut prendre quelques minutes.')) {
        showNotification('Optimisation en cours...', 'info');
        setTimeout(() => {
            showNotification('Base de données optimisée !', 'success');
        }, 4000);
    }
}

function showMaintenanceModal() {
    if (confirm('Activer le mode maintenance ? Les utilisateurs ne pourront plus accéder au site.')) {
        showNotification('Mode maintenance activé', 'warning');
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
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

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
                
                // Re-enable after 3 seconds in case of error
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
                }, 3000);
            }
        });
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

console.log('OpticLook Settings Page loaded successfully');
</script>

</body>
</html>