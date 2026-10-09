<?php
require_once __DIR__ . '/includes/functions.php';

$panierAvant = array_map('intval', cart_get());
[$lignes, $total] = cart_items();
// Vrai si un article a disparu ou si une quantité a été réduite au stock disponible
$panierAjuste = $panierAvant != array_map('intval', cart_get());
if (!$lignes) {
    // Formulaire renvoyé après une commande réussie (double clic, retour arrière) : on réaffiche la confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['derniere_commande'])) {
        redirect(base_url('merci.php?id=' . (int)$_SESSION['derniere_commande']));
    }
    flash('warning', 'Votre panier est vide.');
    redirect(base_url('panier.php'));
}

$erreurs = [];
$val = ['nom' => '', 'prenom' => '', 'telephone' => '', 'ville' => '', 'quartier' => '', 'adresse' => '', 'remarque' => '',
        'paiement' => MODES_PAIEMENT[0], 'mode_livraison' => MODES_LIVRAISON[0]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Jeton à usage unique : un double clic ou un renvoi du formulaire ne crée pas une deuxième commande
    $jeton = (string)($_POST['jeton'] ?? '');
    if ($jeton === '' || !hash_equals($_SESSION['jeton_commande'] ?? '', $jeton)) {
        if (!empty($_SESSION['derniere_commande'])) {
            redirect(base_url('merci.php?id=' . (int)$_SESSION['derniere_commande']));
        }
        flash('warning', 'Ce formulaire a déjà été envoyé. Merci de vérifier votre panier.');
        redirect(base_url('panier.php'));
    }
    unset($_SESSION['jeton_commande']);

    foreach ($val as $k => $_) {
        $val[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $tel = preg_replace('/[^\d+]/', '', $val['telephone']);

    if ($val['nom'] === '' || mb_strlen($val['nom']) > 100)       $erreurs[] = 'Veuillez indiquer votre nom.';
    if ($val['prenom'] === '' || mb_strlen($val['prenom']) > 100) $erreurs[] = 'Veuillez indiquer votre prénom.';
    if (strlen(preg_replace('/\D/', '', $tel)) < 8)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone / WhatsApp valide.';
    if ($val['ville'] === '')                                     $erreurs[] = 'Veuillez indiquer votre ville / commune.';
    if ($val['quartier'] === '')                                  $erreurs[] = 'Veuillez indiquer votre adresse / quartier.';
    if (!in_array($val['paiement'], MODES_PAIEMENT, true))        $erreurs[] = 'Veuillez choisir un moyen de paiement.';
    if (!in_array($val['mode_livraison'], MODES_LIVRAISON, true)) $erreurs[] = 'Veuillez choisir un mode de livraison.';
    foreach (['adresse' => 255, 'remarque' => 1000, 'ville' => 100, 'quartier' => 150] as $champ => $max) {
        if (mb_strlen($val[$champ]) > $max) $erreurs[] = 'Le champ « ' . $champ . ' » est trop long.';
    }

    if ($panierAjuste) {
        $erreurs[] = 'Le stock a changé : certaines quantités ont été ajustées au stock disponible. Vérifiez le récapitulatif ci-dessous puis confirmez à nouveau.';
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
            // Fiche client (une par numéro de téléphone, mise à jour à chaque commande)
            $pdo->prepare(
                'INSERT INTO clients (nom_complet, telephone, adresse, ville) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nom_complet = VALUES(nom_complet),
                                         adresse = VALUES(adresse), ville = VALUES(ville)'
            )->execute([$val['prenom'] . ' ' . $val['nom'], $tel,
                        implode(', ', array_filter([$val['quartier'], $val['adresse']])), $val['ville']]);
            $clientId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare(
                'INSERT INTO commandes (client_id, nom, prenom, telephone, ville, quartier, adresse, remarque,
                                        mode_livraison, frais_livraison, paiement, total, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
            );
            $ins->execute([$clientId, $val['nom'], $val['prenom'], $tel, $val['ville'], $val['quartier'],
                           $val['adresse'] ?: null, $val['remarque'] ?: null, $val['mode_livraison'],
                           $val['paiement'], $totalReel, 'nouvelle']);
            $cmdId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE commandes SET numero_commande = ? WHERE id = ?')->execute([numero_commande($cmdId), $cmdId]);

            $it = $pdo->prepare('INSERT INTO commande_items (commande_id, produit_id, nom_produit, prix, quantite) VALUES (?, ?, ?, ?, ?)');
            foreach ($lignes as $l) {
                $it->execute([$cmdId, $l['p']['id'], $l['p']['nom'], $l['p']['prix'], $l['qty']]);
            }

            // Le paiement reste « en attente » tant que la boutique ne l'a pas vérifié et confirmé
            $pdo->prepare("INSERT INTO paiements (commande_id, mode_paiement, montant, statut_paiement) VALUES (?, ?, ?, 'en_attente')")
                ->execute([$cmdId, $val['paiement'], $totalReel]);
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

$_SESSION['jeton_commande'] = bin2hex(random_bytes(16));

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
<form method="post" class="row g-4" novalidate data-envoi-unique>
  <?= csrf_field() ?>
  <input type="hidden" name="jeton" value="<?= e($_SESSION['jeton_commande']) ?>">
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3">Vos informations</h2>
      <div class="row g-3">
        <div class="col-sm-6"><label class="form-label" for="prenom">Prénom *</label><input class="form-control" id="prenom" name="prenom" value="<?= e($val['prenom']) ?>" required autocomplete="given-name"></div>
        <div class="col-sm-6"><label class="form-label" for="nom">Nom *</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required autocomplete="family-name"></div>
        <div class="col-12"><label class="form-label" for="telephone">Téléphone / WhatsApp *</label><input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" placeholder="Ex : 77 000 00 00" value="<?= e($val['telephone']) ?>" required autocomplete="tel"></div>
        <div class="col-sm-6"><label class="form-label" for="quartier">Adresse / quartier *</label><input class="form-control" id="quartier" name="quartier" value="<?= e($val['quartier']) ?>" required autocomplete="address-line1"></div>
        <div class="col-sm-6"><label class="form-label" for="ville">Ville / commune *</label><input class="form-control" id="ville" name="ville" value="<?= e($val['ville']) ?>" required autocomplete="address-level2"></div>
        <div class="col-12"><label class="form-label" for="adresse">Point de repère (facultatif)</label><input class="form-control" id="adresse" name="adresse" value="<?= e($val['adresse']) ?>" placeholder="Près de la mosquée, en face de la pharmacie…"></div>
        <div class="col-12"><label class="form-label" for="remarque">Instructions de livraison (facultatif)</label><textarea class="form-control" id="remarque" name="remarque" rows="3" placeholder="Taille, couleur, heure de livraison…"><?= e($val['remarque']) ?></textarea></div>
      </div>
    </div>

    <div class="panel mt-4">
      <h2 class="h5 mb-3">Livraison</h2>
      <div class="d-grid gap-2">
        <?php foreach (MODES_LIVRAISON as $mode): ?>
          <label class="pay-option position-relative">
            <input class="form-check-input position-absolute" style="left:1rem;top:1rem" type="radio" name="mode_livraison" value="<?= e($mode) ?>" <?= $val['mode_livraison'] === $mode ? 'checked' : '' ?>>
            <?= e($mode) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-truck-fast me-1"></i>Les frais de livraison éventuels vous sont communiqués par la boutique selon votre zone.</p>
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
      <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Votre paiement reste « en attente » jusqu'à sa vérification par la boutique. Les espèces sont réglées à la livraison ou au retrait.</p>
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
      <button class="btn btn-rose btn-lg w-100" data-texte-envoi="Enregistrement…"><i class="fa-solid fa-check me-2"></i>Confirmer la commande</button>
      <a href="<?= e(base_url('panier.php')) ?>" class="d-block text-center mt-3 small">Modifier mon panier</a>
    </div>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
