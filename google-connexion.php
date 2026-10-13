<?php
/**
 * Bouton « Continuer avec Google » : envoie le client vers la page de connexion Google.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

$retour = google_retour_sur((string)($_GET['retour'] ?? 'mon-compte.php'));
if (client_connecte()) {
    redirect(base_url($retour));
}
if (!google_actif()) {
    flash('info', 'La connexion avec Google n\'est pas encore disponible. Utilisez votre téléphone ou votre e-mail.');
    redirect(base_url('compte.php'));
}
redirect(google_url_connexion($retour));
