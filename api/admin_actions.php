<?php
// api/admin_actions.php - Separate API file for better organization (Optional)
session_start();
include "../conixion.php";

// Vérifier si l'utilisateur est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_product':
            addProduct($pdo);
            break;
            
        case 'edit_product':
            editProduct($pdo);
            break;
            
        case 'delete_product':
            deleteProduct($pdo);
            break;
            
        case 'get_products':
            getProducts($pdo);
            break;
            
        case 'get_clients':
            getClients($pdo);
            break;
            
        case 'get_appointments':
            getAppointments($pdo);
            break;
            
        case 'update_appointment_status':
            updateAppointmentStatus($pdo);
            break;
            
        case 'delete_appointment':
            deleteAppointment($pdo);
            break;
            
        case 'update_stock':
            updateStock($pdo);
            break;
            
        case 'get_statistics':
            getStatistics($pdo);
            break;
            
        case 'delete_client':
            deleteClient($pdo);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function addProduct($pdo) {
    $required = ['nom_produit', 'categorie_id', 'prix', 'quantite_stock'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }
    
    $nom_produit = trim($_POST['nom_produit']);
    $categorie_id = (int)$_POST['categorie_id'];
    $description_produit = trim($_POST['description_produit'] ?? '');
    $prix = (float)$_POST['prix'];
    $quantite_stock = (int)$_POST['quantite_stock'];
    $genre = trim($_POST['genre'] ?? 'Unisexe');
    $url_image = trim($_POST['url_image'] ?? '');
    
    // Validation
    if ($prix <= 0) {
        throw new Exception('Le prix doit être supérieur à 0');
    }
    
    if ($quantite_stock < 0) {
        throw new Exception('La quantité en stock ne peut pas être négative');
    }
    
    if (!in_array($categorie_id, [1, 2, 3])) {
        throw new Exception('Catégorie invalide');
    }
    
    // Validation de l'URL de l'image
    if (!empty($url_image) && !filter_var($url_image, FILTER_VALIDATE_URL)) {
        throw new Exception('URL de l\'image invalide');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO produits (categorie_id, nom_produit, description_produit, prix, quantite_stock, genre, url_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$categorie_id, $nom_produit, $description_produit, $prix, $quantite_stock, $genre, $url_image]);
    
    $product_id = $pdo->lastInsertId();
    
    // Log l'action d'administration
    logAdminAction($pdo, $_SESSION['user_id'], 'add_product', "Produit ajouté: $nom_produit (ID: $product_id)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté avec succès',
        'product_id' => $product_id
    ]);
}

function editProduct($pdo) {
    $product_id = (int)($_POST['edit_product_id'] ?? 0);
    
    if (!$product_id) {
        throw new Exception('ID du produit requis pour la modification');
    }
    
    // Vérifier que le produit existe
    $stmt = $pdo->prepare("SELECT produit_id FROM produits WHERE produit_id = ?");
    $stmt->execute([$product_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Produit non trouvé');
    }
    
    $nom_produit = trim($_POST['nom_produit']);
    $categorie_id = (int)$_POST['categorie_id'];
    $description_produit = trim($_POST['description_produit'] ?? '');
    $prix = (float)$_POST['prix'];
    $quantite_stock = (int)$_POST['quantite_stock'];
    $genre = trim($_POST['genre'] ?? 'Unisexe');
    $url_image = trim($_POST['url_image'] ?? '');
    
    // Validation
    if (empty($nom_produit) || $prix <= 0 || $quantite_stock < 0) {
        throw new Exception('Données invalides');
    }
    
    $stmt = $pdo->prepare("
        UPDATE produits 
        SET categorie_id = ?, nom_produit = ?, description_produit = ?, prix = ?, quantite_stock = ?, genre = ?, url_image = ?
        WHERE produit_id = ?
    ");
    
    $stmt->execute([$categorie_id, $nom_produit, $description_produit, $prix, $quantite_stock, $genre, $url_image, $product_id]);
    
    logAdminAction($pdo, $_SESSION['user_id'], 'edit_product', "Produit modifié: $nom_produit (ID: $product_id)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit modifié avec succès'
    ]);
}

function deleteProduct($pdo) {
    $product_id = (int)($_POST['produit_id'] ?? 0);
    
    if (!$product_id) {
        throw new Exception('ID du produit requis');
    }
    
    // Récupérer le nom du produit avant suppression pour le log
    $stmt = $pdo->prepare("SELECT nom_produit FROM produits WHERE produit_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    // Vérifier s'il y a des rendez-vous liés à ce produit
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prendre WHERE produit_id = ?");
    $stmt->execute([$product_id]);
    $appointments_count = $stmt->fetch()['count'];
    
    if ($appointments_count > 0) {
        throw new Exception('Impossible de supprimer: ce produit a des rendez-vous associés');
    }
    
    $stmt = $pdo->prepare("DELETE FROM produits WHERE produit_id = ?");
    $stmt->execute([$product_id]);
    
    logAdminAction($pdo, $_SESSION['user_id'], 'delete_product', "Produit supprimé: {$product['nom_produit']} (ID: $product_id)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit supprimé avec succès'
    ]);
}

function getProducts($pdo) {
    $stmt = $pdo->query("
        SELECT p.*, c.nom_categorie 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.categorie_id 
        ORDER BY p.produit_id DESC
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
}

function getClients($pdo) {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY client_id DESC");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($clients);
}

function getAppointments($pdo) {
    $status_filter = "";
    if (isset($_POST['status']) && $_POST['status'] !== 'all' && is_numeric($_POST['status'])) {
        $status_filter = " WHERE p.STATUS_RENDEZ_VOUS = " . (int)$_POST['status'];
    }
    
    $stmt = $pdo->query("
        SELECT p.*, c.nom_complet as client_nom, pr.nom_produit 
        FROM prendre p 
        JOIN clients c ON p.client_id = c.client_id 
        JOIN produits pr ON p.produit_id = pr.produit_id 
        $status_filter
        ORDER BY p.DATE_RENDEZ_VOUS DESC
    ");
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($appointments);
}

function updateAppointmentStatus($pdo) {
    $client_id = (int)$_POST['client_id'];
    $produit_id = (int)$_POST['produit_id'];
    $status = (int)$_POST['status'];
    $date_rdv = $_POST['date_rdv'];
    
    if (!in_array($status, [0, 1, 2])) {
        throw new Exception('Statut invalide');
    }
    
    $stmt = $pdo->prepare("
        UPDATE prendre 
        SET STATUS_RENDEZ_VOUS = ? 
        WHERE client_id = ? AND produit_id = ? AND DATE_RENDEZ_VOUS = ?
    ");
    
    $result = $stmt->execute([$status, $client_id, $produit_id, $date_rdv]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Rendez-vous non trouvé');
    }
    
    $status_text = ['En attente', 'Validé', 'Refusé'][$status];
    logAdminAction($pdo, $_SESSION['user_id'], 'update_appointment', "RDV mis à jour: $status_text (Client: $client_id, Produit: $produit_id)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
}

function deleteAppointment($pdo) {
    $client_id = (int)$_POST['client_id'];
    $produit_id = (int)$_POST['produit_id'];
    $date_rdv = $_POST['date_rdv'];
    
    $stmt = $pdo->prepare("
        DELETE FROM prendre 
        WHERE client_id = ? AND produit_id = ? AND DATE_RENDEZ_VOUS = ?
    ");
    
    $result = $stmt->execute([$client_id, $produit_id, $date_rdv]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Rendez-vous non trouvé');
    }
    
    logAdminAction($pdo, $_SESSION['user_id'], 'delete_appointment', "RDV supprimé (Client: $client_id, Produit: $produit_id)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Rendez-vous supprimé avec succès'
    ]);
}

function updateStock($pdo) {
    $product_id = (int)$_POST['produit_id'];
    $new_stock = (int)$_POST['new_stock'];
    
    if ($new_stock < 0) {
        throw new Exception('Le stock ne peut pas être négatif');
    }
    
    // Récupérer l'ancien stock pour le log
    $stmt = $pdo->prepare("SELECT nom_produit, quantite_stock FROM produits WHERE produit_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    $stmt = $pdo->prepare("UPDATE produits SET quantite_stock = ? WHERE produit_id = ?");
    $stmt->execute([$new_stock, $product_id]);
    
    logAdminAction($pdo, $_SESSION['user_id'], 'update_stock', "Stock mis à jour: {$product['nom_produit']} - {$product['quantite_stock']} → $new_stock");
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock mis à jour avec succès'
    ]);
}

function getStatistics($pdo) {
    $stats = [];
    
    // Statistiques générales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
    $stats['total_products'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
    $stats['total_clients'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prendre WHERE STATUS_RENDEZ_VOUS = 0");
    $stats['pending_appointments'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE quantite_stock < 5");
    $stats['low_stock_products'] = $stmt->fetch()['total'];
    
    // Statistiques par catégorie
    $stmt = $pdo->query("
        SELECT c.nom_categorie, COUNT(p.produit_id) as count 
        FROM categories c 
        LEFT JOIN produits p ON c.categorie_id = p.categorie_id 
        GROUP BY c.categorie_id, c.nom_categorie
    ");
    $stats['products_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inscriptions par mois (derniers 6 mois)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(NOW() - INTERVAL n.n MONTH, '%Y-%m') as month,
            COUNT(c.client_id) as count
        FROM (
            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        ) n
        LEFT JOIN clients c ON DATE_FORMAT(c.client_id, '%Y-%m') = DATE_FORMAT(NOW() - INTERVAL n.n MONTH, '%Y-%m')
        GROUP BY month
        ORDER BY month DESC
    ");
    $stats['registrations_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($stats);
}

function deleteClient($pdo) {
    $client_id = (int)$_POST['client_id'];
    
    if (!$client_id) {
        throw new Exception('ID du client requis');
    }
    
    // Récupérer les infos du client pour le log
    $stmt = $pdo->prepare("SELECT nom_complet, email FROM clients WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        throw new Exception('Client non trouvé');
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Supprimer d'abord les rendez-vous du client
        $stmt = $pdo->prepare("DELETE FROM prendre WHERE client_id = ?");
        $stmt->execute([$client_id]);
        
        // Supprimer le client
        $stmt = $pdo->prepare("DELETE FROM clients WHERE client_id = ?");
        $stmt->execute([$client_id]);
        
        // Valider la transaction
        $pdo->commit();
        
        logAdminAction($pdo, $_SESSION['user_id'], 'delete_client', "Client supprimé: {$client['nom_complet']} ({$client['email']})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Client supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollback();
        throw $e;
    }
}

function logAdminAction($pdo, $admin_id, $action, $description) {
    try {
        // Créer la table des logs si elle n'existe pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                FOREIGN KEY (admin_id) REFERENCES administrateurs(administrateur_id)
            )
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, description, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$admin_id, $action, $description, $ip_address]);
        
    } catch (Exception $e) {
        // Log des erreurs ne doit pas faire échouer l'action principale
        error_log("Erreur lors de l'enregistrement du log: " . $e->getMessage());
    }
}

// Fonction utilitaire pour valider les données
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        if ($rule['required'] && empty($value)) {
            $errors[] = "Le champ $field est requis";
            continue;
        }
        
        if (!empty($value)) {
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Le champ $field doit être un email valide";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[] = "Le champ $field doit être une URL valide";
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[] = "Le champ $field doit être un nombre";
                        }
                        break;
                }
            }
            
            if (isset($rule['min']) && $value < $rule['min']) {
                $errors[] = "Le champ $field doit être supérieur ou égal à {$rule['min']}";
            }
            
            if (isset($rule['max']) && $value > $rule['max']) {
                $errors[] = "Le champ $field doit être inférieur ou égal à {$rule['max']}";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[] = "Le champ $field ne peut pas dépasser {$rule['max_length']} caractères";
            }
        }
    }
    
    return $errors;
}

// Fonction pour nettoyer et sécuriser les entrées
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Exemple d'utilisation de la validation (non utilisé dans ce code mais disponible)
function validateProductData($data) {
    $rules = [
        'nom_produit' => ['required' => true, 'max_length' => 255],
        'categorie_id' => ['required' => true, 'type' => 'number', 'min' => 1, 'max' => 3],
        'prix' => ['required' => true, 'type' => 'number', 'min' => 0.01],
        'quantite_stock' => ['required' => true, 'type' => 'number', 'min' => 0],
        'url_image' => ['required' => false, 'type' => 'url'],
        'description_produit' => ['required' => false, 'max_length' => 1000]
    ];
    
    return validateInput($data, $rules);
}
?>