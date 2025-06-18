<?php 
include "conixion.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Csstotal.css">
    <link rel="stylesheet" href="detaill.css">
    <title>OpticLook - Détail Produit</title>
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

    <?php 
    echo "<div class='détails-complet'>";
    
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            // Récupérer le produit sélectionné
            $stmt = $pdo->prepare("SELECT * FROM produits WHERE produit_id = ?");
            $stmt->execute([$id]);
            $produit = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($produit) {
                // Affichage des détails du produit
                echo '<div class="detail-produit">';
                echo '  <div class="divimg">';
                echo '      <img src="' . htmlspecialchars($produit['url_image']) . '" alt="' . htmlspecialchars($produit['nom_produit']) . '" class="img-detail">';
                echo '  </div>';
                echo '  <div class="autreinfo">';
                echo '      <h2>' . htmlspecialchars($produit['nom_produit']) . '</h2>';
                echo '      <p class="description">' . htmlspecialchars($produit['description_produit']) . '</p>';
                echo '      <p class="prix"><strong>' . htmlspecialchars($produit['prix']) . ' dh</strong></p>';
                echo '      <p><strong>Stock disponible:</strong> ' . htmlspecialchars($produit['quantite_stock']) . '</p>';
                echo '      <p><strong>Genre:</strong> ' . htmlspecialchars($produit['genre']) . '</p>';
                echo '      <a href="appointement.php" class="btn-rdv">Prendre rendez-vous</a>';
                echo '  </div>';
                echo '</div>';

                // Récupérer les produits similaires
                $categorie_id = $produit['categorie_id'];
                $stmt_similaires = $pdo->prepare("SELECT * FROM produits WHERE categorie_id = ? AND produit_id != ? LIMIT 4");
                $stmt_similaires->execute([$categorie_id, $id]);
                $produits_similaires = $stmt_similaires->fetchAll(PDO::FETCH_ASSOC);

                if ($produits_similaires) {
                    echo '<h3>Produits similaires</h3>';
                    echo '<div class="liste-similaires">';
                    foreach ($produits_similaires as $similaire) {
                        echo '<div class="produit">';
                        echo '  <img src="' . htmlspecialchars($similaire['url_image']) . '" alt="' . htmlspecialchars($similaire['nom_produit']) . '" class="img-produit">';
                        echo '  <h3 class="nom-produit">' . htmlspecialchars($similaire['nom_produit']) . '</h3>';
                        echo '  <div class="prix-btn">';
                        echo '      <p>' . htmlspecialchars($similaire['prix']) . ' dh</p>';
                        echo '      <a href="detaill.php?id=' . htmlspecialchars($similaire['produit_id']) . '" class="btn-detail">Voir</a>';
                        echo '  </div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo "<p>Aucun produit similaire trouvé.</p>";
                }

                echo '<div class="voir-plus">';
                echo '  <a href="shop.php#cat-' . htmlspecialchars($categorie_id) . '" class="btn-voir-plus">Voir plus</a>';
                echo '</div>';

            } else {
                echo "<p>Produit introuvable.</p>";
            }
        } catch (PDOException $e) {
            echo "<p>Erreur : " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>ID invalide.</p>";
    }

    echo "</div>";
    ?>

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