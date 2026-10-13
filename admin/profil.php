<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_comptes.php';

$moi = (int)$adminConnecte['id'];
$val = [
    'prenom'    => (string)$adminConnecte['prenom'],
    'nom'       => (string)$adminConnecte['nom'],
    'username'  => (string)$adminConnecte['username'],
    'email'     => (string)$adminConnecte['email'],
    'telephone' => (string)$adminConnecte['telephone'],
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Le rôle et l'activation ne font pas partie de ce formulaire : un compte ne peut pas modifier ses propres droits
    $val = champs_compte();
    $erreurs = erreurs_compte($val, $moi);

    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$moi]);
    $hash = (string)$st->fetchColumn();
    $actuel = (string)($_POST['actuel'] ?? '');
    $nouveau = (string)($_POST['nouveau'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    // Champs du mot de passe laissés vides = mot de passe inchangé
    $changeMdp = $nouveau !== '' || $confirm !== '';
    if ($changeMdp) {
        $erreurs = array_merge($erreurs, erreurs_mot_de_passe($nouveau, $confirm));
        if (password_verify($nouveau, $hash)) {
            $erreurs[] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
        }
    }
    // Les identifiants de connexion et le mot de passe ne changent qu'avec le mot de passe actuel
    $changeConnexion = strcasecmp($val['username'], (string)$adminConnecte['username']) !== 0
                    || strcasecmp($val['email'], (string)$adminConnecte['email']) !== 0;
    if (($changeMdp || $changeConnexion) && !password_verify($actuel, $hash)) {
        $erreurs[] = $actuel === ''
            ? 'Saisissez votre mot de passe actuel pour modifier votre identifiant, votre e-mail ou votre mot de passe.'
            : 'Le mot de passe actuel est incorrect : aucune modification n\'a été enregistrée.';
    }

    if (!$erreurs) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE admins SET prenom = ?, nom = ?, username = ?, email = ?, telephone = ? WHERE id = ?')
            ->execute([$val['prenom'], $val['nom'], $val['username'], $val['email'], $val['telephone'] ?: null, $moi]);
        if ($changeMdp) {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($nouveau, PASSWORD_DEFAULT), $moi]);
        }
        $pdo->commit();
        if ($changeMdp) {
            session_regenerate_id(true);
        }
        flash('success', 'Votre profil a été enregistré' . ($changeMdp ? ', avec votre nouveau mot de passe' : '') . '.');
        redirect('profil.php');
    }
}

$adminPage = 'profil';
$titre = 'Mon profil';
require __DIR__ . '/_header.php';
[$roleLibelle, $roleIcone] = ROLES_ADMIN[$adminConnecte['role']] ?? [$adminConnecte['role'], 'fa-user'];
$nomAffiche = nom_complet_admin($adminConnecte);
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
      <div class="profil-avatar"><?= e(mb_strtoupper(mb_substr($nomAffiche, 0, 1))) ?></div>
      <div class="cellule-principale fs-5"><?= e($nomAffiche) ?></div>
      <span class="badge rounded-pill role-<?= e($adminConnecte['role']) ?> mt-2"><i class="fa-solid <?= $roleIcone ?> me-1"></i><?= e($roleLibelle) ?></span>
      <dl class="admin-fiche text-start mt-4">
        <div><dt>Nom d'utilisateur</dt><dd><?= e($adminConnecte['username']) ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($adminConnecte['email'] ?: '—') ?></dd></div>
        <div><dt>Téléphone</dt><dd><?= e($adminConnecte['telephone'] ?: '—') ?></dd></div>
        <div><dt>Compte créé le</dt><dd><?= e(date('d/m/Y', strtotime($adminConnecte['date_creation']))) ?></dd></div>
        <div><dt>Dernière connexion</dt><dd><?= $adminConnecte['derniere_connexion'] ? e(date('d/m/Y à H:i', strtotime($adminConnecte['derniere_connexion']))) : '—' ?></dd></div>
      </dl>
    </div>
  </div>

  <div class="col-xl-8">
    <form method="post" class="panel profil-form" autocomplete="off">
      <?= csrf_field() ?>

      <section class="profil-bloc">
        <h2 class="h5"><i class="fa-solid fa-id-card"></i>Identité</h2>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="prenom">Prénom</label><input class="form-control" id="prenom" name="prenom" value="<?= e($val['prenom']) ?>" required maxlength="100" autocomplete="given-name"></div>
          <div class="col-md-6"><label class="form-label" for="nom">Nom</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="100" autocomplete="family-name"></div>
          <div class="col-md-6"><label class="form-label" for="telephone">Téléphone <span class="text-muted fw-normal">(facultatif)</span></label><input class="form-control" id="telephone" name="telephone" type="tel" value="<?= e($val['telephone']) ?>" maxlength="30" autocomplete="tel" placeholder="Ex : 77 123 45 67"></div>
        </div>
      </section>

      <section class="profil-bloc">
        <h2 class="h5"><i class="fa-solid fa-right-to-bracket"></i>Connexion</h2>
        <p class="text-muted small">Votre nom d'utilisateur et votre e-mail servent tous deux à vous connecter.</p>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="username">Nom d'utilisateur</label><input class="form-control" id="username" name="username" value="<?= e($val['username']) ?>" required maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="username"></div>
          <div class="col-md-6"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= e($val['email']) ?>" required maxlength="150" autocomplete="email"></div>
        </div>
      </section>

      <section class="profil-bloc">
        <h2 class="h5"><i class="fa-solid fa-key"></i>Mot de passe</h2>
        <p class="text-muted small">Laissez ces deux champs vides pour garder votre mot de passe actuel. Sinon : <?= MDP_LONGUEUR_MIN ?> caractères minimum, avec au moins une lettre et un chiffre.</p>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="nouveau">Nouveau mot de passe</label><?= champ_mot_de_passe('nouveau', 'nouveau', 'new-password', false, 'minlength="' . MDP_LONGUEUR_MIN . '"') ?></div>
          <div class="col-md-6"><label class="form-label" for="confirm">Confirmation</label><?= champ_mot_de_passe('confirm', 'confirm', 'new-password') ?></div>
        </div>
      </section>

      <section class="profil-bloc profil-confirmation">
        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label" for="actuel">Mot de passe actuel</label>
            <?= champ_mot_de_passe('actuel', 'actuel', 'current-password') ?>
          </div>
          <div class="col-md-6"><p class="text-muted small mb-md-2 mb-0"><i class="fa-solid fa-shield-halved me-1"></i>Obligatoire uniquement pour changer d'identifiant, d'e-mail ou de mot de passe.</p></div>
        </div>
      </section>

      <div class="admin-form-pied"><button class="btn btn-rose px-4"><i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer mon profil</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
