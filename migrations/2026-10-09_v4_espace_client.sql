-- Hawa Aïcha Boutique — migration v4 : espace client, messages de contact, déclaration de paiement
-- À appliquer APRÈS la v3. Peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

-- ---------- Comptes clients ----------
ALTER TABLE clients
  ADD COLUMN IF NOT EXISTS mot_de_passe_hash VARCHAR(255) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS derniere_connexion DATETIME NULL,
  ADD UNIQUE INDEX IF NOT EXISTS uq_clients_email (email);

-- Les limitations de connexion distinguent l'administration et l'espace client
ALTER TABLE tentatives_connexion
  ADD COLUMN IF NOT EXISTS espace VARCHAR(10) NOT NULL DEFAULT 'admin' AFTER ip;

-- ---------- Messages du formulaire de contact ----------
CREATE TABLE IF NOT EXISTS messages_contact (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  sujet VARCHAR(150) NULL,
  message TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_messages_lu (lu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Paiements : le client déclare avoir payé (la boutique vérifie ensuite) ----------
ALTER TABLE paiements
  ADD COLUMN IF NOT EXISTS reference_client VARCHAR(100) NULL AFTER reference_transaction,
  ADD COLUMN IF NOT EXISTS date_declaration DATETIME NULL AFTER reference_client;

-- ---------- Catalogue : le grand boubou est remplacé par un produit « Maison » ----------
-- (l'historique des commandes conserve le nom et le prix du produit supprimé)
DELETE FROM produits WHERE nom = 'Grand boubou pour homme';

INSERT INTO produits (categorie_id, nom, description, prix, stock, nouveau, image)
SELECT c.id, 'Coussins décoratifs', 'Coussins pour décorer et apporter du confort au salon ou à la chambre.', 6500, 8, 1, '1c329fda442b5e6ae8d6.jpg'
  FROM categories c
 WHERE c.nom = 'Maison'
   AND NOT EXISTS (SELECT 1 FROM produits p WHERE p.nom = 'Coussins décoratifs')
 LIMIT 1;
