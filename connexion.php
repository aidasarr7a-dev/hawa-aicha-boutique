<?php
/**
 * Connexion des clients : formulaire (téléphone ou e-mail + mot de passe) et bouton « Continuer avec Google ».
 * Cette adresse est aussi un « URI de redirection autorisé » dans la console Google Cloud :
 * Google y renvoie le client après son choix de compte (paramètres code + state).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

if (google_est_un_retour($_GET)) {
    google_traiter_retour($_GET);   // redirige toujours
}
$_GET['onglet'] = $_GET['onglet'] ?? 'connexion';
require __DIR__ . '/compte.php';
