-- Hawa Aïcha Boutique — structure complète de la base de données (MySQL / MariaDB)
-- INSTALLATION NEUVE uniquement : importez ce fichier dans phpMyAdmin (onglet « Importer »).
-- Si la base « hawa_aicha » existe déjà, NE réimportez PAS ce fichier :
-- utilisez les scripts du dossier migrations/ (ils ne suppriment aucune donnée).

SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS hawa_aicha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hawa_aicha;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL DEFAULT 'Administrateur',
  email VARCHAR(150) NULL,
  password_hash VARCHAR(255) NOT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tentatives_connexion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  date_tentative TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tentative_ip_date (ip, date_tentative)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  description TEXT NULL,
  icone VARCHAR(50) NOT NULL DEFAULT 'fa-gem',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categorie_id INT NULL,
  nom VARCHAR(150) NOT NULL,
  description TEXT NULL,
  prix INT NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  image VARCHAR(120) NULL,
  nouveau TINYINT(1) NOT NULL DEFAULT 0,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_produits_actif (actif),
  CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT chk_produits_stock CHECK (stock >= 0),
  CONSTRAINT chk_produits_prix CHECK (prix >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_complet VARCHAR(200) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  adresse VARCHAR(255) NULL,
  ville VARCHAR(100) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_clients_telephone (telephone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero_commande VARCHAR(20) NULL,
  client_id INT NULL,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  ville VARCHAR(100) NOT NULL,
  quartier VARCHAR(150) NOT NULL,
  adresse VARCHAR(255) NULL,
  remarque TEXT NULL,
  mode_livraison VARCHAR(50) NOT NULL DEFAULT 'Livraison à domicile',
  frais_livraison INT NOT NULL DEFAULT 0,
  paiement VARCHAR(50) NOT NULL,
  total INT NOT NULL DEFAULT 0,
  statut VARCHAR(20) NOT NULL DEFAULT 'nouvelle',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX uq_commandes_numero (numero_commande),
  INDEX idx_commandes_statut (statut),
  INDEX idx_commandes_telephone (telephone),
  INDEX idx_commandes_date (created_at),
  CONSTRAINT fk_commande_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lignes de commande (« details_commande » du cahier des charges) :
-- le nom et le prix sont copiés au moment de la commande.
CREATE TABLE IF NOT EXISTS commande_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  produit_id INT NULL,
  nom_produit VARCHAR(150) NOT NULL,
  prix INT NOT NULL,
  quantite INT NOT NULL,
  sous_total INT AS (prix * quantite) PERSISTENT,
  CONSTRAINT fk_item_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS paiements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  mode_paiement VARCHAR(50) NOT NULL,
  montant INT NOT NULL DEFAULT 0,
  statut_paiement VARCHAR(20) NOT NULL DEFAULT 'en_attente',
  reference_transaction VARCHAR(100) NULL,
  note VARCHAR(255) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_paiement DATETIME NULL,
  date_confirmation DATETIME NULL,
  confirme_par INT NULL,
  UNIQUE KEY uq_paiements_commande (commande_id),
  INDEX idx_paiements_statut (statut_paiement),
  CONSTRAINT fk_paiement_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_paiement_admin FOREIGN KEY (confirme_par) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Administrateur de démonstration : identifiant « admin », mot de passe « HawaDemo2026! »
-- ⚠ À changer dès la première connexion (menu « Mot de passe »).
INSERT INTO admins (username, nom, password_hash) VALUES
  ('admin', 'Administrateur', '$2y$10$z5JFCbnxchAcr38xpBNUueShi5wP.QGjsiRREr2PTyl3QCKaAzqvW');

INSERT INTO categories (id, nom, icone) VALUES
  (1, 'Parfums', 'fa-spray-can-sparkles'),
  (2, 'Beauté et soins', 'fa-heart'),
  (3, 'Sacs et accessoires', 'fa-bag-shopping'),
  (4, 'Mode et voiles', 'fa-person-dress'),
  (5, 'Vêtements', 'fa-shirt'),
  (6, 'Maison', 'fa-house');

-- Les six produits de démonstration (prix de démonstration, à modifier dans l'administration)
INSERT INTO produits (categorie_id, nom, description, prix, stock, nouveau) VALUES
  (1, 'Parfum d''intérieur', 'Parfum destiné à apporter une senteur agréable à la maison.', 4000, 8, 0),
  (1, 'Parfum corporel', 'Parfum pour une utilisation quotidienne.', 3500, 12, 1),
  (2, 'Crème solaire', 'Produit de protection solaire pour les soins de la peau.', 5000, 15, 1),
  (3, 'Sac à main féminin', 'Sac élégant pour sorties et usage quotidien.', 9500, 5, 1),
  (4, 'Voile pour femme', 'Voile élégant pour compléter différentes tenues.', 6000, 10, 1),
  (5, 'Jalabé', 'Tenue traditionnelle élégante et confortable.', 12000, 4, 0);
