<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_comptes.php';

// Page entière réservée à l'administrateur principal (vérifié à chaque affichage et à chaque action)
exiger_principal('index.php', 'La gestion des administrateurs est réservée à l\'administrateur principal.');

$pdo = db();
$moi = (int)$adminConnecte['id'];

function charger_admin(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, username, nom, email, role, actif FROM admins WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

$erreurs = [];
$editId = (int)($_GET['modifier'] ?? (($_POST['action'] ?? '') === 'modifier' ? ($_POST['id'] ?? 0) : 0));
$cible = $editId ? charger_admin($pdo, $editId) : null;
if ($editId && !$cible) {
    flash('danger', 'Ce compte administrateur n\'existe plus.');
    redirect('administrateurs.php');
}
if ($cible && (int)$cible['id'] === $moi) {
    // Ses propres informations se modifient dans « Mon profil » ; son propre rôle ne se modifie pas
    flash('info', 'Vos propres informations se modifient depuis « Mon profil ».');
    redirect('profil.php');
}
$val = $cible
    ? ['nom' => $cible['nom'], 'username' => $cible['username'], 'email' => (string)$cible['email'], 'role' => $cible['role']]
    : ['nom' => '', 'username' => '', 'email' => '', 'role' => 'employe'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter' || $action === 'modifier') {
        $val = champs_compte() + ['role' => $_POST['role'] ?? ''];
        if (!isset(ROLES_ADMIN[$val['role']])) {
            $erreurs[] = 'Rôle invalide.';
        }
        $erreurs = array_merge($erreurs, erreurs_compte($val, $action === 'modifier' ? (int)$cible['id'] : 0));
        $mdp = (string)($_POST['mdp'] ?? '');
        if ($action === 'ajouter' || $mdp !== '') {
            $erreurs = array_merge($erreurs, erreurs_mot_de_passe($mdp, (string)($_POST['mdp_confirm'] ?? '')));
        }
        if ($action === 'modifier' && $cible['role'] === 'principal' && $val['role'] !== 'principal'
            && (int)$cible['actif'] && nb_principaux_actifs() <= 1) {
            $erreurs[] = 'Impossible de retirer le rôle principal au dernier administrateur principal actif.';
        }

        if (!$erreurs && $action === 'ajouter') {
            $pdo->prepare('INSERT INTO admins (username, nom, email, role, actif, password_hash) VALUES (?, ?, ?, ?, 1, ?)')
                ->execute([$val['username'], $val['nom'], $val['email'], $val['role'], password_hash($mdp, PASSWORD_DEFAULT)]);
            flash('success', 'Compte créé pour ' . e($val['nom']) . ' (' . e(ROLES_ADMIN[$val['role']][0]) . '). Identifiant de connexion : « ' . e($val['username']) . ' ».');
            redirect('administrateurs.php');
        }
        if (!$erreurs && $action === 'modifier') {
            $pdo->prepare('UPDATE admins SET nom = ?, username = ?, email = ?, role = ? WHERE id = ?')
                ->execute([$val['nom'], $val['username'], $val['email'], $val['role'], $cible['id']]);
            if ($mdp !== '') {
                $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($mdp, PASSWORD_DEFAULT), $cible['id']]);
            }
            flash('success', 'Compte de ' . e($val['nom']) . ' modifié' . ($mdp !== '' ? ', avec un nouveau mot de passe' : '') . '.');
            redirect('administrateurs.php');
        }
    } elseif ($action === 'basculer' || $action === 'supprimer') {
        $c = charger_admin($pdo, (int)($_POST['id'] ?? 0));
        if (!$c) {
            flash('danger', 'Ce compte administrateur n\'existe plus.');
        } elseif ((int)$c['id'] === $moi) {
            flash('danger', 'Vous ne pouvez pas désactiver ni supprimer votre propre compte.');
        } elseif ($c['role'] === 'principal' && (int)$c['actif'] && nb_principaux_actifs() <= 1) {
            flash('danger', 'Impossible : c\'est le dernier administrateur principal actif.');
        } elseif ($action === 'basculer') {
            $pdo->prepare('UPDATE admins SET actif = 1 - actif WHERE id = ?')->execute([$c['id']]);
            flash('success', (int)$c['actif']
                ? 'Compte de ' . e($c['nom']) . ' désactivé : cette personne ne peut plus se connecter.'
                : 'Compte de ' . e($c['nom']) . ' réactivé.');
        } else {
            // Les paiements déjà confirmés par ce compte sont conservés (confirme_par passe à NULL)
            $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$c['id']]);
            flash('success', 'Compte de ' . e($c['nom']) . ' supprimé.');
        }
        redirect('administrateurs.php');
    }
}

$adminPage = 'admins';
$titre = 'Administrateurs';
require __DIR__ . '/_header.php';

$comptes = $pdo->query("SELECT id, username, nom, email, role, actif, derniere_connexion, date_creation
                          FROM admins ORDER BY role = 'principal' DESC, actif DESC, nom")->fetchAll();
$enEdition = $cible !== null;
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Administrateurs <span class="compteur-titre">(<?= count($comptes) ?>)</span></h1>
    <p class="admin-sous">Créez les comptes de vos employés et gérez leurs accès à l'administration.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-xxl-4">
    <form method="post" class="panel" autocomplete="off" id="formulaire-compte">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $enEdition ? 'modifier' : 'ajouter' ?>">
      <?php if ($enEdition): ?><input type="hidden" name="id" value="<?= (int)$cible['id'] ?>"><?php endif; ?>
      <h2 class="h5 mb-4"><?= $enEdition ? 'Modifier le compte de ' . e($cible['nom']) : 'Nouveau compte' ?></h2>
      <div class="mb-3"><label class="form-label" for="nom">Nom</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="100"></div>
      <div class="mb-3"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= e($val['email']) ?>" required maxlength="150"></div>
      <div class="mb-3"><label class="form-label" for="username">Nom d'utilisateur</label><input class="form-control" id="username" name="username" value="<?= e($val['username']) ?>" required maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="off"></div>
      <div class="mb-3">
        <label class="form-label" for="role">Rôle</label>
        <select class="form-select" id="role" name="role">
          <?php foreach (ROLES_ADMIN as $k => [$libelle]): ?>
            <option value="<?= e($k) ?>" <?= $val['role'] === $k ? 'selected' : '' ?>><?= e($libelle) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Un employé gère produits, commandes, clients et messages, sans supprimer de produit ni modifier les paiements.</div>
      </div>
      <div class="mb-3"><label class="form-label" for="mdp"><?= $enEdition ? 'Nouveau mot de passe <span class="text-muted fw-normal">(facultatif)</span>' : 'Mot de passe' ?></label><input class="form-control" id="mdp" name="mdp" type="password" <?= $enEdition ? '' : 'required' ?> minlength="<?= MDP_LONGUEUR_MIN ?>" autocomplete="new-password"></div>
      <div><label class="form-label" for="mdp_confirm">Confirmation du mot de passe</label><input class="form-control" id="mdp_confirm" name="mdp_confirm" type="password" <?= $enEdition ? '' : 'required' ?> autocomplete="new-password"></div>
      <div class="form-text"><?= MDP_LONGUEUR_MIN ?> caractères minimum, dont au moins une lettre et un chiffre.</div>
      <div class="admin-form-pied">
        <button class="btn btn-rose px-4"><i class="fa-solid <?= $enEdition ? 'fa-floppy-disk' : 'fa-user-plus' ?> me-1"></i><?= $enEdition ? 'Enregistrer' : 'Créer le compte' ?></button>
        <?php if ($enEdition): ?><a href="administrateurs.php" class="btn btn-outline-rose">Annuler</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="col-xxl-8">
    <div class="panel panel-table">
      <div class="table-responsive">
        <table class="table table-rose align-middle">
          <thead><tr><th>Compte</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($comptes as $c): $estMoi = (int)$c['id'] === $moi; [$rl, $ri] = ROLES_ADMIN[$c['role']] ?? [$c['role'], 'fa-user']; ?>
            <tr class="<?= $c['actif'] ? '' : 'opacity-50' ?>">
              <td style="min-width:200px">
                <span class="cellule-principale"><?= e($c['nom']) ?></span><?= $estMoi ? ' <span class="badge rounded-pill text-bg-light border ms-1">Vous</span>' : '' ?>
                <div class="small text-muted"><?= e($c['username']) ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?></div>
              </td>
              <td class="text-nowrap"><span class="badge rounded-pill role-<?= e($c['role']) ?>"><i class="fa-solid <?= $ri ?> me-1"></i><?= e($rl) ?></span></td>
              <td><?= $c['actif'] ? '<span class="badge rounded-pill text-bg-success">Actif</span>' : '<span class="badge rounded-pill text-bg-secondary">Désactivé</span>' ?></td>
              <td class="small text-nowrap"><?= $c['derniere_connexion'] ? e(date('d/m/Y H:i', strtotime($c['derniere_connexion']))) : 'Jamais' ?></td>
              <td class="text-end">
                <div class="admin-actions">
                  <?php if ($estMoi): ?>
                    <a class="btn btn-outline-rose btn-sm text-nowrap" href="profil.php"><i class="fa-solid fa-user-pen me-1"></i>Mon profil</a>
                  <?php else: ?>
                    <a class="btn btn-outline-rose btn-sm btn-ic" href="administrateurs.php?modifier=<?= (int)$c['id'] ?>#formulaire-compte" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                    <form method="post" <?= $c['actif'] ? 'data-confirm-titre="Désactiver ce compte ?" data-confirm="' . e($c['nom']) . ' ne pourra plus se connecter tant que le compte restera désactivé." data-confirm-ok="Désactiver"' : '' ?>>
                      <?= csrf_field() ?><input type="hidden" name="action" value="basculer"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button class="btn btn-outline-rose btn-sm btn-ic" title="<?= $c['actif'] ? 'Désactiver' : 'Réactiver' ?>"><i class="fa-solid <?= $c['actif'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
                    </form>
                    <form method="post" data-confirm-titre="Supprimer ce compte ?" data-confirm="Le compte de <?= e($c['nom']) ?> sera supprimé définitivement. Les paiements qu'il a confirmés restent enregistrés. Pour suspendre l'accès sans supprimer, préférez « Désactiver »." data-confirm-ok="Supprimer">
                      <?= csrf_field() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button class="btn btn-outline-danger btn-sm btn-ic" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
