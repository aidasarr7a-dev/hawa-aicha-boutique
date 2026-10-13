-- Hawa Aïcha Boutique — migration v5 : rôles des administrateurs (principal / employé)
-- À appliquer APRÈS la v4. Ne supprime aucune donnée et peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

-- ---------- Rôle, activation et dernière connexion ----------
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS role ENUM('principal', 'employe') NOT NULL DEFAULT 'employe' AFTER email,
  ADD COLUMN IF NOT EXISTS actif TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
  ADD COLUMN IF NOT EXISTS derniere_connexion DATETIME NULL AFTER password_hash;

-- Une adresse e-mail ne peut appartenir qu'à un seul compte (plusieurs comptes sans e-mail restent possibles)
UPDATE admins SET email = NULL WHERE email = '';
ALTER TABLE admins
  ADD UNIQUE INDEX IF NOT EXISTS uq_admins_email (email);

-- Les comptes existants avant cette version étaient les comptes de la gérante :
-- s'il n'existe encore aucun administrateur principal, ils le deviennent tous.
UPDATE admins a
  JOIN (SELECT COUNT(*) AS nb FROM admins WHERE role = 'principal') p ON p.nb = 0
   SET a.role = 'principal', a.actif = 1;
