<?php
/**
 * Retour de Google après « Continuer avec Google ».
 *  - compte déjà lié à ce Google → connexion ;
 *  - compte existant avec la même adresse e-mail (vérifiée par Google) → liaison puis connexion ;
 *  - sinon → dernière étape : le numéro de téléphone (identifiant des commandes de la boutique).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

if (!google_actif()) {
    redirect(base_url('compte.php'));
}

try {
    $profil = google_profil_depuis_retour($_GET);
} catch (RuntimeException $e) {
    flash('danger', e($e->getMessage()));
    redirect(base_url('compte.php'));
}

$pdo = db();
$retour = google_retour_sur($profil['retour']);

// 1. Compte déjà lié à ce compte Google
$st = $pdo->prepare('SELECT * FROM clients WHERE google_id = ?');
$st->execute([$profil['sub']]);
$client = $st->fetch();

// 2. Compte existant avec la même adresse e-mail : on le lie à Google
if (!$client) {
    $st = $pdo->prepare('SELECT * FROM clients WHERE email = ? AND google_id IS NULL');
    $st->execute([$profil['email']]);
    $client = $st->fetch();
    if ($client) {
        // Une fiche « sans compte » devient un vrai compte ; un compte avec mot de passe le garde
        $hash = $client['mot_de_passe_hash'] ?: password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE clients SET google_id = ?, mot_de_passe_hash = ? WHERE id = ?')
            ->execute([$profil['sub'], $hash, $client['id']]);
    }
}

if ($client) {
    google_ouvrir_session_client((int)$client['id']);
    flash('success', 'Bienvenue ' . e(strtok($client['nom_complet'], ' ')) . ' ! Vous êtes connecté(e) avec Google.');
    redirect(base_url($retour));
}

// 3. Nouveau client : il ne reste que le numéro de téléphone à demander
$_SESSION['google_inscription'] = $profil + ['debut' => time()];
redirect(base_url('google-terminer.php'));
