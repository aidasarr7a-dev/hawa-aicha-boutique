-- Hawa Aïcha Boutique — structure de la base de données (MySQL)
-- Importez ce fichier dans phpMyAdmin (onglet « Importer »).

CREATE DATABASE IF NOT EXISTS hawa_aicha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hawa_aicha;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
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
  CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  ville VARCHAR(100) NOT NULL,
  quartier VARCHAR(150) NOT NULL,
  adresse VARCHAR(255) NULL,
  remarque TEXT NULL,
  paiement VARCHAR(50) NOT NULL,
  total INT NOT NULL DEFAULT 0,
  statut VARCHAR(20) NOT NULL DEFAULT 'nouvelle',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commande_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  produit_id INT NULL,
  nom_produit VARCHAR(150) NOT NULL,
  prix INT NOT NULL,
  quantite INT NOT NULL,
  CONSTRAINT fk_item_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Administrateur par défaut : identifiant « admin », mot de passe « admin123 »
-- ⚠ À changer dès la première connexion (menu « Mot de passe »).
INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$DBfS/O/LO9.9osc7k1HljOYrVARx4OPgAP/66L6nqTAB9FjpBeEMO');

INSERT INTO categories (nom, icone) VALUES
  ('Parfumerie', 'fa-spray-can-sparkles'),
  ('Beauté / soins', 'fa-heart'),
  ('Sacs et accessoires', 'fa-bag-shopping'),
  ('Chaussures', 'fa-shoe-prints'),
  ('Mode', 'fa-shirt'),
  ('Maison', 'fa-house');

-- Produits d'exemple (à modifier ou supprimer depuis l'espace administrateur)
INSERT INTO produits (categorie_id, nom, description, prix, stock, nouveau) VALUES
  (1, 'Parfum corporel Rose Délice', 'Un parfum corporel floral et doux, longue tenue. Exemple de produit à remplacer.', 3500, 12, 1),
  (1, 'Parfum d''intérieur Vanille', 'Parfum pour la maison, ambiance chaleureuse. Exemple de produit à remplacer.', 4000, 8, 0),
  (2, 'Crème solaire protectrice', 'Protège et hydrate la peau au quotidien. Exemple de produit à remplacer.', 5000, 15, 1),
  (3, 'Sac à main élégant', 'Sac tendance, spacieux et résistant. Exemple de produit à remplacer.', 9500, 5, 1),
  (4, 'Sandales confort', 'Chaussures légères pour tous les jours. Exemple de produit à remplacer.', 7000, 3, 0),
  (5, 'Jalabé brodé', 'Jalabé élégant, belle coupe. Exemple de produit à remplacer.', 12000, 0, 0);
