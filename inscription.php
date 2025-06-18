<?php
session_start();
include "conixion.php";

$error_message = "";
$success_message = "";

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom_complet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'];
    $numero_telephone = trim($_POST['numero_telephone']);
    $user_type = $_POST['user_type']; // 'client' ou 'admin'
    
    // Validation des données
    if (empty($nom_complet) || empty($email) || empty($mot_de_passe)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } elseif ($mot_de_passe !== $confirmer_mot_de_passe) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mot_de_passe) < 6) {
        $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            if ($user_type === 'admin') {
                $stmt = $pdo->prepare("SELECT email FROM administrateurs WHERE email = ?");
            } else {
                $stmt = $pdo->prepare("SELECT email FROM clients WHERE email = ?");
            }
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error_message = "Cet email est déjà utilisé.";
            } else {
                // Hacher le mot de passe
                $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                
                if ($user_type === 'admin') {
                    // Inscription administrateur
                    $stmt = $pdo->prepare("INSERT INTO administrateurs (nom_complet, email, mot_de_passe) VALUES (?, ?, ?)");
                    $stmt->execute([$nom_complet, $email, $mot_de_passe_hache]);
                    
                    $success_message = "Compte administrateur créé avec succès. Vous pouvez maintenant vous connecter.";
                } else {
                    // Inscription client
                    $stmt = $pdo->prepare("INSERT INTO clients (nom_complet, email, mot_de_passe, numero_telephone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nom_complet, $email, $mot_de_passe_hache, $numero_telephone]);
                    
                    $success_message = "Compte créé avec succès. Vous pouvez maintenant vous connecter.";
                }
                
                // Redirection après inscription réussie
                header("refresh:2;url=connexion.php");
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la création du compte. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpticLook - Inscription</title>
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
                <h2>Créer un compte</h2>
                
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

                <form method="POST" action="inscription.php" class="auth-form" id="inscriptionForm">
                    <div class="form-group">
                        <label for="user_type">Type de compte:</label>
                        <select name="user_type" id="user_type" required>
                            <option value="client" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'client') ? 'selected' : ''; ?>>Client</option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nom_complet">Nom complet: *</label>
                        <input type="text" id="nom_complet" name="nom_complet" required 
                               value="<?php echo isset($_POST['nom_complet']) ? htmlspecialchars($_POST['nom_complet']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email: *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group" id="telephone-group">
                        <label for="numero_telephone">Numéro de téléphone:</label>
                        <input type="tel" id="numero_telephone" name="numero_telephone" 
                               placeholder="+212 6XX XXX XXX"
                               value="<?php echo isset($_POST['numero_telephone']) ? htmlspecialchars($_POST['numero_telephone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe: *</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                        <small>Au moins 6 caractères</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmer_mot_de_passe">Confirmer le mot de passe: *</label>
                        <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" required>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="conditions" required>
                        <label for="conditions">J'accepte les <a href="conditions.php" target="_blank">conditions d'utilisation</a> et la <a href="politique_confidentialite.php" target="_blank">politique de confidentialité</a></label>
                    </div>

                    <button type="submit" class="btn-submit">Créer mon compte</button>
                </form>

                <div class="auth-links">
                    <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
                </div>
            </div>

            <div class="auth-image">
                <div class="image-placeholder">
                    <img src="./assest/images/lunettes-inscription.jpg.jpg" alt="Lunettes OpticLook" class="auth-img">
                </div>
                <div class="auth-text">
                    <h3>Rejoignez OpticLook</h3>
                    <p>Créez votre compte pour profiter de nos services personnalisés et prendre rendez-vous facilement.</p>
                    <ul class="benefits-list">
                        <li>✓ Gestion de vos rendez-vous</li>
                        <li>✓ Historique de vos achats</li>
                        <li>✓ Offres exclusives</li>
                        <li>✓ Support personnalisé</li>
                    </ul>
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
    <script>
        // Validation en temps réel du mot de passe
        document.getElementById('confirmer_mot_de_passe').addEventListener('input', function() {
            const password = document.getElementById('mot_de_passe').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });

        // Masquer/afficher le champ téléphone selon le type d'utilisateur
        document.getElementById('user_type').addEventListener('change', function() {
            const telephoneGroup = document.getElementById('telephone-group');
            const telephoneInput = document.getElementById('numero_telephone');
            
            if (this.value === 'admin') {
                telephoneGroup.style.display = 'none';
                telephoneInput.required = false;
            } else {
                telephoneGroup.style.display = 'block';
                telephoneInput.required = false; // Optionnel pour les clients
            }
        });

        // Validation du format du téléphone marocain
        document.getElementById('numero_telephone').addEventListener('input', function() {
            const phonePattern = /^(\+212|0)([ \-_/]*)(\d[ \-_/]*){8}$/;
            if (this.value && !phonePattern.test(this.value)) {
                this.setCustomValidity('Format de téléphone marocain invalide (ex: +212 6XX XXX XXX)');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>