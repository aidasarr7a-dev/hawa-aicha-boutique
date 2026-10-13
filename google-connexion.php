<?php
/**
 * Bouton « Continuer avec Google » : envoie le client vers la page de connexion Google.
 * Google le renverra ensuite vers connexion.php ou inscription.php (selon la page de départ).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

$retour = google_retour_sur((string)($_GET['retour'] ?? 'mon-compte.php'));
$depuis = ($_GET['depuis'] ?? '') === 'inscription' ? 'inscription' : 'connexion';
if (client_connecte()) {
    redirect(base_url($retour));
}
if (!google_actif()) {
    flash('info', 'La connexion avec Google n\'est pas encore disponible. Utilisez votre téléphone ou votre e-mail.');
    redirect(base_url(GOOGLE_PAGES_RETOUR[$depuis]));
}
redirect(google_url_connexion($retour, $depuis));
