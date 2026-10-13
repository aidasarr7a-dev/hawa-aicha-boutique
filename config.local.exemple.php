<?php
/**
 * MODÈLE de configuration privée — Hawa Aïcha Boutique
 *
 * 1. Copiez ce fichier sous le nom « config.local.php » (dans le même dossier).
 * 2. Remplissez les valeurs ci-dessous.
 * config.local.php n'est jamais enregistré dans Git : vos mots de passe restent sur ce serveur.
 */

// Adresse Gmail de la boutique qui envoie les factures
define('MAIL_UTILISATEUR', 'adresse.de.la.boutique@gmail.com');

// Mot de passe d'application Gmail (16 lettres, sans espaces) — PAS le mot de passe habituel du compte Gmail.
// À créer sur https://myaccount.google.com/apppasswords (la validation en deux étapes doit être activée).
define('MAIL_MOT_DE_PASSE', '');

// Nom affiché comme expéditeur
define('MAIL_NOM_EXPEDITEUR', 'Hawa Aïcha Boutique');

// Clé secrète qui signe les liens « Voir ma facture » envoyés par e-mail (longue chaîne aléatoire).
define('FACTURE_CLE_SECRETE', '');
