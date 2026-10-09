<?php
/**
 * Espace client : mes commandes (statut, paiement, facture) et mes informations.
 */
require_once __DIR__ . '/includes/functions.php';
$client = require_client();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'deconnexion') {
        unset($_SESSION['client_id']);
        session_regenerate_id(true);
        flash('success', 'Vous êtes déconnecté(e). À bientôt !');
        redirect(base_url('index.php'));
    }

    if ($action === 'infos') {
        $nom = trim((string)($_POST['nom_complet'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $adresse = trim((string)($_POST['adresse'] ?? ''));
        $ville = trim((string)($_POST['ville'] ?? ''));
        $err = [];
        if (mb_strlen($nom) < 3 || mb_strlen($nom) > 200) $err[] = 'Veuillez indiquer votre nom complet.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'L\'adresse e-mail n\'est pas valide.';
        if (mb_strlen($adresse) > 255 || mb_strlen($ville) > 100) $err[] = 'L\'adresse ou la ville est trop longue.';
        if (!$err && $email !== '') {
            $st = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE email = ? AND id <> ?');
            $st->execute([$email, $client['id']]);
            if ((int)$st->fetchColumn() > 0) $err[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        }
        if ($err) {
            flash('danger', implode('<br>', array_map('e', $err)));
        } else {
            $pdo->prepare('UPDATE clients SET nom_complet = ?, email = ?, adresse = ?, ville = ? WHERE id = ?')
                ->execute([$nom, $email ?: null, $adresse ?: null, $ville ?: null, $client['id']]);
            flash('success', 'Vos informations sont enregistrées.');
        }
        redirect(base_url('mon-compte.php?onglet=infos'));
    }

    if ($action === 'mot_de_passe') {
        $actuel = (string)($_POST['actuel'] ?? '');
        $nouveau = (string)($_POST['nouveau'] ?? '');
        if (!password_verify($actuel, $client['mot_de_passe_hash'])) {
            flash('danger', 'Le mot de passe actuel est incorrect.');
        } elseif (strlen($nouveau) < 8 || $nouveau !== ($_POST['confirmation'] ?? '')) {
            flash('danger', 'Le nouveau mot de passe doit contenir au moins 8 caractères et être confirmé.');
        } else {
            $pdo->prepare('UPDATE clients SET mot_de_passe_hash = ? WHERE id = ?')->execute([password_hash($nouveau, PASSWORD_DEFAULT), $client['id']]);
            flash('success', 'Mot de passe modifié.');
        }
        redirect(base_url('mon-compte.php?onglet=infos'));
    }
}

$onglet = ($_GET['onglet'] ?? '') === 'infos' ? 'infos' : 'commandes';

// Détail d'une commande
$detail = null;
if (!empty($_GET['commande'])) {
    $st = $pdo->prepare('SELECT * FROM commandes WHERE id = ? AND client_id = ?');
    $st->execute([(int)$_GET['commande'], $client['id']]);
    $detail = $st->fetch() ?: null;
    if ($detail) {
        $it = $pdo->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
        $it->execute([$detail['id']]);
        $detailItems = $it->fetchAll();
        $pa = $pdo->prepare('SELECT * FROM paiements WHERE commande_id = ?');
        $pa->execute([$detail['id']]);
        $detailPaiement = $pa->fetch() ?: null;
    }
}

$cmds = $pdo->prepare(
    'SELECT c.*, pa.statut_paiement, (SELECT SUM(quantite) FROM commande_items i WHERE i.commande_id = c.id) AS nb_articles
       FROM commandes c LEFT JOIN paiements pa ON pa.commande_id = c.id
      WHERE c.client_id = ? ORDER BY c.id DESC'
);
$cmds->execute([$client['id']]);
$commandes = $cmds->fetchAll();

$page = 'compte';
$titre = 'Mon compte';
require __DIR__ . '/includes/header.php';
?>

<div class="compte-entete mb-4">
  <div class="d-flex align-items-center gap-3">
    <span class="avatar"><?= e(mb_strtoupper(mb_substr($client['nom_complet'], 0, 1))) ?></span>
    <div>
      <h1 class="page-title mb-0">Bonjour <?= e(strtok($client['nom_complet'], ' ')) ?></h1>
      <div class="text-muted small"><?= e($client['telephone']) ?><?= $client['email'] ? ' · ' . e($client['email']) : '' ?></div>
    </div>
  </div>
  <form method="post" class="ms-auto"><?= csrf_field() ?><input type="hidden" name="action" value="deconnexion">
    <button class="btn btn-outline-rose btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i>Déconnexion</button></form>
</div>

<div class="onglets onglets-large mb-4">
  <a class="<?= $onglet === 'commandes' ? 'on' : '' ?>" href="mon-compte.php"><i class="fa-solid fa-bag-shopping me-1"></i> Mes commandes <span class="compteur"><?= count($commandes) ?></span></a>
  <a class="<?= $onglet === 'infos' ? 'on' : '' ?>" href="mon-compte.php?onglet=infos"><i class="fa-solid fa-user-pen me-1"></i> Mes informations</a>
</div>

<?php if ($onglet === 'commandes'): ?>

  <?php if ($detail): ?>
    <div class="panel mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h2 class="h5 mb-1">Commande <?= e($detail['numero_commande']) ?></h2>
          <div class="small text-muted">du <?= e(date('d/m/Y à H:i', strtotime($detail['created_at']))) ?> · <?= e($detail['mode_livraison']) ?></div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <?= badge_statut($detail['statut']) ?>
          <a class="btn btn-rose btn-sm" target="_blank" href="<?= e(base_url('recu.php?id=' . (int)$detail['id'])) ?>"><i class="fa-solid fa-file-invoice me-1"></i>Ma facture</a>
        </div>
      </div>
      <?= suivi_commande($detail['statut']) ?>
      <div class="row g-4 mt-1">
        <div class="col-lg-6">
          <?php foreach ($detailItems as $i): ?>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span><?= e($i['nom_produit']) ?> × <?= (int)$i['quantite'] ?></span><span class="text-nowrap"><?= fcfa($i['prix'] * $i['quantite']) ?></span></div>
          <?php endforeach; ?>
          <?php if ((int)$detail['frais_livraison'] > 0): ?><div class="d-flex justify-content-between small py-1"><span>Frais de livraison</span><span><?= fcfa($detail['frais_livraison']) ?></span></div><?php endif; ?>
          <div class="d-flex justify-content-between pt-2"><strong>Total</strong><strong class="total-line fs-6"><?= fcfa($detail['total']) ?></strong></div>
        </div>
        <div class="col-lg-6">
          <?php if ($detailPaiement) bloc_payer($detail, $detailPaiement); ?>
        </div>
      </div>
      <a href="mon-compte.php" class="d-inline-block small mt-3"><i class="fa-solid fa-arrow-left me-1"></i> Toutes mes commandes</a>
    </div>
  <?php endif; ?>

  <?php if ($commandes): ?>
    <div class="liste-commandes">
      <?php foreach ($commandes as $c): ?>
        <a class="ligne-commande <?= $detail && (int)$detail['id'] === (int)$c['id'] ? 'active' : '' ?>" href="mon-compte.php?commande=<?= (int)$c['id'] ?>">
          <div><strong><?= e($c['numero_commande']) ?></strong><div class="small text-muted"><?= e(date('d/m/Y', strtotime($c['created_at']))) ?> · <?= (int)$c['nb_articles'] ?> article(s)</div></div>
          <div class="text-nowrap fw-semibold"><?= fcfa($c['total']) ?></div>
          <div class="d-flex flex-wrap gap-1 justify-content-end"><?= badge_statut($c['statut']) ?> <?= badge_paiement($c['statut_paiement']) ?></div>
          <i class="fa-solid fa-chevron-right text-muted"></i>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel empty-state">
      <i class="fa-solid fa-bag-shopping"></i>
      <p class="mt-3 mb-3 text-muted">Vous n'avez pas encore passé de commande.</p>
      <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Découvrir nos produits</a>
    </div>
  <?php endif; ?>

<?php else: ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <form method="post" class="panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="infos">
        <h2 class="h5 mb-3">Mes informations</h2>
        <div class="row g-3">
          <div class="col-12"><label class="form-label" for="nom_complet">Nom complet</label><input class="form-control" id="nom_complet" name="nom_complet" value="<?= e($client['nom_complet']) ?>" required maxlength="200"></div>
          <div class="col-sm-6"><label class="form-label" for="tel">Téléphone</label><input class="form-control" id="tel" value="<?= e($client['telephone']) ?>" disabled><div class="form-text">Pour changer de numéro, contactez la boutique.</div></div>
          <div class="col-sm-6"><label class="form-label" for="email">E-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= e($client['email'] ?? '') ?>" maxlength="150"></div>
          <div class="col-12"><label class="form-label" for="adresse">Adresse de livraison habituelle</label><input class="form-control" id="adresse" name="adresse" value="<?= e($client['adresse'] ?? '') ?>" maxlength="255" placeholder="Quartier, rue, point de repère"></div>
          <div class="col-sm-6"><label class="form-label" for="ville">Ville / commune</label><input class="form-control" id="ville" name="ville" value="<?= e($client['ville'] ?? '') ?>" maxlength="100"></div>
        </div>
        <button class="btn btn-rose mt-4 px-4">Enregistrer</button>
      </form>
    </div>
    <div class="col-lg-5">
      <form method="post" class="panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="mot_de_passe">
        <h2 class="h5 mb-3">Changer mon mot de passe</h2>
        <div class="mb-3"><label class="form-label" for="actuel">Mot de passe actuel</label><input class="form-control" id="actuel" name="actuel" type="password" required autocomplete="current-password"></div>
        <div class="mb-3"><label class="form-label" for="nouveau">Nouveau (8 caractères min.)</label><input class="form-control" id="nouveau" name="nouveau" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="mb-4"><label class="form-label" for="confirmation">Confirmation</label><input class="form-control" id="confirmation" name="confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn-outline-rose px-4">Modifier</button>
      </form>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
