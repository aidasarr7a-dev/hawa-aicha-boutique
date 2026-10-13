<?php
/**
 * Configuration de Hawa Aïcha Boutique
 * Modifiez ces valeurs selon votre hébergement.
 */

// --- Base de données (MySQL) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'hawa_aicha');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Boutique ---
define('SITE_NAME', 'Hawa Aïcha Boutique');
define('SITE_SLOGAN', 'L’élégance au quotidien, en gros comme au détail.');

// --- Coordonnées (À COMPLÉTER par la propriétaire) ---
// Tant qu'une valeur est vide, le site affiche « à compléter » au lieu d'inventer une information.
// Numéro WhatsApp au format international, sans "+" ni espaces (ex : 221770000000)
// Numéros de présentation du projet : à remplacer par ceux de la boutique avant la mise en ligne.
define('WHATSAPP_NUMBER', '221785452544');
define('CONTACT_TELEPHONE', '+221 78 545 25 44');
define('CONTACT_EMAIL', 'aidasarr7.a@gmail.com');
define('BOUTIQUE_ADRESSE', 'Golf Sud, arrêt BRT de Golf Sud');
// Position de la boutique sur la carte (arrêt « Golf Sud », Route de Niayes, d'après OpenStreetMap).
// Pour un point plus précis : ouvrez Google Maps, appuyez longuement sur la boutique et recopiez les deux nombres.
define('BOUTIQUE_LAT', 14.76705);
define('BOUTIQUE_LON', -17.41415);
define('ZONE_LIVRAISON', '');         // ex : 'Dakar et banlieue'
define('WAVE_NUMERO', '78 545 25 44');          // numéro Wave qui reçoit les paiements
define('ORANGE_MONEY_NUMERO', '78 545 25 44');  // numéro Orange Money qui reçoit les paiements
// Lien marchand Wave Business, sans montant : le site ajoute « ?amount=<total de la commande> » automatiquement.
// Laissez vide pour revenir au paiement par envoi au numéro Wave ci-dessus.
define('WAVE_LIEN_PAIEMENT', 'https://pay.wave.com/m/M_sn_hUbxqqXvnNNb');

// Réseaux sociaux
define('TIKTOK_URL', 'https://www.tiktok.com/@hawaaicha');
define('TIKTOK_HANDLE', '@hawaaicha');
// Snapchat : compte officiel de la boutique
define('SNAPCHAT_URL', 'https://snapchat.com/t/cEx5F14D');
define('SNAPCHAT_HANDLE', 'hawaaicha');

// Moyens de paiement proposés au client
define('MODES_PAIEMENT', ['Wave', 'Orange Money', 'Espèces']);
// Moyens de paiement en cours de mise en place : affichés « En cours », mais impossibles à choisir.
// Retirez un mode de cette liste pour le réactiver (ex : [] pour tout réactiver).
define('MODES_PAIEMENT_EN_COURS', ['Orange Money']);

// Modes de livraison proposés au client
define('MODES_LIVRAISON', ['Livraison à domicile', 'Retrait en boutique']);

// Seuil d'alerte « à réapprovisionner »
define('SEUIL_STOCK', 3);

// --- Détection automatique du dossier du site (ne pas modifier en général) ---
$__root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$__dir  = realpath(__DIR__) ?: __DIR__;
$__base = ($__root !== '' && strpos($__dir, $__root) === 0) ? substr($__dir, strlen($__root)) : '';
define('BASE_URL', rtrim(str_replace('\\', '/', $__base), '/'));

date_default_timezone_set('Africa/Dakar');
