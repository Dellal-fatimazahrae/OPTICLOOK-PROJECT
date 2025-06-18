<?php
session_start();
include "conixion.php";

$error_message = "";
$success_message = "";

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $user_type = $_POST['user_type']; // 'client' ou 'admin'
    
    if (!empty($email) && !empty($mot_de_passe)) {
        try {
            if ($user_type === 'admin') {
                // Connexion administrateur
                $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['administrateur_id'];
                    $_SESSION['user_name'] = $user['nom_complet'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['user_email'] = $user['email'];
                    
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error_message = "Email ou mot de passe incorrect pour l'administrateur.";
                }
            } else {
                // Connexion client
                $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['client_id'];
                    $_SESSION['user_name'] = $user['nom_complet'];
                    $_SESSION['user_type'] = 'client';
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_phone'] = $user['numero_telephone'];
                    
                    header("Location: appointement.php");
                    exit();
                } else {
                    $error_message = "Email ou mot de passe incorrect.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion à la base de données.";
        }
    } else {
        $error_message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpticLook - Connexion</title>
    <link rel="stylesheet" href="Csstotal.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <header>
        <img class="menu" id="menu" src="./assest/images/icones/icons8-menu-50.png" alt="menu">
        <nav id="nav">
            <div class="logo_nav">opticlook</div>
            <div class="page">
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="shop.php">Boutique</a></li>
                </ul>
            </div>
            <div class="icone-header">
                <ul>
                        <li><a href="inscription.php" >inscription</a></li>
                    <li><a href="connexion.php" >connexion</a></li>
                    <li><a href="appointement.php" class="panier-link">Rendez-Vous</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-form-container">
                <h2>Connexion</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="connexion.php" class="auth-form">
                    <div class="form-group">
                        <label for="user_type">Type de compte:</label>
                        <select name="user_type" id="user_type" required>
                            <option value="client">Client</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe:</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    </div>

                    <button type="submit" class="btn-submit">Se connecter</button>
                </form>

                <div class="auth-links">
                    <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
                    <p><a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a></p>
                </div>
            </div>

            <div class="auth-image">
                <div class="image-placeholder">
                    <img src="./assest/images/lunettes-inscription.jpg.jpg" alt="Lunettes OpticLook" class="auth-img">
                </div>
                <div class="auth-text">
                    <h3>Bienvenue chez OpticLook</h3>
                    <p>Connectez-vous pour accéder à votre espace personnel et gérer vos rendez-vous.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="footer">
        <div class="content-footer">
            <div class="contac">
                <div class="titre-contact">Contactez-nous</div>
                <div class="adress">
                    <p>Morocco, Tanger-Ahlan</p>
                    <p>+212 567182560</p>
                    <p>opticlook@gmail.com</p>
                </div>
                <div class="social-media">
                    <a href="#"><img class="sicone" src="./assest/images/icones/icons8-twitter-entouré-50.png" alt="Twitter"></a>
                    <a href="#"><img class="sicone" src="./assest/images/icones/icons8-facebook-entouré-50.png" alt="Facebook"></a>
                    <a href="#"><img class="sicone" src="./assest/images/icones/icons8-youtube-encadré-50 (1).png" alt="YouTube"></a>
                    <a href="#"><img class="sicone" src="./assest/images/icones/icons8-cercle-instagram-60.png" alt="Instagram"></a>
                </div>
            </div>
            <div class="liens">
                <div class="titre-liens">Liens rapides</div>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="shop.php">Boutique</a></li>
                    <li><a href="appointement.php">Rendez-vous</a></li>
                </ul>
            </div>
            <div class="categories">
                <div class="titre-categorie">Nos Catégories</div>
                <ul class="categorie-list">
                    <li><a href="shop.php?category=1">Lunettes Médicales</a></li>
                    <li><a href="shop.php?category=2">Lunettes de Soleil</a></li>
                    <li><a href="shop.php?category=3">Accessoires</a></li>
                </ul>
            </div>
            <div class="logototal">
                <div class="logo"></div>
                <div class="nomlogo">opticlook</div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 OpticLook. Tous droits réservés.</p> 
        </div>
    </section>

    <script src="js.js"></script>
</body>
</html>