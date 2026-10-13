<?php
/**
 * Ancienne adresse de retour de Google, conservée par compatibilité.
 * Les adresses à déclarer dans la console Google sont désormais connexion.php et inscription.php.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

google_traiter_retour($_GET);
