-- Hawa Aïcha Boutique — migration v7 : envoi de la facture par e-mail
-- À appliquer APRÈS la v6. Ne supprime aucune donnée et peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

ALTER TABLE commandes
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER telephone,
  ADD COLUMN IF NOT EXISTS facture_envoyee_le DATETIME NULL;
