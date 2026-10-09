-- Hawa Aïcha Boutique — migration v3 : catalogue de 14 produits, formulaire de commande simplifié
-- À appliquer APRÈS la migration v2. Ne supprime aucune donnée et peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

-- ---------- Commandes : nom complet + adresse de livraison ----------
ALTER TABLE commandes
  ADD COLUMN IF NOT EXISTS nom_complet VARCHAR(200) NOT NULL DEFAULT '' AFTER client_id,
  ADD COLUMN IF NOT EXISTS adresse_livraison VARCHAR(255) NULL AFTER ville,
  MODIFY nom VARCHAR(100) NOT NULL DEFAULT '',
  MODIFY prenom VARCHAR(100) NOT NULL DEFAULT '',
  MODIFY quartier VARCHAR(150) NOT NULL DEFAULT '';

-- Reprise des commandes existantes (anciens champs prénom / nom / quartier / repère conservés)
UPDATE commandes SET nom_complet = TRIM(CONCAT(prenom, ' ', nom)) WHERE nom_complet = '';
UPDATE commandes SET adresse_livraison = CONCAT_WS(', ', NULLIF(quartier, ''), NULLIF(adresse, ''))
 WHERE adresse_livraison IS NULL;

-- ---------- Catégorie Chaussures ----------
INSERT INTO categories (nom, icone)
  SELECT 'Chaussures', 'fa-shoe-prints' FROM DUAL
   WHERE NOT EXISTS (SELECT 1 FROM categories WHERE nom = 'Chaussures');

-- ---------- Huit produits supplémentaires (prix de démonstration, modifiables dans l'administration) ----------
INSERT INTO produits (categorie_id, nom, description, prix, stock, nouveau, image)
SELECT c.id, n.nom, n.description, n.prix, n.stock, n.nouveau, n.image
  FROM (
    SELECT 'Parfums' AS cat, 'Encens traditionnel' AS nom, 'Encens à brûler pour parfumer agréablement la maison.' AS description, 2500 AS prix, 20 AS stock, 1 AS nouveau, '242fa0978b3b994e02ed.jpg' AS image
    UNION ALL SELECT 'Beauté et soins', 'Lait corporel', 'Lait pour le soin quotidien du corps.', 4500, 12, 0, '99ddb9b86ebb96d8c75c.jpg'
    UNION ALL SELECT 'Beauté et soins', 'Huile de soin', 'Huile pour le soin quotidien de la peau.', 3500, 10, 1, 'ec7fe0bba75bd9204fcd.jpg'
    UNION ALL SELECT 'Sacs et accessoires', 'Pochette de soirée', 'Petite pochette élégante pour les sorties et les cérémonies.', 7500, 6, 1, 'd987633b1de1f353877d.jpg'
    UNION ALL SELECT 'Sacs et accessoires', 'Bijoux fantaisie dorés', 'Collier et bracelets fantaisie de couleur dorée.', 5000, 8, 0, 'a5c143c32c3e706fa2b0.jpg'
    UNION ALL SELECT 'Sacs et accessoires', 'Lunettes de soleil', 'Lunettes de soleil au style rétro.', 3000, 15, 0, '57a663074db811848997.jpg'
    UNION ALL SELECT 'Chaussures', 'Sandales pour femme', 'Sandales confortables pour tous les jours.', 8000, 3, 1, '8a72f390a1f279db2b22.jpg'
    UNION ALL SELECT 'Vêtements', 'Grand boubou pour homme', 'Tenue traditionnelle élégante pour homme.', 15000, 4, 0, 'de52cce6d36525caa9ab.jpg'
  ) AS n
  JOIN categories c ON c.nom = n.cat
 WHERE NOT EXISTS (SELECT 1 FROM produits p WHERE p.nom = n.nom);
