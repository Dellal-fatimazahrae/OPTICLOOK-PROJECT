<?php 
include "conixion.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpticLook - Boutique</title>
    <link rel="stylesheet" href="Csstotal.css">
    <link rel="stylesheet" href="shop.css">
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

    <section class="hero shop">
        <h1 class="titre-grand">Lunettes & style, à portée de clic</h1>
        <p>Un large choix de lunettes et accessoires pour sublimer votre regard, chaque jour.</p>
        <a href="shop.php" class="btn-hero">Découvrir nos produits</a>
    </section>

    <section class="tout-categorie">
        <div class="title-section">Nos catégories</div> 
        
        <div class="titre-categories medicale">Lunettes médicales</div>
        <div class="lunette" id="cat-1">
            <?php
                try {
                    $stmt = $pdo->query("SELECT produit_id, nom_produit, url_image, prix FROM produits WHERE categorie_id = 1");
                    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($produits) {
                        foreach ($produits as $produit) {
                            echo '<div class="produit">';
                            echo '<img src="' . htmlspecialchars($produit['url_image']) . '" alt="' . htmlspecialchars($produit['nom_produit']) . '" class="img-produit">';
                            echo '<h3 class="nom-produit">' . htmlspecialchars($produit['nom_produit']) . '</h3>';
                            echo '<div class="prix-btn">';
                            echo '<p>' . htmlspecialchars($produit['prix']) . ' dh</p>';
                            echo '<a href="detaill.php?id=' . htmlspecialchars($produit['produit_id']) . '" class="btn-detail">Voir</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo "<p>Aucun produit trouvé.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Erreur de base de données : " . $e->getMessage() . "</p>";
                }
            ?> 
        </div>

        <div class="titre-categories solaire" id="cat-2">Lunettes solaires</div>
        <div class="lunette">
            <?php 
                try {
                    $stmt = $pdo->query("SELECT produit_id, nom_produit, url_image, prix FROM produits WHERE categorie_id = 2");
                    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($produits) {
                        foreach ($produits as $produit) {
                            echo '<div class="produit">';
                            echo '<img src="' . htmlspecialchars($produit['url_image']) . '" alt="' . htmlspecialchars($produit['nom_produit']) . '" class="img-produit">';
                            echo '<h3 class="nom-produit">' . htmlspecialchars($produit['nom_produit']) . '</h3>';
                            echo '<div class="prix-btn">';
                            echo '<p>' . htmlspecialchars($produit['prix']) . ' dh</p>';
                            echo '<a href="detaill.php?id=' . htmlspecialchars($produit['produit_id']) . '" class="btn-detail">Voir</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo "<p>Aucun produit trouvé.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Erreur de base de données : " . $e->getMessage() . "</p>";
                }
            ?>
        </div>

        <div class="titre-categories accessoire" id="cat-3">Accessoires</div>
        <div class="lunette">
            <?php 
                try {
                    $stmt = $pdo->query("SELECT produit_id, nom_produit, url_image, prix FROM produits WHERE categorie_id = 3");
                    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($produits) {
                        foreach ($produits as $produit) {
                            echo '<div class="produit">';
                            echo '<img src="' . htmlspecialchars($produit['url_image']) . '" alt="' . htmlspecialchars($produit['nom_produit']) . '" class="img-produit">';
                            echo '<h3 class="nom-produit">' . htmlspecialchars($produit['nom_produit']) . '</h3>';
                            echo '<div class="prix-btn">';
                            echo '<p>' . htmlspecialchars($produit['prix']) . ' dh</p>';
                            echo '<a href="detaill.php?id=' . htmlspecialchars($produit['produit_id']) . '" class="btn-detail">Voir</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo "<p>Aucun produit trouvé.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Erreur de base de données : " . $e->getMessage() . "</p>";
                }
            ?>
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
                <div class="titre-categories">Nos Catégories</div>
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