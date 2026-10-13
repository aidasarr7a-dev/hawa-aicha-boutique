<?php
/**
 * Dernière étape d'une inscription avec Google : nom et numéro de téléphone.
 * Le téléphone est l'identifiant des commandes de la boutique : si le client avait déjà commandé
 * sans compte avec ce numéro, son compte est activé et il retrouve ses commandes et factures.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google.php';

$g = $_SESSION['google_inscription'] ?? null;
if (!$g || time() - (int)$g['debut'] > 1800) {
    unset($_SESSION['google_inscription']);
    flash('info', 'Merci de recommencer la connexion avec Google.');
    redirect(base_url('compte.php'));
}
if (client_connecte()) {
    unset($_SESSION['google_inscription']);
    redirect(base_url('mon-compte.php'));
}

$val = ['nom_complet' => $g['nom'], 'telephone' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $val['nom_complet'] = trim((string)($_POST['nom_complet'] ?? ''));
    $val['telephone'] = trim((string)($_POST['telephone'] ?? ''));
    $tel = normaliser_telephone($val['telephone']);

    if (mb_strlen($val['nom_complet']) < 3 || mb_strlen($val['nom_complet']) > 200) $erreurs[] = 'Veuillez indiquer votre nom complet.';
    if (strlen(preg_replace('/\D/', '', $tel)) < 8 || strlen($tel) > 20)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone valide.';

    $pdo = db();
    $existant = null;
    if (!$erreurs) {
        $st = $pdo->prepare('SELECT * FROM clients WHERE telephone = ?');
        $st->execute([$tel]);
        $existant = $st->fetch() ?: null;
        if ($existant && $existant['mot_de_passe_hash']) {
            $erreurs[] = 'Un compte existe déjà avec ce numéro. Connectez-vous avec votre téléphone et votre mot de passe.';
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE (email = ? OR google_id = ?) AND telephone <> ?');
        $st->execute([$g['email'], $g['sub'], $tel]);
        if ((int)$st->fetchColumn() > 0) {
            $erreurs[] = 'Ce compte Google est déjà associé à un autre client. Connectez-vous avec ce compte.';
        }
    }

    if (!$erreurs) {
        // Pas de mot de passe pour un compte Google : empreinte aléatoire inutilisable (connexion par Google uniquement)
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        if ($existant) {
            // Le client avait déjà commandé sans compte avec ce numéro : son compte est activé
            $pdo->prepare('UPDATE clients SET nom_complet = ?, email = ?, google_id = ?, mot_de_passe_hash = ? WHERE id = ?')
                ->execute([$val['nom_complet'], $g['email'], $g['sub'], $hash, $existant['id']]);
            $id = (int)$existant['id'];
        } else {
            $pdo->prepare('INSERT INTO clients (nom_complet, telephone, email, google_id, mot_de_passe_hash) VALUES (?, ?, ?, ?, ?)')
                ->execute([$val['nom_complet'], $tel, $g['email'], $g['sub'], $hash]);
            $id = (int)$pdo->lastInsertId();
        }
        $retour = google_retour_sur($g['retour']);
        unset($_SESSION['google_inscription']);
        google_ouvrir_session_client($id);
        flash('success', 'Votre compte est créé avec Google. Bienvenue ' . e(strtok($val['nom_complet'], ' ')) . ' !');
        redirect(base_url($retour));
    }
}

$page = 'compte';
$titre = 'Terminer mon inscription';
require __DIR__ . '/includes/header.php';
?>

<div class="compte-wrap">
  <div class="text-center mb-4">
    <span class="eyebrow-simple"><i class="fa-brands fa-google me-1"></i> Inscription avec Google</span>
    <h1 class="page-title mt-2 mb-1">Dernière étape</h1>
    <p class="text-muted mb-0">Indiquez votre numéro de téléphone : il sert à suivre vos commandes et à vous livrer.</p>
  </div>

  <div class="panel">
    <div class="google-compte mb-4">
      <span class="google-compte-ic"><i class="fa-brands fa-google"></i></span>
      <span class="min-w-0"><span class="d-block small text-muted">Compte Google</span><strong class="text-break"><?= e($g['email']) ?></strong></span>
    </div>
    <form method="post" data-envoi-unique>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label" for="nom_complet">Nom complet</label>
        <input class="form-control" id="nom_complet" name="nom_complet" value="<?= e($val['nom_complet']) ?>" required maxlength="200" autocomplete="name"></div>
      <div class="mb-4"><label class="form-label" for="telephone">Numéro de téléphone</label>
        <input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" value="<?= e($val['telephone']) ?>" required autocomplete="tel" placeholder="Ex : 77 000 00 00"></div>
      <button class="btn btn-rose btn-lg w-100">Créer mon compte</button>
      <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Vous avez déjà commandé sans compte avec ce numéro ? Vos commandes et factures apparaîtront automatiquement dans votre espace.</p>
    </form>
  </div>
  <p class="text-center small mt-3"><a href="<?= e(base_url('compte.php')) ?>">Annuler et revenir à la connexion</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
