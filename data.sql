
CREATE DATABASE opti_db;
USE opti_db;
-- --------------------------------------------------------
-- Table: administrateurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS administrateurs (
  administrateur_id INT(11) NOT NULL AUTO_INCREMENT,
  nom_complet VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  PRIMARY KEY (administrateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  categorie_id INT(11) NOT NULL AUTO_INCREMENT,
  nom_categorie VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (categorie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO categories (categorie_id, nom_categorie) VALUES
(1, 'Lunettes Médicales'),
(2, 'Lunettes de Soleil'),
(3, 'Accessoires');


-- --------------------------------------------------------
-- Table: clients
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
  client_id INT(11) NOT NULL AUTO_INCREMENT,
  nom_complet VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  numero_telephone VARCHAR(50),
  PRIMARY KEY (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: emplacements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS emplacements (
  emplacement_id INT(11) NOT NULL AUTO_INCREMENT,
  numero_emplacement VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (emplacement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table: produits
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS produits (
  produit_id INT(11) NOT NULL AUTO_INCREMENT,
  categorie_id INT(11) NOT NULL,
  nom_produit VARCHAR(255) NOT NULL,
  description_produit VARCHAR(1000),
  prix DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  quantite_stock INT(11) NOT NULL DEFAULT 0,
  genre text(10),
  url_image VARCHAR(255),
  PRIMARY KEY (produit_id),
  FOREIGN KEY (categorie_id) REFERENCES categories(categorie_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table: gestionproduits
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS gestionproduits (
  administrateur_id INT(11) NOT NULL,
  produit_id INT(11) NOT NULL,
  PRIMARY KEY (administrateur_id, produit_id),
  FOREIGN KEY (administrateur_id) REFERENCES administrateurs(administrateur_id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id) REFERENCES produits(produit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: mouvementsstock
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS mouvementsstock (
  produit_id INT(11) NOT NULL,
  emplacement_id INT(11) NOT NULL,
  date_mouvement TIMESTAMP NULL DEFAULT NULL,
  quantite DECIMAL(10,2) DEFAULT 0,
  statut_flux SMALLINT(6) DEFAULT NULL COMMENT '1 = Entrée, 0 = Sortie',
  PRIMARY KEY (produit_id, emplacement_id),
  FOREIGN KEY (produit_id) REFERENCES produits(produit_id) ON DELETE CASCADE,
  FOREIGN KEY (emplacement_id) REFERENCES emplacements(emplacement_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: prendre
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS prendre (
  client_id INT(11) NOT NULL,  -- يقصد به client_id؟
  administrateur_id INT(11) NOT NULL,  -- يقصد به administrateur_id؟
  produit_id INT(11) NOT NULL,
  STATUS_RENDEZ_VOUS SMALLINT(6) DEFAULT NULL COMMENT '0 = En attente, 1 = Validé, 2 = Refusé',
  DATE_RENDEZ_VOUS TIMESTAMP NULL DEFAULT NULL,
PRIMARY KEY (client_id, administrateur_id, produit_id, DATE_RENDEZ_VOUS)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO produits (produit_id, categorie_id, nom_produit, description_produit, prix, quantite_stock, genre, url_image) VALUES
(1, 1, 'Lunettes de Vue Bleather Thorne', 'Monture unisexe noire, forme aviateur moderne. Design élégant et confortable.', 129.99, 50, 'Unisexe', 'https://s1.octika.com/75233-new_product_size/osmose-optique-metal-noir-jaune-homme-os861c1.jpg'),
(2, 1, 'Lunettes de Vue Bleather Classique', 'Monture rectangulaire noire, design intemporel et polyvalent pour homme et femme.', 95.00, 75, 'Unisexe', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQZtrs0iFw828NJ4wtO4rnvEsPtby0r5ae35Q&s'),
(3, 1, 'Lunettes de Vue Tom Ford TF5634B', 'Monture carrée noire distinctive avec détails dorés sur les branches, verres anti-lumière bleue.', 249.99, 30, 'Unisexe', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFOl50DnF79VYO_OkjPJTdQKcW89E8ZNzw6dlMQVxUoxlgiqFKpr3MGlw&s'),
(4, 1, 'Lunettes de Vue Ray-Ban Classique', 'Monture carrée noire mate, légère et durable, style emblématique.', 110.00, 60, 'Unisexe', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSYCqxsq-loEFVyhwGI3D_96NITzo4KFQaLgPtoOWO5sYz2nGaxiyZKLRs&s'),
(9, 2, 'Persol PO0649 Steve McQueen', 'Réédition du modèle légendaire porté par Steve McQueen, style vintage.', 289.99, 9, 'Homme', 'https://drlunettes.ma/cdn/shop/files/lunettes-de-soleil-unisex-jeff-carter-hawk-bleu-216405.jpg?v=1746264528'),
(12, 2, 'Chanel CH5377 Solaire', 'Lunettes de soleil Chanel oversize avec chaîne dorée, luxe et protection.', 399.99, 5, 'Femme', 'https://www.bleather.ma/cdn/shop/files/zephyr-lunettes-de-soleil-de-luxe-noir-908125.png?v=1727198774&width=1080'),
(14, 2, 'Persol PO0649 Steve McQueen', 'Réédition du modèle légendaire porté par Steve McQueen, style vintage.', 289.99, 9, 'Homme', 'https://www.bleather.ma/cdn/shop/files/zephyr-lunettes-de-soleil-de-luxe-noir-908125.png?v=1727198774&width=1080'),
(15, 3, 'Étui à Lunettes Rigide', 'Étui de protection rigide et élégant pour vos lunettes, avec intérieur doux.', 15.00, 100, 'femme', 'https://eyeneedoptic.com/wp-content/uploads/2025/05/Chainette-a-lunettes-perles-blanches-et-dorees-2-1080x1080.jpg'),
(16, 3, 'Chiffon Microfibre Premium', 'Chiffon doux en microfibre pour nettoyer les verres sans laisser de traces.', 5.00, 200, 'femme', 'https://www.cdiscount.com/pdt2/6/7/7/1/700x700/big0729792282677/rw/chaine-de-lunettes-pour-femme-homme-enfant-accesso.jpg'),
(17, 3, 'Cordon à Lunettes Sport', 'Cordon ajustable et résistant, idéal pour maintenir vos lunettes pendant le sport.', 8.50, 75, 'homme', 'https://lnkobrand.com/cdn/shop/files/Design_sans_titre_-_2025-04-16T125545.536.png?v=1744804579'),
(18, 3, 'Kit Nettoyage Verres', 'Kit complet avec spray nettoyant et chiffonnette pour un entretien optimal des verres.', 12.99, 60, 'homme', 'https://shop.ducati.com/media/catalog/product/cache/272aca29c33de9b9841bf2e04fc9a057/1/1/116c79a7235ec492b299ed9a476ff115.jpg'),
(19, 3, 'Étui à Lunettes Rigide', 'Étui de protection rigide et élégant pour vos lunettes, avec intérieur doux.', 15.00, 100, 'femme', 'https://eyeneedoptic.com/wp-content/uploads/2025/05/Chainette-a-lunettes-perles-blanches-et-dorees-2-1080x1080.jpg'),
(20, 3, 'Étui à Lunettes Rigide', 'Étui de protection rigide et élégant pour vos lunettes, avec intérieur doux.', 15.00, 100, 'femme', 'https://eyeneedoptic.com/wp-content/uploads/2025/05/Chainette-a-lunettes-perles-blanches-et-dorees-2-1080x1080.jpg'),
(21, 3, 'Chiffon Microfibre Premium', 'Chiffon doux en microfibre pour nettoyer les verres sans laisser de traces.', 5.00, 200, 'femme', 'https://www.cdiscount.com/pdt2/6/7/7/1/700x700/big0729792282677/rw/chaine-de-lunettes-pour-femme-homme-enfant-accesso.jpg'),
(22, 3, 'Cordon à Lunettes Sport', 'Cordon ajustable et résistant, idéal pour maintenir vos lunettes pendant le sport.', 8.50, 75, 'homme', 'https://lnkobrand.com/cdn/shop/files/Design_sans_titre_-_2025-04-16T125545.536.png?v=1744804579');
