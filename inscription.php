<?php
/**
 * Inscription des clients : formulaire de création de compte et bouton « S'inscrire avec Google ».
 * Cette adresse est aussi un « URI de redirection autorisé » dans la console Google Cloud :
 * Google y renvoie le client après son choix de compte (paramètres code + state).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

if (google_est_un_retour($_GET)) {
    google_traiter_retour($_GET);   // redirige toujours
}
$_GET['onglet'] = 'inscription';
require __DIR__ . '/compte.php';
