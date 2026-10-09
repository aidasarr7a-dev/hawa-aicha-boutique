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
define('SITE_SLOGAN', 'Qualité, prix accessibles et nouveautés chaque semaine');

// Numéro WhatsApp au format international, sans "+" ni espaces (ex : 221770000000)
define('WHATSAPP_NUMBER', '221770000000');

// Réseaux sociaux
define('TIKTOK_URL', 'https://www.tiktok.com/@hawaaicha');
define('TIKTOK_HANDLE', '@hawaaicha');

// Moyens de paiement proposés au client
define('MODES_PAIEMENT', ['Wave', 'Orange Money', 'Paiement à la livraison']);

// --- Détection automatique du dossier du site (ne pas modifier en général) ---
$__root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$__dir  = realpath(__DIR__) ?: __DIR__;
$__base = ($__root !== '' && strpos($__dir, $__root) === 0) ? substr($__dir, strlen($__root)) : '';
define('BASE_URL', rtrim(str_replace('\\', '/', $__base), '/'));

date_default_timezone_set('Africa/Dakar');
