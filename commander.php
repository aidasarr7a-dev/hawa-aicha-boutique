<?php
require_once __DIR__ . '/includes/functions.php';

[$lignes, $total] = cart_items();
if (!$lignes) {
    flash('warning', 'Votre panier est vide.');
    redirect(base_url('panier.php'));
}

$erreurs = [];
$val = ['nom' => '', 'prenom' => '', 'telephone' => '', 'ville' => '', 'quartier' => '', 'adresse' => '', 'remarque' => '', 'paiement' => MODES_PAIEMENT[0]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($val as $k => $_) {
        $val[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $tel = preg_replace('/[^\d+]/', '', $val['telephone']);

    if ($val['nom'] === '' || mb_strlen($val['nom']) > 100)       $erreurs[] = 'Veuillez indiquer votre nom.';
    if ($val['prenom'] === '' || mb_strlen($val['prenom']) > 100) $erreurs[] = 'Veuillez indiquer votre prénom.';
    if (strlen(preg_replace('/\D/', '', $tel)) < 8)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone / WhatsApp valide.';
    if ($val['ville'] === '')                                     $erreurs[] = 'Veuillez indiquer votre ville.';
    if ($val['quartier'] === '')                                  $erreurs[] = 'Veuillez indiquer votre quartier.';
    if (!in_array($val['paiement'], MODES_PAIEMENT, true))        $erreurs[] = 'Veuillez choisir un moyen de paiement.';
    foreach (['adresse' => 255, 'remarque' => 1000, 'ville' => 100, 'quartier' => 150] as $champ => $max) {
        if (mb_strlen($val[$champ]) > $max) $erreurs[] = 'Le champ « ' . $champ . ' » est trop long.';
    }

    if (!$erreurs) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $totalReel = 0;
            foreach ($lignes as $l) {
                // Décrémente le stock seulement s'il est suffisant
                $up = $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ? AND actif = 1 AND stock >= ?');
                $up->execute([$l['qty'], $l['p']['id'], $l['qty']]);
                if ($up->rowCount() !== 1) {
                    throw new RuntimeException('Le produit « ' . $l['p']['nom'] . ' » n\'est plus disponible en quantité suffisante.');
                }
                $totalReel += $l['qty'] * (int)$l['p']['prix'];
            }
            $ins = $pdo->prepare(
                'INSERT INTO commandes (nom, prenom, telephone, ville, quartier, adresse, remarque, paiement, total, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$val['nom'], $val['prenom'], $tel, $val['ville'], $val['quartier'],
                           $val['adresse'] ?: null, $val['remarque'] ?: null, $val['paiement'], $totalReel, 'nouvelle']);
            $cmdId = (int)$pdo->lastInsertId();
            $it = $pdo->prepare('INSERT INTO commande_items (commande_id, produit_id, nom_produit, prix, quantite) VALUES (?, ?, ?, ?, ?)');
            foreach ($lignes as $l) {
                $it->execute([$cmdId, $l['p']['id'], $l['p']['nom'], $l['p']['prix'], $l['qty']]);
            }
            $pdo->commit();

            $_SESSION['panier'] = [];
            $_SESSION['derniere_commande'] = $cmdId;
            redirect(base_url('merci.php?id=' . $cmdId));
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreurs[] = $ex->getMessage() . ' Merci de vérifier votre panier.';
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreurs[] = 'Une erreur est survenue. Merci de réessayer ou de nous écrire sur WhatsApp.';
        }
        [$lignes, $total] = cart_items();
    }
}

$page = 'panier';
$titre = 'Passer la commande';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title mb-2">Passer la commande</h1>
<div class="steps mb-4"><span>1. Panier</span><span class="on">2. Mes informations</span><span>3. Confirmation</span></div>

<?php if ($erreurs): ?>
  <div class="alert alert-danger rounded-4">
    <ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$lignes): ?>
  <div class="panel empty-state"><p>Votre panier est vide.</p><a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Voir les produits</a></div>
<?php else: ?>
<form method="post" class="row g-4" novalidate>
  <?= csrf_field() ?>
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3">Vos informations</h2>
      <div class="row g-3">
        <div class="col-sm-6"><label class="form-label" for="prenom">Prénom *</label><input class="form-control" id="prenom" name="prenom" value="<?= e($val['prenom']) ?>" required autocomplete="given-name"></div>
        <div class="col-sm-6"><label class="form-label" for="nom">Nom *</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required autocomplete="family-name"></div>
        <div class="col-12"><label class="form-label" for="telephone">Téléphone / WhatsApp *</label><input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" placeholder="Ex : 77 000 00 00" value="<?= e($val['telephone']) ?>" required autocomplete="tel"></div>
        <div class="col-sm-6"><label class="form-label" for="ville">Ville *</label><input class="form-control" id="ville" name="ville" value="<?= e($val['ville']) ?>" required autocomplete="address-level2"></div>
        <div class="col-sm-6"><label class="form-label" for="quartier">Quartier *</label><input class="form-control" id="quartier" name="quartier" value="<?= e($val['quartier']) ?>" required></div>
        <div class="col-12"><label class="form-label" for="adresse">Adresse / repère (facultatif)</label><input class="form-control" id="adresse" name="adresse" value="<?= e($val['adresse']) ?>" placeholder="Près de la mosquée, en face de la pharmacie…"></div>
        <div class="col-12"><label class="form-label" for="remarque">Remarque (facultatif)</label><textarea class="form-control" id="remarque" name="remarque" rows="3" placeholder="Taille, couleur, heure de livraison…"><?= e($val['remarque']) ?></textarea></div>
      </div>
    </div>

    <div class="panel mt-4">
      <h2 class="h5 mb-3">Moyen de paiement</h2>
      <div class="d-grid gap-2">
        <?php foreach (MODES_PAIEMENT as $i => $mode): ?>
          <label class="pay-option position-relative">
            <input class="form-check-input position-absolute" style="left:1rem;top:1rem" type="radio" name="paiement" value="<?= e($mode) ?>" <?= $val['paiement'] === $mode ? 'checked' : '' ?>>
            <?= e($mode) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Après la commande, la boutique vous contacte sur WhatsApp pour confirmer la livraison, ses frais et le paiement.</p>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel">
      <h2 class="h5 mb-3">Votre commande</h2>
      <?php foreach ($lignes as $l): ?>
        <div class="d-flex justify-content-between gap-3 mb-2 small">
          <span><?= e($l['p']['nom']) ?> <span class="text-muted">× <?= $l['qty'] ?></span></span>
          <span class="fw-medium text-nowrap"><?= fcfa($l['sous_total']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="d-flex justify-content-between align-items-center mb-3"><span class="fw-medium">Total</span><span class="total-line"><?= fcfa($total) ?></span></div>
      <p class="small text-muted">Frais de livraison communiqués selon votre zone.</p>
      <button class="btn btn-rose btn-lg w-100"><i class="fa-solid fa-check me-2"></i>Confirmer la commande</button>
      <a href="<?= e(base_url('panier.php')) ?>" class="d-block text-center mt-3 small">Modifier mon panier</a>
    </div>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
