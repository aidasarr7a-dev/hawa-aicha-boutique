-- Hawa Aïcha Boutique — migration v6 : prénom et téléphone des administrateurs
-- À appliquer APRÈS la v5. Ne supprime aucune donnée et peut être relancée sans risque.
-- La colonne « nom » existante est conservée telle quelle (elle sert désormais de nom de famille).

SET NAMES utf8mb4;
USE hawa_aicha;

ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS prenom VARCHAR(100) NULL AFTER username,
  ADD COLUMN IF NOT EXISTS telephone VARCHAR(30) NULL AFTER email;
