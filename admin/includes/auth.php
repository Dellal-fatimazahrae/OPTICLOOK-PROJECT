<?php
/**
 * admin/includes/auth.php
 * Système d'authentification et de sécurité pour l'administration OpticLook
 * 
 * @version 2.1.0
 * @author OpticLook Team
 * @description Gestion complète de l'authentification, sécurité et permissions
 */

// Définir la constante d'accès admin
define('ADMIN_ACCESS', true);

// Configuration de sécurité
define('SESSION_TIMEOUT', 7200); // 2 heures en secondes
define('MAX_LOGIN_ATTEMPTS', 5); // Nombre maximum de tentatives de connexion
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes de verrouillage
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_REGENERATE_INTERVAL', 1800); // 30 minutes

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée de la session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

/**
 * Vérifier si l'utilisateur est connecté en tant qu'administrateur
 * 
 * @return bool True si authentifié, False sinon
 */
function checkAdminAuth() {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        logSecurityEvent('AUTH_FAILED', 'No session data');
        redirectToLogin('Veuillez vous connecter pour accéder à cette page.');
        return false;
    }
    
    // Vérifier si l'utilisateur est bien un administrateur
    if ($_SESSION['user_type'] !== 'admin') {
        logSecurityEvent('ACCESS_DENIED', 'Non-admin user attempted admin access');
        redirectToLogin('Accès refusé. Vous devez être administrateur.');
        return false;
    }
    
    // Vérifier la validité de la session
    if (!isValidSession()) {
        logSecurityEvent('SESSION_INVALID', 'Session validation failed');
        destroySession();
        redirectToLogin('Session expirée. Veuillez vous reconnecter.');
        return false;
    }
    
    // Vérifier si le compte est actif
    if (!isAccountActive()) {
        logSecurityEvent('ACCOUNT_INACTIVE', 'Inactive account attempted access');
        destroySession();
        redirectToLogin('Compte désactivé. Contactez l\'administrateur.');
        return false;
    }
    
    // Mettre à jour l'activité de la session
    updateSessionActivity();
    
    return true;
}

/**
 * Vérifier si la session est valide
 * 
 * @return bool True si valide, False sinon
 */
function isValidSession() {
    // Vérifier le timeout de session
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) {
            logSecurityEvent('SESSION_TIMEOUT', "Session expired after {$inactive_time} seconds");
            return false;
        }
    }
    
    // Vérifier l'adresse IP (sécurité contre le vol de session)
    if (isset($_SESSION['ip_address'])) {
        $current_ip = getRealIPAddress();
        if ($_SESSION['ip_address'] !== $current_ip) {
            logSecurityEvent('IP_MISMATCH', "IP changed from {$_SESSION['ip_address']} to {$current_ip}");
            return false;
        }
    }
    
    // Vérifier l'User-Agent (sécurité supplémentaire)
    if (isset($_SESSION['user_agent'])) {
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($_SESSION['user_agent'] !== $current_user_agent) {
            logSecurityEvent('USER_AGENT_MISMATCH', 'User agent changed');
            return false;
        }
    }
    
    // Vérifier le token de session
    if (isset($_SESSION['session_token'])) {
        $expected_token = generateSessionToken();
        if (!hash_equals($_SESSION['session_token'], $expected_token)) {
            logSecurityEvent('TOKEN_MISMATCH', 'Session token validation failed');
            return false;
        }
    }
    
    return true;
}

/**
 * Vérifier si le compte est actif
 * 
 * @return bool True si actif, False sinon
 */
function isAccountActive() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        global $pdo;
        if (!isset($pdo)) {
            include __DIR__ . '/../../conixion.php';
        }
        
        $stmt = $pdo->prepare("
            SELECT administrateur_id, status, last_login_attempt, failed_login_attempts 
            FROM administrateurs 
            WHERE administrateur_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return false;
        }
        
        // Vérifier si le compte est verrouillé
        if (isset($admin['failed_login_attempts']) && $admin['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $last_attempt = strtotime($admin['last_login_attempt']);
            if (time() - $last_attempt < LOGIN_LOCKOUT_TIME) {
                logSecurityEvent('ACCOUNT_LOCKED', "Account locked due to failed attempts");
                return false;
            }
        }
        
        // Vérifier le statut du compte (si la colonne existe)
        if (isset($admin['status']) && $admin['status'] !== 'active') {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        logSecurityEvent('DATABASE_ERROR', "Account check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Mettre à jour l'activité de la session
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
    
    // Régénérer l'ID de session périodiquement (sécurité)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        session_regenerate_id(true);
    } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        logSecurityEvent('SESSION_REGENERATED', 'Session ID regenerated for security');
    }
    
    // Mettre à jour la dernière activité en base de données
    updateLastActivity();
}

/**
 * Mettre à jour la dernière activité en base de données
 */
function updateLastActivity() {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        global $pdo;
        if (!isset($pdo)) {
            include __DIR__ . '/../../conixion.php';
        }
        
        $stmt = $pdo->prepare("
            UPDATE administrateurs 
            SET last_activity = NOW(), 
                current_ip = ?,
                current_user_agent = ?
            WHERE administrateur_id = ?
        ");
        $stmt->execute([
            getRealIPAddress(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SESSION['user_id']
        ]);
        
    } catch (Exception $e) {
        // Ignorer les erreurs de mise à jour d'activité
        error_log("Erreur mise à jour activité: " . $e->getMessage());
    }
}

/**
 * Détruire la session de manière sécurisée
 */
function destroySession() {
    // Logger la déconnexion
    if (isset($_SESSION['user_id'])) {
        logAdminAction('LOGOUT', 'Session destroyed');
        logSecurityEvent('SESSION_DESTROYED', 'User logged out');
    }
    
    // Supprimer toutes les variables de session
    $_SESSION = array();
    
    // Supprimer le cookie de session s'il existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
}

/**
 * Rediriger vers la page de connexion
 * 
 * @param string $message Message d'erreur à afficher
 */
function redirectToLogin($message = '') {
    $redirect_url = '../connexion.php';
    
    if (!empty($message)) {
        $redirect_url .= '?error=' . urlencode($message);
    }
    
    // Ajouter l'URL de retour si on n'est pas déjà sur la page de connexion
    $current_page = $_SERVER['REQUEST_URI'] ?? '';
    if (!empty($current_page) && !strpos($current_page, 'connexion.php')) {
        $separator = strpos($redirect_url, '?') ? '&' : '?';
        $redirect_url .= $separator . 'redirect=' . urlencode($current_page);
    }
    
    header('Location: ' . $redirect_url);
    exit();
}

/**
 * Vérifier les permissions spécifiques
 * 
 * @param string $permission Permission à vérifier
 * @return bool True si autorisé, False sinon
 */
function checkPermission($permission) {
    if (!checkAdminAuth()) {
        return false;
    }
    
    // Système de permissions extensible
    $admin_permissions = $_SESSION['permissions'] ?? ['all'];
    
    // Super admin a toutes les permissions
    if (in_array('all', $admin_permissions)) {
        return true;
    }
    
    // Vérifier la permission spécifique
    return in_array($permission, $admin_permissions);
}

/**
 * Obtenir les informations de l'utilisateur connecté
 * 
 * @return array|null Informations utilisateur ou null
 */
function getCurrentUser() {
    if (!checkAdminAuth()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'type' => $_SESSION['user_type'],
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null,
        'permissions' => $_SESSION['permissions'] ?? ['all']
    ];
}

/**
 * Obtenir la vraie adresse IP du client
 * 
 * @return string Adresse IP
 */
function getRealIPAddress() {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy/Load Balancer
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Si plusieurs IPs, prendre la première
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Valider l'IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Logger les événements de sécurité
 * 
 * @param string $event Type d'événement
 * @param string $details Détails de l'événement
 */
function logSecurityEvent($event, $details = '') {
    $ip = getRealIPAddress();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'anonymous';
    $session_id = session_id();
    
    $log_entry = sprintf(
        "[%s] SECURITY: %s - User: %s, Session: %s, IP: %s, Details: %s, User-Agent: %s\n",
        $timestamp,
        $event,
        $user_id,
        $session_id,
        $ip,
        $details,
        $user_agent
    );
    
    // Écrire dans le fichier de log de sécurité
    $log_file = __DIR__ . '/../logs/security.log';
    writeToLogFile($log_file, $log_entry);
    
    // Enregistrer aussi en base de données
    try {
        global $pdo;
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$event, $user_id, $ip, $user_agent, $details]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du log de sécurité: " . $e->getMessage());
    }
}

/**
 * Logger les actions d'administration
 * 
 * @param string $action Action effectuée
 * @param string $details Détails de l'action
 */
function logAdminAction($action, $details = '') {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
    $ip = getRealIPAddress();
    $session_id = session_id();
    
    $log_entry = sprintf(
        "[%s] ADMIN: %s - User: %s (ID: %s), Session: %s, IP: %s, Details: %s\n",
        $timestamp,
        $action,
        $user_name,
        $user_id,
        $session_id,
        $ip,
        $details
    );
    
    // Écrire dans le fichier de log d'administration
    $log_file = __DIR__ . '/../logs/admin.log';
    writeToLogFile($log_file, $log_entry);
    
    // Enregistrer aussi en base de données
    try {
        global $pdo;
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (admin_id, action, description, ip_address, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $action, $details, $ip]);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du log admin: " . $e->getMessage());
    }
}

/**
 * Écrire dans un fichier de log de manière sécurisée
 * 
 * @param string $log_file Chemin du fichier
 * @param string $log_entry Entrée de log
 */
function writeToLogFile($log_file, $log_entry) {
    // Créer le dossier de logs s'il n'existe pas
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Rotation des logs si le fichier devient trop gros (>10MB)
    if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) {
        $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename($log_file, $backup_file);
    }
    
    // Écrire l'entrée de log
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Sécuriser les permissions du fichier
    chmod($log_file, 0644);
}

/**
 * Vérifier la force du mot de passe
 * 
 * @param string $password Mot de passe à vérifier
 * @return array Résultat de la vérification
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $feedback = [];
    $requirements = [];
    
    // Longueur minimum
    if (strlen($password) >= PASSWORD_MIN_LENGTH) {
        $strength += 1;
        $requirements['length'] = true;
    } else {
        $feedback[] = "Au moins " . PASSWORD_MIN_LENGTH . " caractères";
        $requirements['length'] = false;
    }
    
    // Contient des minuscules
    if (preg_match('/[a-z]/', $password)) {
        $strength += 1;
        $requirements['lowercase'] = true;
    } else {
        $feedback[] = "Au moins une lettre minuscule";
        $requirements['lowercase'] = false;
    }
    
    // Contient des majuscules
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 1;
        $requirements['uppercase'] = true;
    } else {
        $feedback[] = "Au moins une lettre majuscule";
        $requirements['uppercase'] = false;
    }
    
    // Contient des chiffres
    if (preg_match('/[0-9]/', $password)) {
        $strength += 1;
        $requirements['numbers'] = true;
    } else {
        $feedback[] = "Au moins un chiffre";
        $requirements['numbers'] = false;
    }
    
    // Contient des caractères spéciaux
    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
        $strength += 1;
        $requirements['special'] = true;
    } else {
        $feedback[] = "Au moins un caractère spécial";
        $requirements['special'] = false;
    }
    
    // Vérifier les patterns communs faibles
    $common_patterns = [
        '/(.)\1{2,}/',           // Caractères répétés
        '/12345/',               // Séquences numériques
        '/abcde/',               // Séquences alphabétiques
        '/password/i',           // Mot "password"
        '/admin/i',              // Mot "admin"
        '/qwerty/i'              // Mot "qwerty"
    ];
    
    $has_weak_pattern = false;
    foreach ($common_patterns as $pattern) {
        if (preg_match($pattern, $password)) {
            $has_weak_pattern = true;
            break;
        }
    }
    
    if ($has_weak_pattern) {
        $feedback[] = "Évitez les patterns communs (123, abc, password, etc.)";
        $strength = max(0, $strength - 1);
    }
    
    // Calculer le niveau de sécurité
    $security_level = 'faible';
    if ($strength >= 4 && !$has_weak_pattern) {
        $security_level = 'fort';
    } elseif ($strength >= 3) {
        $security_level = 'moyen';
    }
    
    return [
        'strength' => $strength,
        'max_strength' => 5,
        'feedback' => $feedback,
        'requirements' => $requirements,
        'is_strong' => $strength >= 4 && !$has_weak_pattern,
        'security_level' => $security_level,
        'has_weak_pattern' => $has_weak_pattern
    ];
}

/**
 * Générer un token CSRF sécurisé
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Régénérer le token toutes les heures
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 * 
 * @param string $token Token à vérifier
 * @return bool True si valide, False sinon
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    $is_valid = hash_equals($_SESSION['csrf_token'], $token);
    
    if (!$is_valid) {
        logSecurityEvent('CSRF_TOKEN_INVALID', 'Invalid CSRF token submitted');
    }
    
    return $is_valid;
}

/**
 * Générer un token de session unique
 * 
 * @return string Token de session
 */
function generateSessionToken() {
    $data = $_SESSION['user_id'] . $_SESSION['login_time'] . $_SESSION['ip_address'];
    return hash('sha256', $data . session_id());
}

/**
 * Sanitiser les données d'entrée
 * 
 * @param mixed $data Données à sanitiser
 * @return mixed Données sanitisées
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (!is_string($data)) {
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

/**
 * Valider une adresse email
 * 
 * @param string $email Email à valider
 * @return bool True si valide, False sinon
 */
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Générer un mot de passe sécurisé
 * 
 * @param int $length Longueur du mot de passe
 * @return string Mot de passe généré
 */
function generateSecurePassword($length = 12) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $all_chars = $lowercase . $uppercase . $numbers . $special;
    
    $password = '';
    
    // S'assurer qu'il y a au moins un caractère de chaque type
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Remplir le reste
    for ($i = 4; $i < $length; $i++) {
        $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
    }
    
    // Mélanger les caractères
    return str_shuffle($password);
}

/**
 * Vérifier les tentatives de connexion échouées
 * 
 * @param string $email Email de l'utilisateur
 * @return bool True si autorisé, False si verrouillé
 */
function checkLoginAttempts($email) {
    try {
        global $pdo;
        if (!isset($pdo)) {
            include __DIR__ . '/../../conixion.php';
        }
        
        $stmt = $pdo->prepare("
            SELECT failed_login_attempts, last_login_attempt 
            FROM administrateurs 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return true; // Email n'existe pas, laisser l'authentification échouer normalement
        }
        
        $failed_attempts = $admin['failed_login_attempts'] ?? 0;
        $last_attempt = $admin['last_login_attempt'];
        
        if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
            $time_since_last = time() - strtotime($last_attempt);
            if ($time_since_last < LOGIN_LOCKOUT_TIME) {
                $remaining_time = LOGIN_LOCKOUT_TIME - $time_since_last;
                logSecurityEvent('LOGIN_BLOCKED', "Account locked, {$remaining_time} seconds remaining");
                return false;
            } else {
                // Reset les tentatives après expiration du verrouillage
                resetLoginAttempts($email);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        logSecurityEvent('DATABASE_ERROR', "Login attempts check failed: " . $e->getMessage());
        return true; // En cas d'erreur, permettre la tentative
    }
}

/**
 * Enregistrer une tentative de connexion échouée
 * 
 * @param string $email Email de l'utilisateur
 */
function recordFailedLogin($email) {
    try {
        global $pdo;
        if (!isset($pdo)) {
            include __DIR__ . '/../../conixion.php';
        }
        
        $stmt = $pdo->prepare("
            UPDATE administrateurs 
            SET failed_login_attempts = COALESCE(failed_login_attempts, 0) + 1,
                last_login_attempt = NOW()
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        logSecurityEvent('LOGIN_FAILED', "Failed login attempt for: $email");
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement de la tentative échouée: " . $e->getMessage());
    }
}

/**
 * Réinitialiser les tentatives de connexion
 * 
 * @param string $email Email de l'utilisateur
 */
function resetLoginAttempts($email) {
    try {
        global $pdo;
        if (!isset($pdo)) {
            include __DIR__ . '/../../conixion.php';
        }
        
        $stmt = $pdo->prepare("
            UPDATE administrateurs 
            SET failed_login_attempts = 0,
                last_successful_login = NOW()
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        logSecurityEvent('LOGIN_ATTEMPTS_RESET', "Login attempts reset for: $email");
        
    } catch (Exception $e) {
        error_log("Erreur lors de la réinitialisation des tentatives: " . $e->getMessage());
    }
}

/**
 * Initialiser la session admin de manière sécurisée
 * 
 * @param array $admin_data Données de l'administrateur
 */
function initAdminSession($admin_data) {
    // Régénérer l'ID de session pour éviter la fixation
    session_regenerate_id(true);
    
    // Stocker les données de l'administrateur
    $_SESSION['user_id'] = $admin_data['administrateur_id'];
    $_SESSION['user_name'] = $admin_data['nom_complet'];
    $_SESSION['user_email'] = $admin_data['email'];
    $_SESSION['user_type'] = 'admin';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = getRealIPAddress();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['last_regeneration'] = time();
    $_SESSION['session_token'] = generateSessionToken();
    $_SESSION['permissions'] = $admin_data['permissions'] ?? ['all'];
    
    // Générer un token CSRF
    generateCSRFToken();
    
    // Réinitialiser les tentatives de connexion
    resetLoginAttempts($admin_data['email']);
    
    // Logger la connexion
    logAdminAction('LOGIN', 'Connexion administrateur réussie');
    logSecurityEvent('LOGIN_SUCCESS', 'Admin login successful');
}

/**
 * Nettoyer les sessions et logs expirés
 */
