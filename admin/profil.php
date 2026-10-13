<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_comptes.php';

$moi = (int)$adminConnecte['id'];
$val = ['nom' => $adminConnecte['nom'], 'username' => $adminConnecte['username'], 'email' => (string)$adminConnecte['email']];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$moi]);
    $hash = (string)$st->fetchColumn();
    $actuel = (string)($_POST['actuel'] ?? '');

    if ($action === 'infos') {
        // Le rôle et l'activation ne font pas partie de ce formulaire : un compte ne peut pas modifier ses propres droits
        $val = champs_compte();
        $erreurs = erreurs_compte($val, $moi);
        if (!password_verify($actuel, $hash)) {
            $erreurs[] = 'Le mot de passe actuel est incorrect : vos informations n\'ont pas été modifiées.';
        }
        if (!$erreurs) {
            db()->prepare('UPDATE admins SET nom = ?, username = ?, email = ? WHERE id = ?')
                ->execute([$val['nom'], $val['username'], $val['email'], $moi]);
            $_SESSION['admin_nom'] = $val['nom'];
            flash('success', 'Vos informations ont été enregistrées. Vous pourrez vous connecter avec « ' . e($val['username']) . ' » ou ' . e($val['email']) . '.');
            redirect('profil.php');
        }
    } elseif ($action === 'mdp') {
        $nouveau = (string)($_POST['nouveau'] ?? '');
        if (!password_verify($actuel, $hash)) {
            $erreurs[] = 'Le mot de passe actuel est incorrect.';
        }
        $erreurs = array_merge($erreurs, erreurs_mot_de_passe($nouveau, (string)($_POST['confirm'] ?? '')));
        if (!$erreurs && password_verify($nouveau, $hash)) {
            $erreurs[] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
        }
        if (!$erreurs) {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $moi]);
            session_regenerate_id(true);
            flash('success', 'Mot de passe modifié. Utilisez-le lors de votre prochaine connexion.');
            redirect('profil.php');
        }
    }
}

$adminPage = 'profil';
$titre = 'Mon profil';
require __DIR__ . '/_header.php';
[$roleLibelle, $roleIcone] = ROLES_ADMIN[$adminConnecte['role']] ?? [$adminConnecte['role'], 'fa-user'];
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Mon profil</h1>
    <p class="admin-sous">Consultez et modifiez les informations de votre compte.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-4">
    <div class="panel profil-carte">
      <div class="profil-avatar"><?= e(mb_strtoupper(mb_substr($adminConnecte['nom'] ?: $adminConnecte['username'], 0, 1))) ?></div>
      <div class="cellule-principale fs-5"><?= e($adminConnecte['nom']) ?></div>
      <span class="badge rounded-pill role-<?= e($adminConnecte['role']) ?> mt-2"><i class="fa-solid <?= $roleIcone ?> me-1"></i><?= e($roleLibelle) ?></span>
      <dl class="admin-fiche text-start mt-4">
        <div><dt>Nom d'utilisateur</dt><dd><?= e($adminConnecte['username']) ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($adminConnecte['email'] ?: '—') ?></dd></div>
        <div><dt>Compte créé le</dt><dd><?= e(date('d/m/Y', strtotime($adminConnecte['date_creation']))) ?></dd></div>
        <div><dt>Dernière connexion</dt><dd><?= $adminConnecte['derniere_connexion'] ? e(date('d/m/Y à H:i', strtotime($adminConnecte['derniere_connexion']))) : '—' ?></dd></div>
      </dl>
    </div>
  </div>

  <div class="col-xl-8 d-grid gap-4">
    <form method="post" class="panel" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="infos">
      <h2 class="h5 mb-1">Informations du compte</h2>
      <p class="text-muted small mb-4">Votre nom d'utilisateur et votre e-mail servent tous deux à vous connecter.</p>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="nom">Nom</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="100" autocomplete="name"></div>
        <div class="col-md-6"><label class="form-label" for="username">Nom d'utilisateur</label><input class="form-control" id="username" name="username" value="<?= e($val['username']) ?>" required maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="username"></div>
        <div class="col-md-6"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= e($val['email']) ?>" required maxlength="150" autocomplete="email"></div>
        <div class="col-md-6"><label class="form-label" for="actuel-infos">Mot de passe actuel <span class="text-muted fw-normal">(pour confirmer)</span></label><input class="form-control" id="actuel-infos" name="actuel" type="password" required autocomplete="current-password"></div>
      </div>
      <div class="admin-form-pied"><button class="btn btn-rose px-4"><i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer mes informations</button></div>
    </form>

    <form method="post" class="panel" id="mot-de-passe">
      <?= csrf_field() ?><input type="hidden" name="action" value="mdp">
      <h2 class="h5 mb-1">Mot de passe</h2>
      <p class="text-muted small mb-4">Au moins <?= MDP_LONGUEUR_MIN ?> caractères, avec au moins une lettre et un chiffre.</p>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="actuel">Mot de passe actuel</label><input class="form-control" id="actuel" name="actuel" type="password" required autocomplete="current-password"></div>
        <div class="col-md-4"><label class="form-label" for="nouveau">Nouveau mot de passe</label><input class="form-control" id="nouveau" name="nouveau" type="password" required minlength="<?= MDP_LONGUEUR_MIN ?>" autocomplete="new-password"></div>
        <div class="col-md-4"><label class="form-label" for="confirm">Confirmation</label><input class="form-control" id="confirm" name="confirm" type="password" required autocomplete="new-password"></div>
      </div>
      <div class="admin-form-pied"><button class="btn btn-rose px-4"><i class="fa-solid fa-key me-1"></i>Changer le mot de passe</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
