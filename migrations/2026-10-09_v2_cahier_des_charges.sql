-- Hawa Aïcha Boutique — migration v2 (cahier des charges)
-- À appliquer sur une base EXISTANTE (hawa_aicha). Ne supprime aucune donnée.
-- Peut être relancée sans risque : chaque modification vérifie si elle est déjà faite.
-- Faites toujours un export de la base (phpMyAdmin > Exporter) avant de l'importer.

SET NAMES utf8mb4;
USE hawa_aicha;

-- ---------- Administrateurs ----------
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS nom VARCHAR(100) NOT NULL DEFAULT 'Administrateur' AFTER username,
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER nom,
  ADD COLUMN IF NOT EXISTS date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Remplace le mot de passe de démonstration faible « admin123 » par « HawaDemo2026! »
-- (uniquement s'il n'a jamais été changé).
UPDATE admins
   SET password_hash = '$2y$10$z5JFCbnxchAcr38xpBNUueShi5wP.QGjsiRREr2PTyl3QCKaAzqvW'
 WHERE password_hash = '$2y$10$DBfS/O/LO9.9osc7k1HljOYrVARx4OPgAP/66L6nqTAB9FjpBeEMO';

-- Limitation des tentatives de connexion
CREATE TABLE IF NOT EXISTS tentatives_connexion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  date_tentative TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tentative_ip_date (ip, date_tentative)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Catégories ----------
ALTER TABLE categories
  ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER nom;

UPDATE categories SET nom = 'Parfums'         WHERE nom = 'Parfumerie';
UPDATE categories SET nom = 'Beauté et soins' WHERE nom = 'Beauté / soins';
UPDATE categories SET nom = 'Mode et voiles', icone = 'fa-person-dress' WHERE nom = 'Mode';
INSERT INTO categories (nom, icone)
  SELECT 'Vêtements', 'fa-shirt' FROM DUAL
   WHERE NOT EXISTS (SELECT 1 FROM categories WHERE nom = 'Vêtements');

-- ---------- Produits ----------
ALTER TABLE produits
  ADD COLUMN IF NOT EXISTS date_modification TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD INDEX IF NOT EXISTS idx_produits_actif (actif);

-- Le stock et le prix ne peuvent jamais être négatifs
ALTER TABLE produits DROP CONSTRAINT IF EXISTS chk_produits_stock;
ALTER TABLE produits ADD CONSTRAINT chk_produits_stock CHECK (stock >= 0);
ALTER TABLE produits DROP CONSTRAINT IF EXISTS chk_produits_prix;
ALTER TABLE produits ADD CONSTRAINT chk_produits_prix CHECK (prix >= 0);

-- Les six produits de démonstration du cahier des charges.
-- Seules les fiches d'exemple jamais modifiées sont mises à jour.
UPDATE produits SET nom = 'Parfum d''intérieur',
       description = 'Parfum destiné à apporter une senteur agréable à la maison.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Parfums' LIMIT 1)
 WHERE nom = 'Parfum d''intérieur Vanille' AND description LIKE '%Exemple de produit%';
UPDATE produits SET nom = 'Parfum corporel',
       description = 'Parfum pour une utilisation quotidienne.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Parfums' LIMIT 1)
 WHERE nom = 'Parfum corporel Rose Délice' AND description LIKE '%Exemple de produit%';
UPDATE produits SET nom = 'Crème solaire',
       description = 'Produit de protection solaire pour les soins de la peau.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Beauté et soins' LIMIT 1)
 WHERE nom = 'Crème solaire protectrice' AND description LIKE '%Exemple de produit%';
UPDATE produits SET nom = 'Sac à main féminin',
       description = 'Sac élégant pour sorties et usage quotidien.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Sacs et accessoires' LIMIT 1)
 WHERE nom = 'Sac à main élégant' AND description LIKE '%Exemple de produit%';
UPDATE produits SET nom = 'Voile pour femme',
       description = 'Voile élégant pour compléter différentes tenues.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Mode et voiles' LIMIT 1),
       prix = 6000, stock = 10, nouveau = 1
 WHERE nom = 'Sandales confort' AND description LIKE '%Exemple de produit%';
UPDATE produits SET nom = 'Jalabé',
       description = 'Tenue traditionnelle élégante et confortable.',
       categorie_id = (SELECT id FROM categories WHERE nom = 'Vêtements' LIMIT 1),
       stock = 4
 WHERE nom = 'Jalabé brodé' AND description LIKE '%Exemple de produit%';

-- La catégorie d'exemple « Chaussures » n'est plus utilisée : supprimée seulement si elle est vide.
DELETE FROM categories
 WHERE nom = 'Chaussures'
   AND NOT EXISTS (SELECT 1 FROM produits p WHERE p.categorie_id = categories.id);

-- ---------- Clients ----------
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

-- ---------- Commandes ----------
UPDATE commandes SET statut = 'en_preparation' WHERE statut = 'confirmee';
UPDATE commandes SET paiement = 'Espèces' WHERE paiement = 'Paiement à la livraison';

ALTER TABLE commandes
  ADD COLUMN IF NOT EXISTS numero_commande VARCHAR(20) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS client_id INT NULL AFTER numero_commande,
  ADD COLUMN IF NOT EXISTS mode_livraison VARCHAR(50) NOT NULL DEFAULT 'Livraison à domicile' AFTER remarque,
  ADD COLUMN IF NOT EXISTS frais_livraison INT NOT NULL DEFAULT 0 AFTER mode_livraison,
  ADD COLUMN IF NOT EXISTS date_modification TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  ADD UNIQUE INDEX IF NOT EXISTS uq_commandes_numero (numero_commande),
  ADD INDEX IF NOT EXISTS idx_commandes_statut (statut),
  ADD INDEX IF NOT EXISTS idx_commandes_telephone (telephone),
  ADD INDEX IF NOT EXISTS idx_commandes_date (created_at);

ALTER TABLE commandes
  ADD CONSTRAINT fk_commande_client FOREIGN KEY IF NOT EXISTS (client_id) REFERENCES clients(id) ON DELETE SET NULL;

UPDATE commandes
   SET numero_commande = CONCAT('HA-', YEAR(created_at), '-', LPAD(id, 5, '0'))
 WHERE numero_commande IS NULL;

INSERT IGNORE INTO clients (nom_complet, telephone, adresse, ville, date_creation)
  SELECT CONCAT(prenom, ' ', nom), telephone, CONCAT_WS(', ', quartier, adresse), ville, MIN(created_at)
    FROM commandes GROUP BY telephone;
UPDATE commandes c JOIN clients cl ON cl.telephone = c.telephone
   SET c.client_id = cl.id
 WHERE c.client_id IS NULL;

-- ---------- Lignes de commande (= details_commande du cahier des charges) ----------
ALTER TABLE commande_items
  ADD COLUMN IF NOT EXISTS sous_total INT AS (prix * quantite) PERSISTENT;

-- ---------- Paiements ----------
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

-- Un paiement « en attente » pour chaque commande existante qui n'en a pas.
INSERT INTO paiements (commande_id, mode_paiement, montant, statut_paiement, date_creation)
  SELECT c.id, c.paiement, c.total, IF(c.statut = 'annulee', 'echec', 'en_attente'), c.created_at
    FROM commandes c
   WHERE NOT EXISTS (SELECT 1 FROM paiements p WHERE p.commande_id = c.id);
