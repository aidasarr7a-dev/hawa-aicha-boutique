<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_comptes.php';

// Page entière réservée à l'administrateur principal (vérifié à chaque affichage et à chaque action)
exiger_principal('index.php', 'La gestion des administrateurs est réservée à l\'administrateur principal.');

$pdo = db();
$moi = (int)$adminConnecte['id'];

function charger_admin(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, username, prenom, nom, email, telephone, role, actif FROM admins WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

$erreurs = [];
$vide = ['prenom' => '', 'nom' => '', 'username' => '', 'email' => '', 'telephone' => '', 'role' => 'employe'];
$valAjout = $vide;
$modaleOuverte = '';   // fenêtre à rouvrir automatiquement : « ajout » ou « modif »

// Compte à modifier : ?modifier=ID (lien du tableau) ou formulaire de modification envoyé
$editId = (int)($_GET['modifier'] ?? ((($_POST['action'] ?? '') === 'modifier') ? ($_POST['id'] ?? 0) : 0));
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
$valModif = $cible ? array_map('strval', array_intersect_key($cible, $vide)) : $vide;
if ($cible) {
    $modaleOuverte = 'modif';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter' || $action === 'modifier') {
        $v = champs_compte() + ['role' => (string)($_POST['role'] ?? '')];
        if (!isset(ROLES_ADMIN[$v['role']])) {
            $erreurs[] = 'Rôle invalide.';
        }
        $erreurs = array_merge($erreurs, erreurs_compte($v, $action === 'modifier' ? (int)$cible['id'] : 0));
        $mdp = (string)($_POST['mdp'] ?? '');
        $mdpConfirm = (string)($_POST['mdp_confirm'] ?? '');
        if ($action === 'ajouter' || $mdp !== '' || $mdpConfirm !== '') {
            $erreurs = array_merge($erreurs, erreurs_mot_de_passe($mdp, $mdpConfirm));
        }
        if ($action === 'modifier' && $cible['role'] === 'principal' && $v['role'] !== 'principal'
            && (int)$cible['actif'] && nb_principaux_actifs() <= 1) {
            $erreurs[] = 'Impossible de retirer le rôle principal au dernier administrateur principal actif.';
        }

        if (!$erreurs && $action === 'ajouter') {
            $pdo->prepare('INSERT INTO admins (username, prenom, nom, email, telephone, role, actif, password_hash) VALUES (?, ?, ?, ?, ?, ?, 1, ?)')
                ->execute([$v['username'], $v['prenom'], $v['nom'], $v['email'], $v['telephone'] ?: null, $v['role'], password_hash($mdp, PASSWORD_DEFAULT)]);
            flash('success', 'Compte créé pour ' . e(nom_complet_admin($v)) . ' (' . e(ROLES_ADMIN[$v['role']][0]) . '). Identifiant de connexion : « ' . e($v['username']) . ' ».');
            redirect('administrateurs.php');
        }
        if (!$erreurs && $action === 'modifier') {
            $pdo->prepare('UPDATE admins SET prenom = ?, nom = ?, username = ?, email = ?, telephone = ?, role = ? WHERE id = ?')
                ->execute([$v['prenom'], $v['nom'], $v['username'], $v['email'], $v['telephone'] ?: null, $v['role'], $cible['id']]);
            if ($mdp !== '') {
                $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($mdp, PASSWORD_DEFAULT), $cible['id']]);
            }
            flash('success', 'Compte de ' . e(nom_complet_admin($v)) . ' modifié' . ($mdp !== '' ? ', avec un nouveau mot de passe' : '') . '.');
            redirect('administrateurs.php');
        }
        // Erreurs : la fenêtre se rouvre avec les valeurs saisies (les erreurs s'affichent en Toast)
        if ($action === 'ajouter') {
            $valAjout = $v;
            $modaleOuverte = 'ajout';
        } else {
            $valModif = $v;
            $modaleOuverte = 'modif';
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
                ? 'Compte de ' . e(nom_complet_admin($c)) . ' désactivé : cette personne ne peut plus se connecter.'
                : 'Compte de ' . e(nom_complet_admin($c)) . ' réactivé.');
        } else {
            // Les paiements déjà confirmés par ce compte sont conservés (confirme_par passe à NULL)
            $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$c['id']]);
            flash('success', 'Compte de ' . e(nom_complet_admin($c)) . ' supprimé.');
        }
        redirect('administrateurs.php');
    }
}

/** Fenêtre modale du formulaire de compte (ajout ou modification). */
function modale_compte(string $mode, array $val, ?array $cible): string
{
    $ajout = $mode === 'ajout';
    $id = $ajout ? 'modale-ajout' : 'modale-modif';
    ob_start(); ?>
    <div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-labelledby="<?= $id ?>-titre" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="post" class="modal-content admin-modale" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $ajout ? 'ajouter' : 'modifier' ?>">
          <?php if (!$ajout): ?><input type="hidden" name="id" value="<?= (int)$cible['id'] ?>"><?php endif; ?>
          <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
              <span class="admin-modale-ic"><i class="fa-solid <?= $ajout ? 'fa-user-plus' : 'fa-user-pen' ?>"></i></span>
              <div>
                <h2 class="modal-title h5" id="<?= $id ?>-titre"><?= $ajout ? 'Ajouter un administrateur' : 'Modifier le compte de ' . e(nom_complet_admin($cible)) ?></h2>
                <p class="text-muted small mb-0"><?= $ajout ? 'La personne pourra se connecter avec son identifiant ou son e-mail.' : 'Laissez le mot de passe vide pour ne pas le changer.' ?></p>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-prenom">Prénom</label><input class="form-control" id="<?= $id ?>-prenom" name="prenom" value="<?= e($val['prenom']) ?>" required maxlength="100"></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-nom">Nom</label><input class="form-control" id="<?= $id ?>-nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="100"></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-email">Adresse e-mail</label><input class="form-control" id="<?= $id ?>-email" name="email" type="email" value="<?= e($val['email']) ?>" required maxlength="150"></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-tel">Téléphone <span class="text-muted fw-normal">(facultatif)</span></label><input class="form-control" id="<?= $id ?>-tel" name="telephone" type="tel" value="<?= e($val['telephone']) ?>" maxlength="30"></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-username">Nom d'utilisateur</label><input class="form-control" id="<?= $id ?>-username" name="username" value="<?= e($val['username']) ?>" required maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="off"></div>
              <div class="col-md-6">
                <label class="form-label" for="<?= $id ?>-role">Rôle</label>
                <select class="form-select" id="<?= $id ?>-role" name="role">
                  <?php foreach (ROLES_ADMIN as $k => [$libelle]): ?>
                    <option value="<?= e($k) ?>" <?= $val['role'] === $k ? 'selected' : '' ?>><?= e($libelle) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12"><p class="form-text mt-0">Un employé gère produits, catégories, commandes, clients et messages, sans pouvoir supprimer de produit, de catégorie ou de commande, ni modifier les paiements.</p></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-mdp"><?= $ajout ? 'Mot de passe' : 'Nouveau mot de passe' ?></label><?= champ_mot_de_passe($id . '-mdp', 'mdp', 'new-password', $ajout, 'minlength="' . MDP_LONGUEUR_MIN . '"') ?></div>
              <div class="col-md-6"><label class="form-label" for="<?= $id ?>-mdp2">Confirmation</label><?= champ_mot_de_passe($id . '-mdp2', 'mdp_confirm', 'new-password', $ajout) ?></div>
              <div class="col-12"><p class="form-text mt-0"><?= MDP_LONGUEUR_MIN ?> caractères minimum, dont au moins une lettre et un chiffre.</p></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-rose" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-rose px-4"><i class="fa-solid <?= $ajout ? 'fa-user-plus' : 'fa-floppy-disk' ?> me-1"></i><?= $ajout ? 'Créer le compte' : 'Enregistrer' ?></button>
          </div>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

$adminPage = 'admins';
$titre = 'Administrateurs';
require __DIR__ . '/_header.php';

$comptes = $pdo->query("SELECT id, username, prenom, nom, email, telephone, role, actif, derniere_connexion, date_creation
                          FROM admins ORDER BY role = 'principal' DESC, actif DESC, nom, prenom")->fetchAll();
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Administrateurs <span class="compteur-titre">(<?= count($comptes) ?>)</span></h1>
    <p class="admin-sous">Créez les comptes de vos employés et gérez leurs accès à l'administration.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="btn btn-rose" data-bs-toggle="modal" data-bs-target="#modale-ajout"><i class="fa-solid fa-plus me-1"></i>Ajouter un administrateur</button>
  </div>
</div>

<div class="panel panel-table">
  <div class="table-responsive">
    <table class="table table-rose align-middle">
      <thead><tr><th>Compte</th><th>Contact</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($comptes as $c): $estMoi = (int)$c['id'] === $moi; [$rl, $ri] = ROLES_ADMIN[$c['role']] ?? [$c['role'], 'fa-user']; $nc = nom_complet_admin($c); ?>
        <tr class="<?= $c['actif'] ? '' : 'opacity-50' ?>">
          <td style="min-width:200px">
            <div class="d-flex align-items-center gap-3">
              <span class="admin-connecte-avatar"><?= e(mb_strtoupper(mb_substr($nc, 0, 1))) ?></span>
              <div class="min-w-0">
                <span class="cellule-principale"><?= e($nc) ?></span><?= $estMoi ? ' <span class="badge rounded-pill text-bg-light border ms-1">Vous</span>' : '' ?>
                <div class="small text-muted"><?= e($c['username']) ?></div>
              </div>
            </div>
          </td>
          <td class="small" style="min-width:170px"><?= $c['email'] ? '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' : '—' ?><?= $c['telephone'] ? '<div><a href="tel:' . e($c['telephone']) . '">' . e($c['telephone']) . '</a></div>' : '' ?></td>
          <td class="text-nowrap"><span class="badge rounded-pill role-<?= e($c['role']) ?>"><i class="fa-solid <?= $ri ?> me-1"></i><?= e($rl) ?></span></td>
          <td><?= $c['actif'] ? '<span class="badge rounded-pill text-bg-success">Actif</span>' : '<span class="badge rounded-pill text-bg-secondary">Désactivé</span>' ?></td>
          <td class="small text-nowrap"><?= $c['derniere_connexion'] ? e(date('d/m/Y H:i', strtotime($c['derniere_connexion']))) : 'Jamais' ?></td>
          <td class="text-end">
            <div class="admin-actions">
              <?php if ($estMoi): ?>
                <a class="btn btn-outline-rose btn-sm text-nowrap" href="profil.php"><i class="fa-solid fa-user-pen me-1"></i>Mon profil</a>
              <?php else: ?>
                <a class="btn btn-outline-rose btn-sm btn-ic" href="administrateurs.php?modifier=<?= (int)$c['id'] ?>" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                <form method="post" <?= $c['actif'] ? 'data-confirm-titre="Désactiver ce compte ?" data-confirm="' . e($nc) . ' ne pourra plus se connecter tant que le compte restera désactivé." data-confirm-ok="Désactiver"' : '' ?>>
                  <?= csrf_field() ?><input type="hidden" name="action" value="basculer"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-outline-rose btn-sm btn-ic" title="<?= $c['actif'] ? 'Désactiver' : 'Réactiver' ?>"><i class="fa-solid <?= $c['actif'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
                </form>
                <form method="post" data-confirm-titre="Supprimer ce compte ?" data-confirm="Le compte de <?= e($nc) ?> sera supprimé définitivement. Les paiements qu'il a confirmés restent enregistrés. Pour suspendre l'accès sans supprimer, préférez « Désactiver »." data-confirm-ok="Supprimer">
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

<?= modale_compte('ajout', $valAjout, null) ?>
<?php if ($cible): ?><?= modale_compte('modif', $valModif, $cible) ?><?php endif; ?>
<?php if ($modaleOuverte): ?>
  <span hidden data-ouvrir-modale="#modale-<?= $modaleOuverte ?>"></span>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
