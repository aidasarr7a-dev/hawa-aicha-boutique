-- Hawa Aïcha Boutique — migration v8 : connexion des clients avec Google
-- À appliquer APRÈS la v7. Ne supprime aucune donnée et peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

-- Identifiant Google (« sub ») du compte client : un compte Google ne peut être lié qu'à un seul client
ALTER TABLE clients
  ADD COLUMN IF NOT EXISTS google_id VARCHAR(64) NULL AFTER mot_de_passe_hash,
  ADD UNIQUE INDEX IF NOT EXISTS uq_clients_google (google_id);
