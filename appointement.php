<?php
session_start();
include "conixion.php";

$error_message = "";
$success_message = "";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: connexion.php?redirect=appointement.php");
    exit();
}

// Traitement du formulaire de rendez-vous
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $produit_id = (int)$_POST['produit_id'];
    $date_rendez_vous = $_POST['date_rendez_vous'];
    $heure_rendez_vous = $_POST['heure_rendez_vous'];
    $message = trim($_POST['message']);
    
    // Combiner date et heure
    $datetime_rdv = $date_rendez_vous . ' ' . $heure_rendez_vous . ':00';
    
    if (empty($produit_id) || empty($date_rendez_vous) || empty($heure_rendez_vous)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Vérifier si le produit existe
            $stmt = $pdo->prepare("SELECT nom_produit FROM produits WHERE produit_id = ?");
            $stmt->execute([$produit_id]);
            $produit = $stmt->fetch();
            
            if (!$produit) {
                $error_message = "Produit non trouvé.";
            } else {
                // Vérifier si un rendez-vous n'existe pas déjà
                $stmt = $pdo->prepare("SELECT * FROM prendre WHERE client_id = ? AND produit_id = ? AND DATE_RENDEZ_VOUS = ?");
                $stmt->execute([$_SESSION['user_id'], $produit_id, $datetime_rdv]);
                
                if ($stmt->fetch()) {
                    $error_message = "Vous avez déjà un rendez-vous pour ce produit à cette date.";
                } else {
                    // Insérer le rendez-vous
                    $stmt = $pdo->prepare("INSERT INTO prendre (client_id, administrateur_id, produit_id, STATUS_RENDEZ_VOUS, DATE_RENDEZ_VOUS) VALUES (?, 1, ?, 0, ?)");
                    $stmt->execute([$_SESSION['user_id'], $produit_id, $datetime_rdv]);
                    
                    $success_message = "Votre rendez-vous a été enregistré avec succès. Nous vous confirmerons la disponibilité sous peu.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'enregistrement du rendez-vous.";
        }
    }
}

// Récupérer les produits pour le formulaire
try {
    $stmt = $pdo->query("SELECT produit_id, nom_produit, prix FROM produits ORDER BY nom_produit");
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    $produits = [];
}

// Récupérer les rendez-vous du client
try {
    $stmt = $pdo->prepare("
        SELECT p.DATE_RENDEZ_VOUS, p.STATUS_RENDEZ_VOUS, pr.nom_produit, pr.prix 
        FROM prendre p 
        JOIN produits pr ON p.produit_id = pr.produit_id 
        WHERE p.client_id = ? 
        ORDER BY p.DATE_RENDEZ_VOUS DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $mes_rdv = $stmt->fetchAll();
} catch (PDOException $e) {
    $mes_rdv = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpticLook - Prendre Rendez-vous</title>
    <link rel="stylesheet" href="Csstotal.css">
    <link rel="stylesheet" href="appointement.css">
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
                    <li><a href="appointement.php" class="active">Rendez-vous</a></li>
                </ul>
            </div>
            <div class="icone-header">
                <ul>
                    <li class="user-info">
                        <span>Bonjour, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </li>
                        <!-- <li><a href="inscription.php" >inscription</a></li>
                     <li><a href="connexion.php" >connexion</a></li> -->
                    <li><a href="appointement.php" class="panier-link">Rendez-Vous</a></li>
                    <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <section class="appointment-hero">
        <div class="hero-content">
            <h1>Prendre Rendez-vous</h1>
            <p>Réservez votre consultation avec nos experts opticiens</p>
        </div>
    </section>

    <section class="appointment-section">
        <div class="appointment-container">
            <div class="appointment-form-section">
                <div class="form-header">
                    <h2>📅 Nouveau Rendez-vous</h2>
                    <p>Choisissez le produit qui vous intéresse et votre créneau préféré</p>
                </div>

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

                <form method="POST" action="appointement.php" class="appointment-form" id="appointmentForm">
                    <div class="form-group">
                        <label for="produit_id">Produit d'intérêt: *</label>
                        <select name="produit_id" id="produit_id" required>
                            <option value="">-- Sélectionnez un produit --</option>
                            <?php foreach ($produits as $produit): ?>
                                <option value="<?php echo $produit['produit_id']; ?>" 
                                        data-prix="<?php echo $produit['prix']; ?>">
                                    <?php echo htmlspecialchars($produit['nom_produit']) . ' - ' . $produit['prix'] . ' dh'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_rendez_vous">Date: *</label>
                            <input type="date" id="date_rendez_vous" name="date_rendez_vous" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="heure_rendez_vous">Heure: *</label>
                            <select name="heure_rendez_vous" id="heure_rendez_vous" required>
                                <option value="">-- Choisir l'heure --</option>
                                <option value="09:00">09:00</option>
                                <option value="09:30">09:30</option>
                                <option value="10:00">10:00</option>
                                <option value="10:30">10:30</option>
                                <option value="11:00">11:00</option>
                                <option value="11:30">11:30</option>
                                <option value="14:00">14:00</option>
                                <option value="14:30">14:30</option>
                                <option value="15:00">15:00</option>
                                <option value="15:30">15:30</option>
                                <option value="16:00">16:00</option>
                                <option value="16:30">16:30</option>
                                <option value="17:00">17:00</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Message (optionnel):</label>
                        <textarea id="message" name="message" rows="4" 
                                  placeholder="Décrivez vos besoins spécifiques, questions, ou préférences..."></textarea>
                    </div>

                    <button type="submit" class="btn-appointment">Réserver le rendez-vous</button>
                </form>
            </div>

            <div class="appointment-info-section">
                <div class="info-card">
                    <h3>🏪 Informations Pratiques</h3>
                    <div class="info-content">
                        <div class="info-item">
                            <strong>📍 Adresse:</strong>
                            <p>Morocco, Tanger-Ahlan</p>
                        </div>
                        <div class="info-item">
                            <strong>📞 Téléphone:</strong>
                            <p>+212 567182560</p>
                        </div>
                        <div class="info-item">
                            <strong>📧 Email:</strong>
                            <p>opticlook@gmail.com</p>
                        </div>
                        <div class="info-item">
                            <strong>🕒 Horaires:</strong>
                            <p>Lundi - Vendredi: 9h00 - 17h30<br>
                            Samedi: 9h00 - 12h00<br>
                            Dimanche: Fermé</p>
                        </div>
                    </div>
                </div>

                <div class="services-card">
                    <h3>👓 Nos Services</h3>
                    <ul class="services-list">
                        <li>✓ Examen de la vue complet</li>
                        <li>✓ Conseil personnalisé</li>
                        <li>✓ Essayage de montures</li>
                        <li>✓ Ajustement et réparation</li>
                        <li>✓ Devis gratuit</li>
                        <li>✓ Suivi après-vente</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($mes_rdv)): ?>
    <section class="my-appointments-section">
        <div class="container">
            <h2>📋 Mes Rendez-vous</h2>
            
            <div class="appointments-grid">
                <?php foreach ($mes_rdv as $rdv): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <h4><?php echo htmlspecialchars($rdv['nom_produit']); ?></h4>
                            <span class="status-badge <?php echo getStatusClass($rdv['STATUS_RENDEZ_VOUS']); ?>">
                                <?php echo getStatusText($rdv['STATUS_RENDEZ_VOUS']); ?>
                            </span>
                        </div>
                        <div class="appointment-details">
                            <p><strong>📅 Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($rdv['DATE_RENDEZ_VOUS'])); ?></p>
                            <p><strong>💰 Prix:</strong> <?php echo $rdv['prix']; ?> dh</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
        // Validation du formulaire de rendez-vous
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const date = document.getElementById('date_rendez_vous').value;
            const heure = document.getElementById('heure_rendez_vous').value;
            
            if (!date || !heure) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            // Vérifier que la date n'est pas un dimanche
            const selectedDate = new Date(date);
            if (selectedDate.getDay() === 0) {
                e.preventDefault();
                alert('Nous sommes fermés le dimanche. Veuillez choisir une autre date.');
                return;
            }
            
            // Vérifier les horaires selon le jour
            const hour = parseInt(heure.split(':')[0]);
            if (selectedDate.getDay() === 6) { // Samedi
                if (hour >= 12) {
                    e.preventDefault();
                    alert('Le samedi, nous sommes ouverts seulement de 9h00 à 12h00.');
                    return;
                }
            }
        });

        // Filtrer les heures disponibles selon le jour sélectionné
        document.getElementById('date_rendez_vous').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const heureSelect = document.getElementById('heure_rendez_vous');
            const options = heureSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value) {
                    const hour = parseInt(option.value.split(':')[0]);
                    
                    if (selectedDate.getDay() === 6) { // Samedi
                        option.style.display = hour < 12 ? 'block' : 'none';
                    } else {
                        option.style.display = 'block';
                    }
                }
            });
            
            // Reset la sélection de l'heure
            heureSelect.value = '';
        });
        
    </script>
</body>
</html>

<?php
// Fonctions PHP pour les statuts
function getStatusClass($status) {
    switch($status) {
        case 0: return 'pending';
        case 1: return 'approved';
        case 2: return 'rejected';
        default: return 'pending';
    }
}

function getStatusText($status) {
    switch($status) {
        case 0: return 'En attente';
        case 1: return 'Confirmé';
        case 2: return 'Annulé';
        default: return 'Inconnu';
    }
}
?>