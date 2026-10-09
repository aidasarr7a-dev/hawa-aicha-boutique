<?php
/**
 * Espace client : connexion et création de compte.
 * Identifiant : numéro de téléphone (ou e-mail) + mot de passe.
 */
require_once __DIR__ . '/includes/functions.php';

// Page de retour après connexion : uniquement une page locale du site
$retour = (string)($_GET['retour'] ?? $_POST['retour'] ?? 'mon-compte.php');
if (!preg_match('/^[a-z_-]+\.php(\?[\w=&%.+-]*)?$/i', $retour)) {
    $retour = 'mon-compte.php';
}
if (client_connecte()) {
    redirect(base_url($retour));
}

$onglet = ($_GET['onglet'] ?? $_POST['action'] ?? '') === 'inscription' ? 'inscription' : 'connexion';
$erreurs = [];
$val = ['identifiant' => '', 'nom_complet' => '', 'telephone' => '', 'email' => ''];

function ouvrir_session_client(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['client_id'] = $id;
    db()->prepare('UPDATE clients SET derniere_connexion = NOW() WHERE id = ?')->execute([$id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

    if ($onglet === 'connexion') {
        $val['identifiant'] = trim((string)($_POST['identifiant'] ?? ''));
        $mdp = (string)($_POST['mot_de_passe'] ?? '');
        $nb = $pdo->prepare("SELECT COUNT(*) FROM tentatives_connexion WHERE ip = ? AND espace = 'client' AND date_tentative > NOW() - INTERVAL 15 MINUTE");
        $nb->execute([$ip]);
        if ((int)$nb->fetchColumn() >= 8) {
            $erreurs[] = 'Trop de tentatives. Merci de patienter 15 minutes avant de réessayer.';
        } else {
            $tel = normaliser_telephone($val['identifiant']);
            $st = $pdo->prepare('SELECT * FROM clients WHERE mot_de_passe_hash IS NOT NULL AND (telephone = ? OR (email IS NOT NULL AND email = ?)) LIMIT 1');
            $st->execute([$tel, mb_strtolower($val['identifiant'])]);
            $client = $st->fetch();
            if ($client && password_verify($mdp, $client['mot_de_passe_hash'])) {
                $pdo->prepare("DELETE FROM tentatives_connexion WHERE ip = ? AND espace = 'client'")->execute([$ip]);
                ouvrir_session_client((int)$client['id']);
                flash('success', 'Bienvenue ' . e(strtok($client['nom_complet'], ' ')) . ' !');
                redirect(base_url($retour));
            }
            $pdo->prepare("INSERT INTO tentatives_connexion (ip, espace) VALUES (?, 'client')")->execute([$ip]);
            $erreurs[] = 'Téléphone / e-mail ou mot de passe incorrect.';
        }
    } else {
        foreach (['nom_complet', 'telephone', 'email'] as $k) {
            $val[$k] = trim((string)($_POST[$k] ?? ''));
        }
        $val['email'] = mb_strtolower($val['email']);
        $tel = normaliser_telephone($val['telephone']);
        $mdp = (string)($_POST['mot_de_passe'] ?? '');
        $confirm = (string)($_POST['confirmation'] ?? '');

        if (mb_strlen($val['nom_complet']) < 3 || mb_strlen($val['nom_complet']) > 200) $erreurs[] = 'Veuillez indiquer votre nom complet.';
        if (strlen(preg_replace('/\D/', '', $tel)) < 8 || strlen($tel) > 20)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone valide.';
        if ($val['email'] !== '' && !filter_var($val['email'], FILTER_VALIDATE_EMAIL))     $erreurs[] = 'L\'adresse e-mail n\'est pas valide.';
        if (strlen($mdp) < 8)                                                               $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        if ($mdp !== $confirm)                                                              $erreurs[] = 'Les deux mots de passe ne correspondent pas.';

        if (!$erreurs) {
            $st = $pdo->prepare('SELECT * FROM clients WHERE telephone = ?');
            $st->execute([$tel]);
            $existant = $st->fetch();
            if ($existant && $existant['mot_de_passe_hash']) {
                $erreurs[] = 'Un compte existe déjà avec ce numéro. Connectez-vous.';
                $onglet = 'connexion';
                $val['identifiant'] = $val['telephone'];
            }
            if (!$erreurs && $val['email'] !== '') {
                $st = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE email = ? AND telephone <> ?');
                $st->execute([$val['email'], $tel]);
                if ((int)$st->fetchColumn() > 0) $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
            }
        }

        if (!$erreurs) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            if ($existant) {
                // Le client avait déjà commandé sans compte : on active son compte et il retrouve ses commandes
                $pdo->prepare('UPDATE clients SET nom_complet = ?, email = ?, mot_de_passe_hash = ? WHERE id = ?')
                    ->execute([$val['nom_complet'], $val['email'] ?: null, $hash, $existant['id']]);
                $id = (int)$existant['id'];
            } else {
                $pdo->prepare('INSERT INTO clients (nom_complet, telephone, email, mot_de_passe_hash) VALUES (?, ?, ?, ?)')
                    ->execute([$val['nom_complet'], $tel, $val['email'] ?: null, $hash]);
                $id = (int)$pdo->lastInsertId();
            }
            ouvrir_session_client($id);
            flash('success', 'Votre compte est créé. Bienvenue ' . e(strtok($val['nom_complet'], ' ')) . ' !');
            redirect(base_url($retour));
        }
    }
}

$page = 'compte';
$titre = $onglet === 'inscription' ? 'Créer mon compte' : 'Connexion';
require __DIR__ . '/includes/header.php';
?>

<div class="compte-wrap">
  <div class="text-center mb-4">
    <span class="eyebrow-simple"><i class="fa-solid fa-user me-1"></i> Espace client</span>
    <h1 class="page-title mt-2 mb-1"><?= $onglet === 'inscription' ? 'Créer mon compte' : 'Se connecter' ?></h1>
    <p class="text-muted mb-0">Suivez vos commandes, vos paiements et téléchargez vos factures.</p>
  </div>

  <div class="onglets mb-3">
    <a class="<?= $onglet === 'connexion' ? 'on' : '' ?>" href="compte.php?retour=<?= e(rawurlencode($retour)) ?>">Connexion</a>
    <a class="<?= $onglet === 'inscription' ? 'on' : '' ?>" href="compte.php?onglet=inscription&amp;retour=<?= e(rawurlencode($retour)) ?>">Créer un compte</a>
  </div>

  <?php if ($erreurs): ?>
    <div class="alert alert-danger rounded-4"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="panel">
    <?php if ($onglet === 'connexion'): ?>
      <form method="post" data-envoi-unique>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="connexion">
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <div class="mb-3"><label class="form-label" for="identifiant">Téléphone ou e-mail</label>
          <input class="form-control" id="identifiant" name="identifiant" value="<?= e($val['identifiant']) ?>" required autocomplete="username" placeholder="Ex : 77 000 00 00"></div>
        <div class="mb-4"><label class="form-label" for="mot_de_passe">Mot de passe</label>
          <input class="form-control" id="mot_de_passe" name="mot_de_passe" type="password" required autocomplete="current-password"></div>
        <button class="btn btn-rose btn-lg w-100">Se connecter</button>
      </form>
      <p class="text-center small text-muted mt-3 mb-0">Pas encore de compte ? <a href="compte.php?onglet=inscription&amp;retour=<?= e(rawurlencode($retour)) ?>">Créez-le en 1 minute</a></p>
    <?php else: ?>
      <form method="post" data-envoi-unique>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="inscription">
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <div class="mb-3"><label class="form-label" for="nom_complet">Nom complet *</label>
          <input class="form-control" id="nom_complet" name="nom_complet" value="<?= e($val['nom_complet']) ?>" required maxlength="200" autocomplete="name"></div>
        <div class="mb-3"><label class="form-label" for="telephone">Numéro de téléphone *</label>
          <input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" value="<?= e($val['telephone']) ?>" required autocomplete="tel" placeholder="Ex : 77 000 00 00"></div>
        <div class="mb-3"><label class="form-label" for="email">E-mail (facultatif)</label>
          <input class="form-control" id="email" name="email" type="email" value="<?= e($val['email']) ?>" maxlength="150" autocomplete="email"></div>
        <div class="row g-3 mb-4">
          <div class="col-sm-6"><label class="form-label" for="mdp">Mot de passe * <span class="text-muted small">(8 caractères min.)</span></label>
            <input class="form-control" id="mdp" name="mot_de_passe" type="password" required minlength="8" autocomplete="new-password"></div>
          <div class="col-sm-6"><label class="form-label" for="confirmation">Confirmation *</label>
            <input class="form-control" id="confirmation" name="confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
        </div>
        <button class="btn btn-rose btn-lg w-100">Créer mon compte</button>
        <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Vous avez déjà commandé sans compte avec ce numéro ? Vos commandes apparaîtront automatiquement dans votre espace.</p>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
